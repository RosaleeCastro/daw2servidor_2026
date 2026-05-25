# Guia de examen - Programacion de servicios web

Este README es el indice rapido de todo el tema `007-Programacion_Servicios_web`.

Sirve para encontrar rapido:

- que hace cada carpeta,
- que tecnologia usa,
- que archivo contiene cada patron reutilizable,
- que funciones puedes copiar en otros ejercicios,
- que estructura usar si el examen pide SOAP, REST, JSON, MySQL, YAML u OpenAPI.

## Mapa rapido

| Carpeta | Tema | Estructura | Herramientas |
| --- | --- | --- | --- |
| `001-Tecnologias_protocolos_implicaciones` | Servicios PHP + JSON + MySQL | HTML -> fetch -> PHP -> MySQL -> JSON | HTML, JS, PHP, PDO, MySQL |
| `002-Estandares_Arquitectura_actuales_formato_intercambio_Datos` | SOAP calculadora | SOAP XML + WSDL | XML, SOAP, WSDL, PHP |
| `003-Generacion_de _un_servicio_web` | SOAP con cabecera de sesion | SOAP Header + Body | XML, SOAP, WSDL, PHP |
| `004-Interface_servicio_web` | Interfaces SOAP | WSDL + PHP + cliente | XML, SOAP, WSDL |
| `005-Consumo_Servicio_web_Herramientas_Prueba` | APIs REST y OpenAPI | REST + JSON + YAML | PHP, JS, JSON, OpenAPI |

## Si el examen pide REST

Usa esta estructura mental:

```text
HTML cliente
  -> fetch()
  -> api.php/recurso
  -> PHP detecta metodo y ruta
  -> PHP consulta datos
  -> PHP responde JSON
```

Metodos:

```text
GET    consultar
POST   crear
PATCH  modificar parcialmente
DELETE eliminar
```

Archivos donde tienes ejemplos:

- JSON local: `005-.../api_libros/apiRestLibros.php`
- JSON local con filtros: `005-.../gestorTareas_api/apiTareas.php`
- MySQL: `005-.../api_videojuegos/apiVideojuegos.php`
- MySQL con PATCH dinamico: `005-.../apiEstudios/apiEstudios.php`

## Si el examen pide SOAP

Usa esta estructura mental:

```text
HTML cliente
  -> crea XML SOAP
  -> fetch() con Content-Type text/xml
  -> PHP lee php://input
  -> DOMDocument carga XML
  -> DOMXPath busca Header y Body
  -> PHP responde XML SOAP
```

Estructura SOAP:

```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
    <!-- datos de sesion, requestId, clienteInfo... -->
  </soap:Header>
  <soap:Body>
    <!-- operacion real -->
  </soap:Body>
</soap:Envelope>
```

Archivos donde tienes ejemplos:

- SOAP simple: `002-.../calculadora-soap.php`
- SOAP con cabecera de sesion: `003-.../prestamos.php`
- SOAP con requestId: `004-.../envioPostal.php`
- SOAP con validaciones: `004-.../acceso_examen/accesoExamen.php`

## Si el examen pide YAML/OpenAPI

Recuerda:

```text
El YAML no ejecuta nada.
El YAML documenta la API REST.
```

Sirve para describir:

- rutas,
- metodos HTTP,
- parametros,
- body JSON,
- respuestas,
- schemas reutilizables.

Archivos de ejemplo:

- `005-.../api_libros/openApiLibros.yaml`
- `005-.../api_videojuegos/openApiVideojuegos.yaml`
- `005-.../apiEstudios/openApiEstudio.yaml`
- `005-.../gestorTareas_api/openApiTareas.yaml`

## Funciones reutilizables PHP

### 1. Conexion PDO a MySQL

Donde esta:

