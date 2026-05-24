# Tienda online separada por servicios

Este ejercicio muestra una mini tienda hecha con una separación clara entre:

- `tienda.html`: cliente web, interfaz y llamadas `fetch()`.
- Servicios PHP: reciben peticiones, validan datos, hablan con MySQL y devuelven JSON.
- MySQL: guarda productos, stock y pedidos.

La idea importante es que el navegador no toca la base de datos directamente. El navegador habla con servicios PHP, y los servicios PHP hablan con MySQL.

## Archivos de la carpeta

| Archivo | Función principal | Se conecta con |
| --- | --- | --- |
| `tienda.html` | Interfaz de usuario de la tienda | `servicio_productos.php`, `servicio_stock.php`, `servicio_pedidos.php` |
| `conexion_mysql.php` | Función reutilizable para abrir conexión PDO | MySQL, base `tienda_servicios` |
| `servicio_productos.php` | Devuelve el catálogo de productos en JSON | `conexion_mysql.php`, tabla `producto` |
| `servicio_stock.php` | Recibe un producto y devuelve su stock | `conexion_mysql.php`, tablas `producto` y `stock` |
| `servicio_pedidos.php` | Crea un pedido y descuenta stock | `conexion_mysql.php`, tablas `producto`, `stock` y `pedido` |
| `test_conexion.php` | Comprueba si la conexión a MySQL funciona | `conexion_mysql.php` |
| `tienda_servicio.sql` | Crea la base de datos, tablas y datos de prueba | MySQL/phpMyAdmin |
| `servicioVideojuegos.txt` | Enunciado o idea para otro ejercicio de servicios | No se ejecuta |

## Flujo general de la aplicación

1. El usuario abre `tienda.html`.
2. Pulsa `Cargar productos`.
3. JavaScript llama a `servicio_productos.php`.
4. PHP consulta la tabla `producto`.
5. PHP devuelve JSON.
6. JavaScript rellena el `<select>` con los productos.
7. El usuario puede consultar stock.
8. JavaScript llama a `servicio_stock.php` enviando `id_producto`.
9. PHP consulta las tablas `producto` y `stock`.
10. El usuario puede crear un pedido.
11. JavaScript llama a `servicio_pedidos.php` enviando cliente, producto y cantidad.
12. PHP valida stock, inserta el pedido y descuenta unidades.

## Base de datos

La base se llama:

```sql
tienda_servicios
```

Tiene tres tablas:

### `producto`

Guarda el catálogo.

```sql
id_producto INT AUTO_INCREMENT PRIMARY KEY
nombre VARCHAR(100)
precio DECIMAL(10,2)
```

Ejemplo:

```text
1 - Teclado - 25.50
2 - Ratón - 14.95
```

### `stock`

Guarda las unidades disponibles de cada producto.

```sql
id_producto INT PRIMARY KEY
unidades INT
```

`id_producto` también es clave foránea hacia `producto`.

### `pedido`

Guarda las compras realizadas.

```sql
id_pedido INT AUTO_INCREMENT PRIMARY KEY
cliente VARCHAR(100)
id_producto INT
cantidad INT
precio_unitario DECIMAL(10,2)
total DECIMAL(10,2)
fecha DATETIME
```

## Cómo preparar el ejercicio

1. Abrir XAMPP.
2. Arrancar Apache.
3. Arrancar MySQL.
4. Comprobar que MySQL usa el puerto `3307`.
5. Abrir phpMyAdmin.
6. Importar o ejecutar `tienda_servicio.sql`.
7. Probar la conexión desde:

```text
http://localhost/daw2servidor_RCT/daw2servidor_2026/007-Programacion_Servicios_web/001-Tecnologias_protocolos_implicaciones/test_conexion.php
```

8. Abrir la tienda:

```text
http://localhost/daw2servidor_RCT/daw2servidor_2026/007-Programacion_Servicios_web/001-Tecnologias_protocolos_implicaciones/tienda.html
```

## `conexion_mysql.php`

