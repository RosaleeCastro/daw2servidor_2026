# 001 - Servicios Web: PHP + MySQL + JSON

## ⚡ Regla de oro (nunca la olvides)

```
HTML  →  fetch()  →  PHP  →  MySQL  →  JSON  →  HTML muestra
```

> HTML **nunca** consulta MySQL directamente. Siempre pasa por PHP.

---

## 🗂️ Archivos del proyecto

```
tienda.html              ← cliente web (fetch)
conexion_mysql.php       ← función obtenerPDO() reutilizable
servicio_productos.php   ← GET: lista todos los productos
servicio_stock.php       ← POST: consulta stock de uno
servicio_pedidos.php     ← POST: crea pedido + descuenta stock (transacción)
test_conexion.php        ← prueba rápida de conexión
tienda_servicio.sql      ← crea las tablas e inserta datos
ejercicio_videojuegos/   ← mismo patrón aplicado a otra BD
```

---

## 🔌 1. Conexión PDO (reutilizable en cualquier ejercicio)

```php
// conexion_mysql.php
function obtenerPDO() {
    $host   = "127.0.0.1";
    $port   = "3307";        // ← XAMPP suele usar 3306 o 3307
    $dbname = "tienda_servicios";
    $user   = "root";
    $pass   = "";

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user, $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
```

> **Qué cambiar en cada ejercicio:** `$port`, `$dbname`, `$user`, `$pass`

---

## 📦 2. Patrón base de cualquier servicio PHP

```php
<?php
// PASO 1 — Decirle al cliente que la respuesta es JSON
header("Content-Type: application/json; charset=utf-8");

require_once "conexion_mysql.php";

try {
    // PASO 2 — Conectar a MySQL
    $pdo = obtenerPDO();

    // PASO 3 — Consultar
    $stmt = $pdo->query("SELECT * FROM tabla ORDER BY id ASC");
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PASO 4 — Responder JSON con éxito
    echo json_encode(["ok" => true, "datos" => $datos], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // PASO 5 — Responder JSON con error
    http_response_code(500);
    echo json_encode([
        "ok"      => false,
        "mensaje" => "Error de base de datos",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
```

---

## 📤 3. Leer JSON que manda el cliente (fetch POST)

```php
// PHP — recibir el JSON del body
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);

$id = $data["id_producto"] ?? null;  // ?? null = valor por defecto si no llega

// Validar antes de consultar
if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "mensaje" => "ID no válido"]);
    exit;
}
```

```javascript
// JS — enviar JSON con fetch
const resp = await fetch("servicio_stock.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ id_producto: 1 }),
});
const data = await resp.json();
```

---

## 📋 4. Tres servicios, tres responsabilidades

| Servicio                 | Método                               | Qué hace                      | Clave técnica             |
| ------------------------ | ------------------------------------ | ----------------------------- | ------------------------- |
| `servicio_productos.php` | GET (sin body)                       | Lista todos los productos     | `query()` + `fetchAll()`  |
| `servicio_stock.php`     | POST con `id_producto`               | Consulta stock de uno         | `prepare()` + `execute()` |
| `servicio_pedidos.php`   | POST con cliente, producto, cantidad | Crea pedido y descuenta stock | Transacción               |

---

## 🔍 5. Consulta con parámetro (evita SQL injection)

```php
// servicio_stock.php — consulta preparada
$sql = "
    SELECT p.nombre, s.unidades
    FROM producto p
    INNER JOIN stock s ON p.id_producto = s.id_producto
    WHERE p.id_producto = :id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => (int)$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    http_response_code(404);
    echo json_encode(["ok" => false, "mensaje" => "Producto no encontrado"]);
    exit;
}

echo json_encode([
    "ok"      => true,
    "producto" => $producto["nombre"],
    "stock"    => (int)$producto["unidades"]
]);
```

> ⚠️ Usa siempre `prepare()` + `execute([":param" => $valor])`, nunca insertes variables en el SQL directamente.

---

## 🔄 6. Transacción (operaciones que van juntas o no van)