- `001-.../conexion_mysql.php`
- `001-.../ejercicio_videojuegos/conexion_videojuegos.php`
- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`

Para que sirve:

Abrir conexion segura con MySQL usando PDO.

Plantilla:

```php
function obtenerPDO() {
    $host = "127.0.0.1";
    $port = "3306";
    $dbname = "nombre_base";
    $user = "root";
    $pass = "";

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
```

Que cambiar:

```text
$port
$dbname
$user
$pass
```

### 2. Responder JSON con codigo HTTP

Donde esta:

- `005-.../api_libros/apiRestLibros.php`
- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Devolver una respuesta uniforme y terminar la ejecucion.

```php
function responder($codigo, $datos = null) {
    http_response_code($codigo);

    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    exit;
}
```

Codigos utiles:

```text
200 OK
201 Created
204 No Content
400 Bad Request
404 Not Found
405 Method Not Allowed
409 Conflict
500 Internal Server Error
```

### 3. Leer body JSON

Donde esta:

- `005-.../api_libros/apiRestLibros.php`
- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Leer datos enviados por `POST` o `PATCH`.

```php
function leerJSONBody() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if ($raw !== "" && $data === null) {
        responder(400, ["error" => "JSON invalido"]);
    }

    return $data ?? [];
}
```

### 4. Leer y guardar datos en archivo JSON

Donde esta:

- `005-.../api_libros/apiRestLibros.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Crear APIs sin MySQL.

```php
function leerDatos($archivoDatos) {
    return json_decode(file_get_contents($archivoDatos), true) ?? [];
}

function guardarDatos($archivoDatos, $datos) {
    file_put_contents(
        $archivoDatos,
        json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}
```

### 5. Obtener ruta REST

Donde esta:

- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Separar `/tareas/1` en recurso `tareas` e id `1`.

```php
$metodo = $_SERVER["REQUEST_METHOD"];
$ruta = $_SERVER["PATH_INFO"] ?? ($_GET["ruta"] ?? "");
$partesRuta = explode("/", trim($ruta, "/"));

$recurso = $partesRuta[0] ?? "";
$id = $partesRuta[1] ?? null;
```

### 6. Buscar por ID en MySQL

Donde esta:

- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`

Para que sirve:

Evitar repetir el mismo `SELECT` en `GET`, `POST` y `PATCH`.

```php
function buscarPorId($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM tabla WHERE id = :id");
    $stmt->execute([":id" => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

### 7. PATCH dinamico

Donde esta:

- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`

Para que sirve:

Modificar solo los campos enviados.

```php
$campos = [];
$params = [":id" => $id];

if (isset($data["nombre"])) {
    $campos[] = "nombre = :nombre";
    $params[":nombre"] = trim($data["nombre"]);
}

$sql = "UPDATE tabla SET " . implode(", ", $campos) . " WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

### 8. Transaccion para operaciones relacionadas

Donde esta:

- `001-.../servicio_pedidos.php`
- `001-.../ejercicio_videojuegos/servicio_disponibilidad.php`

Para que sirve:

Si una operacion modifica varias cosas, o se hacen todas o ninguna.

```php
$pdo->beginTransaction();

try {
    // INSERT
    // UPDATE
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
}
```

### 9. Bloqueo de filas con FOR UPDATE

Donde esta:

- `001-.../servicio_pedidos.php`
- `001-.../ejercicio_videojuegos/servicio_disponibilidad.php`

Para que sirve:

Evitar vender/restar el mismo stock dos veces si llegan peticiones simultaneas.

```sql
SELECT ...
FROM stock
WHERE id_producto = :id
FOR UPDATE
```

### 10. Responder SOAP

Donde esta:

- `002-.../calculadora-soap.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Crear una respuesta XML SOAP correcta.

```php
function responderSOAP($operacion, $resultado) {
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<' . $operacion . 'Response>'
       . '<resultado>' . $resultado . '</resultado>'
       . '</' . $operacion . 'Response>'
       . '</soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

### 11. Responder error SOAP

Donde esta:

- `002-.../calculadora-soap.php`
- `003-.../prestamos.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Responder errores SOAP formales.

```php
function responderFault($mensaje) {
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");

    echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<soap:Fault>'
       . '<faultcode>SOAP-ENV:Client</faultcode>'
       . '<faultstring>' . $mensajeSeguro . '</faultstring>'
       . '</soap:Fault>'
       . '</soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

### 12. Leer XML SOAP

Donde esta:

- `002-.../calculadora-soap.php`
- `003-.../prestamos.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Leer una peticion SOAP recibida por POST.

```php
$xmlRecibido = file_get_contents("php://input");

$dom = new DOMDocument();
$dom->loadXML($xmlRecibido);

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

$body = $xpath->query("//soap:Body")->item(0);
```

### 13. Leer SOAP Header

Donde esta:

- `003-.../prestamos.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Leer token, requestId o informacion de cliente.

```php
$header = $xpath->query("//soap:Header")->item(0);
$requestIdNode = $header->getElementsByTagName("requestId")->item(0);
$requestId = $requestIdNode ? trim($requestIdNode->textContent) : "";
```

## Funciones reutilizables JavaScript

### 1. fetch GET JSON

Donde esta:

- todos los clientes REST HTML del bloque 005.

```js
const resp = await fetch(url, {
  method: "GET",
  headers: {
    Accept: "application/json",
  },
});
```

### 2. fetch POST/PATCH JSON

```js
const resp = await fetch(url, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify(body),
});
```

### 3. Mostrar peticion

Donde esta:

- `005-.../api_libros/apiRestLibros.html`
- `005-.../api_videojuegos/apiVideoJuegos.html`
- `005-.../apiEstudios/apiEstudios.html`
- `005-.../gestorTareas_api/gestorTareas.html`

```js
function mostrarPeticion(metodo, url, body = null) {
  let texto = metodo + " " + url;

  if (body !== null) {
    texto += "\n\nBody:\n" + JSON.stringify(body, null, 2);
  }

  peticion.innerText = texto;
}
```

### 4. Mostrar respuesta

```js
async function mostrarRespuesta(resp) {
  const texto = await resp.text();

  respuesta.innerText =
    "Codigo HTTP: " +
    resp.status +
    "\n\n" +
    (texto || "Sin cuerpo de respuesta");
}
```

### 5. Construir URL compatible con XAMPP

Donde esta:

- `005-.../gestorTareas_api/gestorTareas.html`

```js
function construirUrl(ruta, parametros = null) {
  let url = baseUrl + "?ruta=" + encodeURIComponent(ruta);

  if (parametros !== null && parametros.toString() !== "") {
    url += "&" + parametros.toString();
  }

  return url;
}
```

### 6. Crear query string

Donde esta:

- `005-.../api_videojuegos/apiVideoJuegos.html`
- `005-.../apiEstudios/apiEstudios.html`
- `005-.../gestorTareas_api/gestorTareas.html`

```js
const parametros = new URLSearchParams();

if (precioMax !== "") {
  parametros.append("precioMax", precioMax);
}

if (parametros.toString() !== "") {
  url += "?" + parametros.toString();
}
```

### 7. fetch SOAP

Donde esta:

- `002-.../calculadora-soap.html`
- `003-.../prestamos.html`
- `004-.../envioPostal.html`
- `004-.../acceso_examen/accesoExamen.html`

```js
const respuesta = await fetch("servicio.php", {
  method: "POST",
  headers: {
    "Content-Type": "text/xml; charset=utf-8",
    Accept: "text/xml",
  },
  body: xmlSOAP,
});
```

### 8. Interpretar XML en navegador

```js
const parser = new DOMParser();
const xml = parser.parseFromString(textoRespuesta, "text/xml");
const nodoResultado = xml.getElementsByTagName("resultado")[0];
```

## Plantillas de examen

### API REST minima con JSON

```php
<?php
header("Content-Type: application/json; charset=utf-8");

function responder($codigo, $datos = null) {
    http_response_code($codigo);
    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$metodo = $_SERVER["REQUEST_METHOD"];
$ruta = $_SERVER["PATH_INFO"] ?? "";
$partes = explode("/", trim($ruta, "/"));
$recurso = $partes[0] ?? "";
$id = $partes[1] ?? null;

if ($recurso !== "recurso") {
    responder(404, ["error" => "Recurso no encontrado"]);
}

if ($metodo === "GET" && $id === null) {
    responder(200, ["datos" => []]);
}

responder(405, ["error" => "Metodo no permitido"]);
?>
```

### Servicio SOAP minimo

```php
<?php
header("Content-Type: text/xml; charset=utf-8");

$xmlRecibido = file_get_contents("php://input");
$dom = new DOMDocument();
$dom->loadXML($xmlRecibido);

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");
$body = $xpath->query("//soap:Body")->item(0);

echo '<?xml version="1.0" encoding="UTF-8"?>'
   . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
   . '<soap:Body>'
   . '<respuesta><mensaje>OK</mensaje></respuesta>'
   . '</soap:Body>'
   . '</soap:Envelope>';
?>
```

## Como decidir que usar

| Enunciado dice | Usa |
| --- | --- |
| "servicio SOAP" | XML + Envelope + Body + WSDL |
| "cabecera de sesion" | SOAP Header |
| "API REST" | metodos HTTP + rutas + JSON |
| "documenta la API" | OpenAPI YAML |
| "consume desde cliente" | HTML + fetch |
| "base MySQL" | PDO + prepare/execute |
| "sin base de datos" | archivo JSON |

