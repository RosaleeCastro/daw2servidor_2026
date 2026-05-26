# 005 - REST + OpenAPI

## ⚡ Regla de oro

```
URL + método HTTP = qué hace
GET    /recurso      → listar
GET    /recurso/1    → consultar uno
POST   /recurso      → crear
PATCH  /recurso/1    → modificar parcialmente
DELETE /recurso/1    → eliminar
```

> REST no usa XML ni Envelope. Todo es **JSON + método HTTP + URL**.

---

## 🗂️ Cuatro subcarpetas — cuándo usar cada una

| Subcarpeta         | Datos           | Úsala cuando                      |
| ------------------ | --------------- | --------------------------------- |
| `api_libros`       | Archivo `.json` | No pide MySQL, CRUD simple        |
| `gestorTareas_api` | Archivo `.json` | No pide MySQL + necesitas filtros |
| `api_videojuegos`  | MySQL           | Pide base de datos                |
| `apiEstudios`      | MySQL           | Pide BD + PATCH con muchos campos |

---

## 🔧 Funciones reutilizables en TODOS los ejercicios REST

Estas tres van siempre. Cópialas al inicio de cualquier API:

```php
<?php
header("Content-Type: application/json; charset=utf-8");

// 1. Responder JSON con código HTTP — el más importante
function responder($codigo, $datos = null) {
    http_response_code($codigo);
    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// 2. Leer el JSON que manda el cliente (POST / PATCH)
function leerJSONBody() {
    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if ($raw !== "" && $data === null) responder(400, ["error" => "JSON inválido"]);
    return $data ?? [];
}

// 3. Detectar método HTTP y ruta
$metodo  = $_SERVER["REQUEST_METHOD"];
$ruta    = $_SERVER["PATH_INFO"] ?? ($_GET["ruta"] ?? ""); // ← compatible XAMPP
$partes  = explode("/", trim($ruta, "/"));
$recurso = $partes[0] ?? "";   // "libros", "tareas", "videojuegos"...
$id      = $partes[1] ?? null; // "1" o null
```

---

## 📋 Códigos HTTP — los que usa el examen

| Código | Cuándo                                            |
| ------ | ------------------------------------------------- |
| `200`  | OK — consulta o actualización correcta            |
| `201`  | Created — recurso creado (POST)                   |
| `204`  | No Content — borrado correcto, sin body           |
| `400`  | Bad Request — datos inválidos o incompletos       |
| `404`  | Not Found — no existe ese recurso                 |
| `405`  | Method Not Allowed — método no permitido          |
| `409`  | Conflict — no se puede borrar (tiene FK en MySQL) |
| `500`  | Internal Server Error — error del servidor        |

---

## 📁 BLOQUE 1 — Sin MySQL (api_libros / gestorTareas)

### Leer y guardar en archivo JSON

```php
$archivo = __DIR__ . "/libros.json";

function leerDatos($archivo) {
    return json_decode(file_get_contents($archivo), true) ?? [];
}

function guardarDatos($archivo, $datos) {
    file_put_contents(
        $archivo,
        json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}
```

### Los 5 métodos con JSON local

```php
$datos = leerDatos($archivo);

// GET — listar todos
if ($metodo === "GET" && $id === null) {
    responder(200, ["total" => count($datos), "items" => $datos]);
}

// GET /id — consultar uno
if ($metodo === "GET" && $id !== null) {
    foreach ($datos as $item) {
        if ($item["id"] == $id) responder(200, $item);
    }
    responder(404, ["error" => "No encontrado"]);
}

// POST — crear
if ($metodo === "POST" && $id === null) {
    $body  = leerJSONBody();
    $titulo = trim($body["titulo"] ?? "");
    if ($titulo === "") responder(400, ["error" => "titulo obligatorio"]);

    // ID incremental sin base de datos
    $ids     = array_column($datos, "id");
    $nuevoId = empty($ids) ? 1 : max($ids) + 1;

    $nuevo = [
        "id"             => $nuevoId,
        "titulo"         => $titulo,
        "completada"     => false,
        "fecha_creacion" => date("Y-m-d H:i:s")   // ← automática
    ];

    $datos[] = $nuevo;
    guardarDatos($archivo, $datos);
    responder(201, $nuevo);
}

// PATCH — modificar solo los campos enviados
if ($metodo === "PATCH" && $id !== null) {
    $body = leerJSONBody();

    foreach ($datos as $i => $item) {
        if ($item["id"] == $id) {
            if (isset($body["titulo"]))     $datos[$i]["titulo"]     = trim($body["titulo"]);
            if (isset($body["completada"])) $datos[$i]["completada"] = (bool)$body["completada"];

            guardarDatos($archivo, $datos);
            responder(200, $datos[$i]);
        }
    }
    responder(404, ["error" => "No encontrado"]);
}

// DELETE — eliminar
if ($metodo === "DELETE" && $id !== null) {
    foreach ($datos as $i => $item) {
        if ($item["id"] == $id) {
            array_splice($datos, $i, 1);
            guardarDatos($archivo, $datos);
            responder(204);   // sin body
        }
    }
    responder(404, ["error" => "No encontrado"]);
}

responder(405, ["error" => "Método no permitido"]);
```