```php
// servicio_pedidos.php — insertar pedido Y descontar stock
$pdo->beginTransaction();

try {
    // Verificar stock con bloqueo de fila
    $stmt = $pdo->prepare("
        SELECT p.precio, s.unidades
        FROM producto p
        INNER JOIN stock s ON p.id_producto = s.id_producto
        WHERE p.id_producto = :id
        FOR UPDATE
    ");
    $stmt->execute([":id" => $idProducto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cantidad > $producto["unidades"]) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["ok" => false, "mensaje" => "Sin stock"]);
        exit;
    }

    // Insertar pedido
    $pdo->prepare("
        INSERT INTO pedido (cliente, id_producto, cantidad, total)
        VALUES (:cliente, :id, :cantidad, :total)
    ")->execute([
        ":cliente"  => $cliente,
        ":id"       => $idProducto,
        ":cantidad" => $cantidad,
        ":total"    => $producto["precio"] * $cantidad
    ]);

    // Descontar stock
    $pdo->prepare("
        UPDATE stock SET unidades = unidades - :cantidad
        WHERE id_producto = :id
    ")->execute([":cantidad" => $cantidad, ":id" => $idProducto]);

    $pdo->commit();

    echo json_encode(["ok" => true, "mensaje" => "Pedido creado"]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["ok" => false, "mensaje" => $e->getMessage()]);
}
```

> **`FOR UPDATE`** bloquea la fila mientras dura la transacción → evita que dos usuarios compren el mismo stock a la vez.

---

## 🌐 7. Cliente HTML completo (fetch)

```javascript
// GET — solo leer, sin body
async function cargarProductos() {
  const resp = await fetch("servicio_productos.php");
  const data = await resp.json();

  if (!data.ok) {
    document.getElementById("resultado").innerText = data.mensaje;
    return;
  }

  // Rellenar un <select>
  data.productos.forEach((p) => {
    const opt = document.createElement("option");
    opt.value = p.id_producto;
    opt.textContent = p.nombre + " - €" + p.precio;
    document.getElementById("select").appendChild(opt);
  });
}

// POST — enviar datos
async function consultarStock() {
  const id = document.getElementById("select").value;

  const resp = await fetch("servicio_stock.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id_producto: parseInt(id) }),
  });

  const data = await resp.json();
  document.getElementById("resultado").innerText = data.ok
    ? "Stock: " + data.stock
    : "Error: " + data.mensaje;
}
```

---

## 🗄️ 8. SQL mínimo para crear el entorno

```sql
CREATE DATABASE tienda_servicios DEFAULT CHARACTER SET utf8mb4;
USE tienda_servicios;

CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    precio      DECIMAL(10,2) NOT NULL
);

CREATE TABLE stock (
    id_producto INT PRIMARY KEY,
    unidades    INT NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);

CREATE TABLE pedido (
    id_pedido       INT AUTO_INCREMENT PRIMARY KEY,
    cliente         VARCHAR(100) NOT NULL,
    id_producto     INT NOT NULL,
    cantidad        INT NOT NULL,
    total           DECIMAL(10,2) NOT NULL,
    fecha           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);

INSERT INTO producto (nombre, precio) VALUES ('Teclado', 25.50), ('Ratón', 14.95);
INSERT INTO stock (id_producto, unidades) VALUES (1, 10), (2, 25);
```

---

## ✅ Respuestas JSON estándar

```php
// Éxito
echo json_encode(["ok" => true,  "datos"   => $datos]);

// Error validación
http_response_code(400);
echo json_encode(["ok" => false, "mensaje" => "Datos no válidos"]); exit;

// Error servidor
http_response_code(500);
echo json_encode(["ok" => false, "mensaje" => "Error BD", "detalle" => $e->getMessage()]); exit;

// No encontrado
http_response_code(404);
echo json_encode(["ok" => false, "mensaje" => "No encontrado"]); exit;
```

---

## 🧠 Resumen mental para el examen

| Pregunta                           | Respuesta                                                 |
| ---------------------------------- | --------------------------------------------------------- |
| ¿Cómo lee PHP el JSON del cliente? | `json_decode(file_get_contents("php://input"), true)`     |
| ¿Cómo evitas SQL injection?        | `prepare()` + `execute([":param" => $valor])`             |
| ¿Cuándo usas transacción?          | Cuando dos operaciones deben ir juntas (INSERT + UPDATE)  |
| ¿Qué hace `FOR UPDATE`?            | Bloquea la fila para evitar concurrencia                  |
| ¿Qué devuelve siempre PHP?         | JSON con `"ok": true/false`                               |
| ¿Qué cabecera pone PHP?            | `header("Content-Type: application/json; charset=utf-8")` |