Este archivo contiene la función más reutilizable del ejercicio:

```php
function obtenerPDO(){
  ...
  return $pdo;
}
```

Sirve para no repetir la conexión en todos los servicios.

Cada servicio puede usarla así:

```php
require_once "conexion_mysql.php";

$pdo = obtenerPDO();
```

Si haces otro ejercicio, normalmente solo tendrás que cambiar:

```php
$host   = "127.0.0.1";
$port   = "3307";
$dbname = "nombre_de_tu_base";
$user   = "root";
$pass   = "";
```

## Patrón reutilizable para servicios que devuelven JSON

Todos los servicios PHP siguen esta idea:

```php
<?php
header("Content-Type: application/json; charset=utf-8");

require_once "conexion_mysql.php";

try {
    $pdo = obtenerPDO();

    // Consulta o modificación de base de datos.

    echo json_encode([
        "ok" => true,
        "datos" => []
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error en el servicio",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
```

Este patrón se puede copiar para crear nuevos servicios.

## Patrón reutilizable para leer JSON enviado por `fetch()`

Cuando JavaScript envía datos con `POST`, PHP los lee así:

```php
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$idProducto = $data["id_producto"] ?? null;
```

Después conviene validar:

```php
if (!$idProducto || !is_numeric($idProducto)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Dato no válido"
    ], JSON_PRETTY_PRINT);
    exit;
}
```

## Patrón reutilizable de `fetch()` desde JavaScript

Para consultar un servicio sin enviar datos:

```js
const respuesta = await fetch("servicio_productos.php");
const data = await respuesta.json();
```

Para enviar datos JSON:

```js
const respuesta = await fetch("servicio_stock.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify({
    id_producto: 1,
  }),
});

const data = await respuesta.json();
```

## Servicios de esta tienda

### `servicio_productos.php`

Método usado:

```text
GET
```

Entrada:

```text
No necesita datos.
```

Consulta:

```sql
SELECT id_producto, nombre, precio
FROM producto
ORDER BY nombre ASC
```

Respuesta correcta:

```json
{
  "ok": true,
  "productos": [
    {
      "id_producto": 1,
      "nombre": "Teclado",
      "precio": "25.50"
    }
  ]
}
```

Uso típico:

```js
fetch("servicio_productos.php")
```

### `servicio_stock.php`

Método usado:

```text
POST
```

Entrada:

```json
{
  "id_producto": 1
}
```

Consulta:

```sql
SELECT p.id_producto, p.nombre, s.unidades
FROM producto p
INNER JOIN stock s ON p.id_producto = s.id_producto
WHERE p.id_producto = :id_producto
```

Respuesta correcta:

```json
{
  "ok": true,
  "producto": "Teclado",
  "stock": 10
}
```

Errores posibles:

- `400`: no se ha enviado un `id_producto` válido.
- `404`: el producto no existe.
- `500`: error de base de datos.

### `servicio_pedidos.php`

Método usado:

```text
POST
```

Entrada:

```json
{
  "cliente": "Ana",
  "id_producto": 1,
  "cantidad": 2
}
```

Qué hace:

1. Valida que los datos existan.
2. Valida que `id_producto` y `cantidad` sean numéricos.
3. Abre una transacción.
4. Consulta producto, precio y stock.
5. Bloquea la fila con `FOR UPDATE`.
6. Comprueba si hay stock suficiente.
7. Inserta el pedido.
8. Descuenta stock.
9. Confirma la transacción.
10. Devuelve el resumen del pedido.

Respuesta correcta:

```json
{
  "ok": true,
  "mensaje": "Pedido realizado correctamente",
  "id_pedido": "1",
  "producto": "Teclado",
  "cliente": "Ana",
  "cantidad": 2,
  "precio_unitario": 25.5,
  "total": 51,
  "stock_restante": 8
}
```

Errores posibles:

- `400`: faltan datos.
- `400`: cantidad no válida.
- `400`: no hay stock suficiente.
- `404`: producto no encontrado.
- `500`: error al crear el pedido.