### Filtros por query string (gestorTareas)

```php
// Filtrar array con array_filter
if (isset($_GET["completada"]) && $_GET["completada"] !== "") {
    $val = strtolower($_GET["completada"]);
    if ($val !== "true" && $val !== "false") responder(400, ["error" => "true o false"]);
    $completada = ($val === "true");
    $datos = array_values(array_filter($datos, fn($t) => (bool)$t["completada"] === $completada));
}

if (isset($_GET["prioridad"]) && $_GET["prioridad"] !== "") {
    $p = strtolower(trim($_GET["prioridad"]));
    if (!in_array($p, ["baja", "media", "alta"], true)) responder(400, ["error" => "baja, media o alta"]);
    $datos = array_values(array_filter($datos, fn($t) => $t["prioridad"] === $p));
}
```

### Cliente JS con filtros (gestorTareas)

```javascript
// Construir URL compatible con XAMPP
function construirUrl(ruta, params = null) {
  let url = "apiTareas.php?ruta=" + encodeURIComponent(ruta);
  if (params && params.toString() !== "") url += "&" + params.toString();
  return url;
}

// Añadir filtros opcionales
const params = new URLSearchParams();
if (completada !== "") params.append("completada", completada);
if (prioridad !== "") params.append("prioridad", prioridad);

const url = construirUrl("/tareas", params);
// → apiTareas.php?ruta=/tareas&completada=false&prioridad=alta

const resp = await fetch(url);
const data = await resp.json();
```

---

## 📁 BLOQUE 2 — Con MySQL (api_videojuegos / apiEstudios)

### Conexión PDO reutilizable

```php
function obtenerPDO() {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=videojuegos_asir;charset=utf8mb4",
        "root", ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
```

### Buscar por ID — función reutilizable (evita repetir el SELECT)

```php
// Cambiar "tabla", "id_tabla" y campos según el ejercicio
function buscarPorId($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT id_estudio AS id, nombre, pais, ciudad, fundado_en, web
        FROM estudio
        WHERE id_estudio = :id
    ");
    $stmt->execute([":id" => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    // Convertir tipos — PDO devuelve TODO como string
    $row["id"]         = (int)$row["id"];
    $row["fundado_en"] = $row["fundado_en"] !== null ? (int)$row["fundado_en"] : null;
    return $row;
}
```

### Los 5 métodos con MySQL

```php
$pdo = obtenerPDO();

// GET — listar con filtros opcionales
if ($metodo === "GET" && $id === null) {
    $sql    = "SELECT id_estudio AS id, nombre, pais FROM estudio WHERE 1=1";
    $params = [];

    if (isset($_GET["pais"]) && trim($_GET["pais"]) !== "") {
        $sql .= " AND pais LIKE :pais";
        $params[":pais"] = "%" . trim($_GET["pais"]) . "%";
    }

    $sql .= " ORDER BY id_estudio ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    responder(200, ["total" => count($datos), "estudios" => $datos]);
}

// GET /id — consultar uno
if ($metodo === "GET" && $id !== null) {
    if (!is_numeric($id)) responder(400, ["error" => "ID no válido"]);
    $item = buscarPorId($pdo, (int)$id);
    if (!$item) responder(404, ["error" => "No encontrado"]);
    responder(200, $item);
}

// POST — crear
if ($metodo === "POST" && $id === null) {
    $data   = leerJSONBody();
    $nombre = trim($data["nombre"] ?? "");
    if ($nombre === "") responder(400, ["error" => "nombre obligatorio"]);

    $stmt = $pdo->prepare("
        INSERT INTO estudio (nombre, pais, ciudad, fundado_en, web)
        VALUES (:nombre, :pais, :ciudad, :fundado_en, :web)
    ");
    $stmt->execute([
        ":nombre"     => $nombre,
        ":pais"       => $data["pais"]       ?? null,
        ":ciudad"     => $data["ciudad"]     ?? null,
        ":fundado_en" => $data["fundado_en"] ?? null,
        ":web"        => $data["web"]        ?? null,
    ]);

    responder(201, buscarPorId($pdo, (int)$pdo->lastInsertId()));
}

// PATCH — modificar solo los campos enviados (DINÁMICO)
if ($metodo === "PATCH" && $id !== null) {
    if (!is_numeric($id)) responder(400, ["error" => "ID no válido"]);
    if (!buscarPorId($pdo, (int)$id)) responder(404, ["error" => "No encontrado"]);

    $data   = leerJSONBody();
    $campos = [];
    $params = [":id" => (int)$id];

    // Añadir solo los campos que llegan
    if (isset($data["nombre"])) {
        $campos[]          = "nombre = :nombre";
        $params[":nombre"]  = trim($data["nombre"]);
    }
    if (isset($data["pais"])) {
        $campos[]         = "pais = :pais";
        $params[":pais"]   = trim($data["pais"]) ?: null;
    }
    if (isset($data["ciudad"])) {
        $campos[]           = "ciudad = :ciudad";
        $params[":ciudad"]   = trim($data["ciudad"]) ?: null;
    }
    if (isset($data["fundado_en"])) {
        $campos[]               = "fundado_en = :fundado_en";
        $params[":fundado_en"]   = $data["fundado_en"] !== null ? (int)$data["fundado_en"] : null;
    }

    if (empty($campos)) responder(400, ["error" => "Sin campos válidos"]);

    // implode construye: "nombre = :nombre, pais = :pais"
    $pdo->prepare("UPDATE estudio SET " . implode(", ", $campos) . " WHERE id_estudio = :id")
        ->execute($params);

    responder(200, buscarPorId($pdo, (int)$id));
}

// DELETE
if ($metodo === "DELETE" && $id !== null) {
    if (!is_numeric($id)) responder(400, ["error" => "ID no válido"]);
    if (!buscarPorId($pdo, (int)$id)) responder(404, ["error" => "No encontrado"]);

    try {
        $pdo->prepare("DELETE FROM estudio WHERE id_estudio = :id")
            ->execute([":id" => (int)$id]);
        responder(204);
    } catch (PDOException $e) {
        responder(409, ["error" => "No se puede eliminar, tiene datos relacionados"]);
    }
}

responder(405, ["error" => "Método no permitido"]);
```

