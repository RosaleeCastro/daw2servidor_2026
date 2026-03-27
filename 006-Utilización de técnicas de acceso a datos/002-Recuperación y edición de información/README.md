# 002 - Recuperación y edición de información

Este bloque contiene ejemplos reutilizables para el examen de servidor, centrados en acceso a datos con PHP, PDO, consultas SQL seguras y consumo de una API desde HTML con JavaScript.

La carpeta útil es:

- `002-Ejercicios/Select.php`
- `002-Ejercicios/QueryVSPrepare.php`
- `002-Ejercicios/videojuegos_api.php`
- `002-Ejercicios/filtroVideojuegos.html`

## Qué funcionalidades hay

### 1. Conexión a MySQL con PDO

Se usa en todos los `.php`.

Sirve para:

- conectarse a MySQL desde PHP
- configurar errores con excepciones
- ejecutar consultas
- preparar sentencias seguras

Patrón reutilizable:

```php
$host = "127.0.0.1";
$port = "3307";
$dbname = "videojuegos_asir";
$user = "root";
$pass = "";

$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

Reutilizable en examen:

- cualquier ejercicio con MySQL
- listados
- filtros
- inserciones, actualizaciones y borrados
- APIs en PHP

### 2. `SELECT` simple de todos los registros

Archivo: `002-Ejercicios/Select.php`

Qué hace:

- conecta a la BD
- ejecuta `SELECT * FROM videojuego`
- recorre los resultados con `fetchAll(PDO::FETCH_ASSOC)`
- los muestra por pantalla

Patrón clave:

```php
$sql = "SELECT * FROM videojuego";
$stmt = $pdo->query($sql);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

Reutilizable en examen:

- mostrar listados completos
- comprobar si la conexión funciona
- hacer una primera prueba rápida de una tabla

### 3. Comparación entre `query()` y `prepare()`

Archivo: `002-Ejercicios/QueryVSPrepare.php`

Qué hace:

- recibe un `id` por `GET`
- construye una consulta insegura con `query()`
- construye una consulta segura con `prepare()`
- muestra la diferencia frente a inyección SQL

Idea principal:

```php
$sql = "SELECT * FROM videojuego WHERE id_videojuego = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
```

Reutilizable en examen:

- búsquedas por `id`
- filtros por campos enviados por formulario
- cualquier caso donde entren datos del usuario

Muy importante:

- `query()` solo conviene cuando la consulta no lleva datos externos
- `prepare()` es la opción correcta cuando llega información por `GET`, `POST`, JSON o formularios

### 4. API PHP que responde JSON

Archivo: `002-Ejercicios/videojuegos_api.php`

Qué hace:

- recibe JSON en el body
- lo convierte con `json_decode`
- lee filtros de precio y fecha
- ejecuta una consulta segura con parámetros nombrados
- devuelve JSON con resultados

Patrones importantes:

```php
header("Content-Type: application/json; charset=utf-8");
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
```

```php
$sql = "
  SELECT id_videojuego, titulo, fecha_lanzamiento, precio_base
  FROM videojuego
  WHERE precio_base < :precio_max
    AND fecha_lanzamiento > :fecha_min
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':precio_max' => $precioMax,
  ':fecha_min'  => $fechaMin
]);
```

```php
echo json_encode([
  "ok" => true,
  "total" => count($juegos),
  "juegos" => $juegos
], JSON_PRETTY_PRINT);
```

Reutilizable en examen:

- crear una mini API REST o pseudo-REST
- devolver resultados en JSON
- recibir filtros desde `fetch()`
- separar frontend y backend

### 5. Cliente HTML + JavaScript con `fetch()`

Archivo: `002-Ejercicios/filtroVideojuegos.html`

Qué hace:

- recoge datos de inputs
- construye un JSON
- envía un `POST` con `fetch()`
- procesa la respuesta JSON
- muestra los resultados en pantalla

Patrón reutilizable:

```javascript
const resp = await fetch("videojuegos_api.php", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "Accept": "application/json"
  },
  body: JSON.stringify({
    precio_max: parseFloat(precio),
    fecha_min: fechaMin
  })
});

const data = await resp.json();
```

Reutilizable en examen:

- formularios con filtros
- conexión frontend-backend
- consumo de una API PHP
- mostrar resultados sin recargar la página

## Qué se puede reutilizar directamente

### Reutilización alta

Estas partes son casi plantilla:

- bloque de conexión PDO
- `prepare()` + `execute()`
- `fetchAll(PDO::FETCH_ASSOC)`
- `header("Content-Type: application/json; charset=utf-8")`
- `file_get_contents("php://input")`
- `json_decode(..., true)`
- `json_encode(...)`
- `fetch()` con `POST` y JSON

### Reutilización media

Estas partes sirven, pero adaptando nombres:

- nombres de tablas y columnas
- filtros por precio y fecha
- estructura del texto mostrado en pantalla
- validaciones concretas de campos

### Reutilización baja

Estas partes son más de ejemplo didáctico:

- comparación visual entre método inseguro y seguro
- salida HTML muy simple con `echo`

## Chuleta rápida para examen

### A. Listar datos de una tabla

```php
$stmt = $pdo->query("SELECT * FROM tabla");
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### B. Buscar por un dato recibido del usuario

```php
$sql = "SELECT * FROM tabla WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);
```

### C. Filtrar con varios parámetros

```php
$sql = "SELECT * FROM tabla WHERE precio < :precio AND fecha > :fecha";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':precio' => $precio,
    ':fecha' => $fecha
]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### D. Recibir JSON en PHP

```php
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
```

### E. Devolver JSON en PHP

```php
header("Content-Type: application/json; charset=utf-8");
echo json_encode($respuesta, JSON_PRETTY_PRINT);
```

### F. Consumir API con JavaScript

```javascript
const resp = await fetch("api.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(datos)
});

const data = await resp.json();
```

## Cómo aprovechar esta carpeta en el examen

Orden recomendado:

1. reutilizar primero la conexión PDO
2. montar la consulta con `prepare()`
3. decidir si la salida será HTML o JSON
4. si hay frontend, conectar con `fetch()`
5. probar siempre con un caso normal y un caso vacío

## Resumen práctico de cada archivo

### `Select.php`

- plantilla básica para conectar y listar

### `QueryVSPrepare.php`

- plantilla para preguntas teóricas o prácticas sobre seguridad e inyección SQL

### `videojuegos_api.php`

- plantilla de backend API con filtros y respuesta JSON

### `filtroVideojuegos.html`

- plantilla de frontend para enviar filtros y mostrar resultados

## Consejo de examen

Si tienes poco tiempo, lo más reutilizable de toda esta carpeta es:

- la conexión PDO
- el uso de `prepare()`
- la lectura de JSON con `php://input`
- la respuesta con `json_encode`
- la llamada `fetch()` desde HTML

Con esas cinco piezas puedes resolver gran parte de los ejercicios típicos de recuperación de información.