## Por qué `servicio_pedidos.php` usa transacciones

Crear un pedido modifica dos cosas:

- Inserta una fila en `pedido`.
- Resta unidades en `stock`.

Si una operación se hiciera y la otra fallara, la base de datos quedaría incoherente.

Por eso se usa:

```php
$pdo->beginTransaction();
...
$pdo->commit();
```

Si algo falla:

```php
$pdo->rollBack();
```

## Por qué se usa `FOR UPDATE`

En `servicio_pedidos.php` aparece:

```sql
FOR UPDATE
```

Esto bloquea temporalmente la fila del producto mientras se crea el pedido.

Sirve para evitar este problema:

1. Queda 1 unidad de stock.
2. Dos usuarios compran a la vez.
3. Los dos ven que queda 1.
4. Los dos compran.
5. El stock acaba mal.

Con `FOR UPDATE`, una compra espera a que termine la otra.

## Códigos HTTP usados

| Código | Significado | Cuándo se usa |
| --- | --- | --- |
| `200` | Correcto | La operación salió bien |
| `400` | Petición incorrecta | Faltan datos o son inválidos |
| `404` | No encontrado | No existe el producto |
| `500` | Error interno | Falló MySQL o el servidor |

## Estructura recomendada para otros ejercicios

Para ejercicios similares, puedes reutilizar esta organización:

```text
mi_ejercicio/
├── conexion_mysql.php
├── test_conexion.php
├── index.html
├── servicio_listar.php
├── servicio_detalle.php
├── servicio_insertar.php
└── base_datos.sql
```

Ejemplo aplicado al enunciado de videojuegos:

```text
videojuegos/
├── conexion_mysql.php
├── test_conexion.php
├── videojuegos.html
├── servicio_videojuegos.php
├── servicio_disponibilidad.php
├── servicio_calculo_precio.php
└── videojuegos.sql
```

## Plantilla para un servicio de listado

```php
<?php
header("Content-Type: application/json; charset=utf-8");

require_once "conexion_mysql.php";

try {
    $pdo = obtenerPDO();

    $sql = "SELECT * FROM tabla ORDER BY nombre ASC";
    $stmt = $pdo->query($sql);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "ok" => true,
        "datos" => $filas
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener datos",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
```

## Plantilla para un servicio con filtro

```php
<?php
header("Content-Type: application/json; charset=utf-8");

require_once "conexion_mysql.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$precioMaximo = $data["precio_maximo"] ?? null;

if ($precioMaximo === null || !is_numeric($precioMaximo)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes enviar un precio válido"
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = obtenerPDO();

    $sql = "SELECT * FROM producto WHERE precio <= :precio_maximo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":precio_maximo" => (float)$precioMaximo
    ]);

    echo json_encode([
        "ok" => true,
        "datos" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al filtrar datos",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
```

## Plantilla para calcular importes en servidor

```php
$subtotal = $precio * $cantidad;
$importeDescuento = $subtotal * ($descuento / 100);
$baseImponible = $subtotal - $importeDescuento;
$importeIva = $baseImponible * ($iva / 100);
$total = $baseImponible + $importeIva;
```

Respuesta JSON recomendada:

```php
echo json_encode([
    "ok" => true,
    "subtotal" => $subtotal,
    "importe_descuento" => $importeDescuento,
    "base_imponible" => $baseImponible,
    "importe_iva" => $importeIva,
    "total" => $total
], JSON_PRETTY_PRINT);
```

## Consejos para estudiar este ejercicio

- Empieza por `tienda.html`, porque muestra qué botones existen.
- Sigue cada botón hasta su función JavaScript.
- Mira a qué servicio llama cada `fetch()`.
- Entra en ese servicio PHP.
- Busca qué tabla SQL consulta.
- Comprueba qué JSON devuelve.

Ese recorrido cliente -> servicio -> base de datos -> JSON -> cliente es la idea principal del tema.