---

## 📄 OpenAPI YAML — estructura mínima reutilizable

> No ejecuta nada. **Documenta** la API para Swagger/Postman.

```yaml
openapi: 3.0.3
info:
  title: API REST Ejemplo
  version: 1.0.0

servers:
  - url: http://localhost/miApi.php

paths:
  /recursos:
    get:
      summary: Listar
      parameters:
        - name: filtro # query string ?filtro=valor
          in: query
          required: false
          schema:
            type: string
      responses:
        "200":
          description: Lista correcta
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/Recurso"

    post:
      summary: Crear
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: "#/components/schemas/RecursoEntrada"
      responses:
        "201":
          description: Creado

  /recursos/{id}:
    get:
      summary: Consultar uno
      parameters:
        - name: id
          in: path # parámetro en la URL /recursos/1
          required: true
          schema:
            type: integer
      responses:
        "200":
          description: Encontrado
        "404":
          description: No encontrado

    patch:
      summary: Modificar parcialmente
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: "#/components/schemas/RecursoParcial"
      responses:
        "200":
          description: Actualizado

    delete:
      summary: Eliminar
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        "204":
          description: Eliminado sin body
        "409":
          description: Conflicto con datos relacionados

components:
  schemas:
    Recurso: # lo que devuelve el servidor
      type: object
      properties:
        id:
          type: integer
        nombre:
          type: string

    RecursoEntrada: # lo que manda el cliente al crear
      type: object
      required:
        - nombre
      properties:
        nombre:
          type: string

    RecursoParcial: # lo que manda el cliente al modificar (todo opcional)
      type: object
      properties:
        nombre:
          type: string

    Error: # reutilizable en cualquier respuesta de error
      type: object
      properties:
        error:
          type: string
          example: No encontrado
```

---

## 🧠 Resumen mental para el examen

| Pregunta                               | Respuesta                                             |
| -------------------------------------- | ----------------------------------------------------- |
| ¿Cómo detecta PHP el método?           | `$_SERVER["REQUEST_METHOD"]`                          |
| ¿Cómo detecta PHP la ruta?             | `$_SERVER["PATH_INFO"]` o `$_GET["ruta"]`             |
| ¿Cómo leo el JSON del cliente?         | `leerJSONBody()` → `file_get_contents("php://input")` |
| ¿Cómo creo un ID sin MySQL?            | `max(array_column($datos, "id")) + 1`                 |
| ¿Cómo filtro un array en PHP?          | `array_values(array_filter($datos, fn($x) => ...))`   |
| ¿Cómo construyo el PATCH dinámico?     | Array `$campos[]` + `implode(", ", $campos)`          |
| ¿Qué devuelve el DELETE correcto?      | `204` sin body                                        |
| ¿Qué pasa si no puedo borrar por FK?   | `PDOException` → `responder(409, ...)`                |
| ¿Para qué sirve el YAML?               | Documenta la API, no ejecuta nada                     |
| ¿Diferencia `in: path` vs `in: query`? | `path` = `/recursos/1`, `query` = `?filtro=valor`     |
