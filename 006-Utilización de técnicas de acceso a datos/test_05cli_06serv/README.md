# 🌐 Cliente ↔ PHP ↔ MongoDB/MySQL — Guía de referencia
`fetch()` · JSON · MongoDB · PDO/MySQL

Práctica típica de examen: el cliente valida y envía, PHP procesa, MongoDB busca y MySQL guarda favoritos.

## 📁 Archivos de esta carpeta

| Archivo | Qué contiene | Cuándo consultarlo |
|---|---|---|
| `app_videojuegos.html` | Cliente con formulario, validación, `fetch()`, timeout y renderizado | Siempre que necesites la parte navegador |
| `buscar_videojuegos_mongo.php` | PHP que recibe JSON, consulta MongoDB y devuelve JSON | Cuando quieras buscar datos en Mongo |
| `favoritos_mysql.php` | PHP que guarda, lista y elimina favoritos en MySQL | Cuando necesites CRUD simple con PDO |
| `datos.json` | Datos de apoyo / ejemplo de estructura | Cuando quieras ver qué campos maneja la app |

## 🗺️ Mapa mental — qué hace cada parte

```text
¿Qué necesito hacer?
|
|-- Validar lo que escribe el usuario
|   `-- Cliente: app_videojuegos.html
|
|-- Buscar videojuegos con filtros
|   `-- fetch() -> buscar_videojuegos_mongo.php -> MongoDB
|
|-- Guardar un resultado como favorito
|   `-- fetch() -> favoritos_mysql.php -> MySQL INSERT
|
|-- Ver favoritos guardados
|   `-- fetch() -> favoritos_mysql.php -> MySQL SELECT
|
`-- Eliminar un favorito
    `-- fetch() -> favoritos_mysql.php -> MySQL DELETE
```

## 📡 Los patrones que se reutilizan

### 1️⃣ Cliente JS → PHP con JSON
📄 Ver: `app_videojuegos.html`

JavaScript envía un objeto en JSON:

```js
const resp = await fetch("buscar_videojuegos_mongo.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify({
    precio_max: parseFloat(precio),
    anio_min: parseInt(anio),
  }),
});

const data = await resp.json();
```

PHP lo recibe leyendo el body:

```php
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
```

✅ Úsalo para: enviar varios datos, filtros, objetos completos y trabajar tipo API.

### 2️⃣ PHP → MongoDB → respuesta JSON
📄 Ver: `buscar_videojuegos_mongo.php`

PHP conecta con Mongo, aplica filtro y devuelve JSON:

```php
require 'vendor/autoload.php';
use MongoDB\Client;

$client = new Client("mongodb://localhost:27017");
$db = $client->Videojuegos;
$collection = $db->JuegosBase;

$filtro = [
    "precio_base" => ['$lte' => $precioMax],
    "fecha_lanzamiento" => ['$gt' => $fechaMin]
];

$cursor = $collection->find($filtro);
```

Respuesta:

```php
echo json_encode([
    "ok" => true,
    "total" => count($juegos),
    "juegos" => $juegos
], JSON_PRETTY_PRINT);
```

✅ Úsalo para: búsquedas, filtros y lectura de colecciones MongoDB.

### 3️⃣ PHP → MySQL con PDO
📄 Ver: `favoritos_mysql.php`

Conexión base reutilizable:

```php
$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

Idea del archivo:

- `guardar_favorito` → `INSERT`
- `listar_favoritos` → `SELECT`
- `eliminar_favorito` → `DELETE`

✅ Úsalo para: favoritos, carrito, listas guardadas y CRUD simple.

### 4️⃣ Timeout con `AbortController`
📄 Ver: `app_videojuegos.html`

```js
const controller = new AbortController();
const temporizador = setTimeout(() => controller.abort(), TIMEOUT_MS);

const resp = await fetch("favoritos_mysql.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify({ accion: "listar_favoritos" }),
  signal: controller.signal,
});

clearTimeout(temporizador);
```

✅ Úsalo para: evitar que la interfaz se quede esperando eternamente.

## 🔁 Flujo cliente ↔ servidor

```text
Cliente: app_videojuegos.html          Servidor: PHP + MongoDB/MySQL

1. El usuario rellena el formulario
2. JavaScript valida los datos
3. JS hace fetch() con JSON
   ------------------------------>
4. PHP recibe y procesa el JSON
5. PHP consulta MongoDB o MySQL
6. PHP devuelve una respuesta JSON
   <------------------------------
7. JavaScript recibe la respuesta
8. La página pinta resultados o favoritos
```

## ⚠️ Reglas que no conviene olvidar

| Regla | Por qué |
|---|---|
| Con JSON en PHP usa `file_get_contents("php://input")` | Los datos no llegan como formulario normal |
| Si devuelves JSON, pon `Content-Type: application/json` | El cliente podrá usar `response.json()` |
| En MySQL usa PDO y consultas preparadas | Evita errores y mejora seguridad |
| En cliente valida antes de enviar | Evita peticiones inútiles |
| Usa `AbortController` si piden timeout | Es un patrón muy reutilizable en examen |

## 🔁 Función reutilizable — enviar JSON desde JS

```js
async function enviarJSON(url, datos) {
  const resp = await fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(datos),
  });

  return await resp.json();
}
```

## 🔁 Funciones reutilizables — leer y responder JSON en PHP

```php
function leerJSON() {
    $raw = file_get_contents("php://input");
    return json_decode($raw, true) ?? [];
}

function responderJSON($datos) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($datos, JSON_PRETTY_PRINT);
    exit;
}
```

## 🧠 Resumen rápido para decirlo en examen

El cliente está en HTML y JavaScript: valida, hace `fetch()` y actualiza la página.
PHP actúa como servidor intermedio: recibe JSON, consulta la base de datos y devuelve JSON.
MongoDB se usa para buscar videojuegos y MySQL para guardar favoritos.
