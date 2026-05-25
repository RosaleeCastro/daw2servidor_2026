# 001 - Tecnologias, protocolos e implicaciones

## Que contiene

Esta carpeta introduce servicios web sencillos con:

- Cliente HTML con `fetch()`.
- Servicios PHP que devuelven JSON.
- Conexion a MySQL con PDO.
- Operaciones separadas por responsabilidad: catalogo, stock y pedidos.

## Herramientas usadas

- HTML
- JavaScript `fetch()`
- PHP
- PDO
- MySQL
- JSON

## Estructura principal

```text
tienda.html
conexion_mysql.php
servicio_productos.php
servicio_stock.php
servicio_pedidos.php
test_conexion.php
tienda_servicio.sql
ejercicio_videojuegos/
```

## Flujo de la tienda

```text
tienda.html
  -> fetch()
  -> servicio PHP
  -> conexion_mysql.php
  -> MySQL
  -> JSON
  -> HTML muestra respuesta
```

## Funciones reutilizables

### Conexion PDO

Sirve para reutilizar conexion en cualquier ejercicio con MySQL.

```php
function obtenerPDO(){
  $host = "127.0.0.1";
  $port = "3307";
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

### Leer JSON enviado por fetch

```php
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
```

### Respuesta JSON correcta

```php
echo json_encode([
  "ok" => true,
  "datos" => $datos
], JSON_PRETTY_PRINT);
```

### Error JSON

```php
http_response_code(400);
echo json_encode([
  "ok" => false,
  "mensaje" => "Datos no validos"
], JSON_PRETTY_PRINT);
exit;
```

## Patron de servicio reutilizable

```php
<?php
header("Content-Type: application/json; charset=utf-8");
require_once "conexion_mysql.php";

try {
    $pdo = obtenerPDO();

    $stmt = $pdo->query("SELECT * FROM tabla");
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "ok" => true,
        "datos" => $datos
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error de base de datos",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
```

## Para examen

Recuerda esta idea:

```text
HTML nunca consulta MySQL directamente.
HTML llama a PHP.
PHP consulta MySQL.
PHP devuelve JSON.
```
