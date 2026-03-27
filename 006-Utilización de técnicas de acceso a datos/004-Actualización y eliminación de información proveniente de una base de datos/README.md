# 004 - Actualización y eliminación de información proveniente de una base de datos

Esta carpeta reúne ejemplos base para operaciones de escritura sobre MySQL con PHP y PDO. Lo más valioso aquí no es solo cada ejercicio por separado, sino los patrones reutilizables que sirven como plantilla para construir formularios, procesos CRUD y flujos con transacciones.

La carpeta principal contiene:

- `002-Ejercicios/crudSimple.php`
- `002-Ejercicios/transaccion.php`
- `002-Ejercicios/ejercicio_crud/conexion.php`
- `002-Ejercicios/ejercicio_crud/insertar_videojuego.php`
- `002-Ejercicios/ejercicio_crud/actualizar_videojuego.php`
- `002-Ejercicios/ejercicio_crud/añadir_videojuego.html`

## Lo más reutilizable

Si hay que quedarse con las piezas base de esta carpeta, las más reutilizables son estas:

- conexión centralizada con PDO
- `prepare()` + `execute()` para `INSERT`, `UPDATE` y `DELETE`
- recogida de datos con `$_POST`
- uso de valores `null` cuando un campo opcional llega vacío
- recuperación de la fila recién insertada con `lastInsertId()`
- actualización parcial conservando valores antiguos
- transacciones con `beginTransaction()`, `commit()` y `rollBack()`
- separación entre formulario HTML y script PHP de proceso

## Funcionalidades disponibles

### 1. CRUD básico en un solo archivo

Archivo: `002-Ejercicios/crudSimple.php`

Qué hace:

- conecta con la base de datos
- recibe una acción por `POST`
- permite añadir, actualizar y eliminar
- usa un solo formulario con varios botones

Base reutilizable:

```php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "add") {
        // INSERT
    }

    if ($accion === "update") {
        // UPDATE
    }

    if ($accion === "delete") {
        // DELETE
    }
}
```

Por qué sirve de base:

- concentra todo el flujo CRUD en un único punto
- permite montar prototipos rápidos
- deja claro cómo distinguir varias acciones desde un mismo formulario

Reutilización recomendada:

- paneles CRUD simples
- ejercicios pequeños de mantenimiento
- primeras versiones de una administración interna

### 2. Transacciones con commit y rollback

Archivo: `002-Ejercicios/transaccion.php`

Qué hace:

- inicia una transacción
- inserta un desarrollador
- hace un `SELECT` dentro de la transacción
- decide entre confirmar o deshacer según el botón pulsado
- revierte también si ocurre una excepción

Base reutilizable:

```php
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO tabla (campo) VALUES (:campo)");
    $stmt->execute([':campo' => $valor]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
```

Por qué sirve de base:

- protege operaciones que deben ejecutarse juntas
- evita dejar datos a medias si algo falla
- enseña una estructura sólida para procesos críticos

Reutilización recomendada:

- inserciones encadenadas
- altas con varias tablas relacionadas
- operaciones de negocio donde el error no puede dejar datos inconsistentes

### 3. Conexión centralizada compartida

Archivo: `002-Ejercicios/ejercicio_crud/conexion.php`

Qué hace:

- encapsula la conexión PDO en un archivo aparte
- permite reutilizar `$pdo` desde varios scripts con `require_once`

Base reutilizable:

```php
require_once "conexion.php";

if (!isset($pdo)) {
    die("No se pudo establecer la conexión.");
}
```

Por qué sirve de base:

- evita repetir credenciales en cada archivo
- facilita cambios de conexión en un solo punto
- mejora el orden del proyecto

Reutilización recomendada:

- cualquier mini proyecto con varios scripts PHP
- CRUDs separados por acciones
- APIs o paneles con varios endpoints

### 4. Inserción completa con validación básica

Archivo: `002-Ejercicios/ejercicio_crud/insertar_videojuego.php`

Qué hace:

- recoge datos enviados por `POST`
- limpia algunos campos con `trim()`
- transforma vacíos en `null`
- ejecuta un `INSERT` con parámetros nombrados
- obtiene el ID insertado con `lastInsertId()`
- recupera la fila insertada para mostrarla

Base reutilizable:

```php
$sql = "INSERT INTO tabla (campo1, campo2) VALUES (:campo1, :campo2)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":campo1" => $valor1,
    ":campo2" => $valor2
]);

$idNuevo = $pdo->lastInsertId();
```

Patrón especialmente útil:

```php
$campo = ($_POST["campo"] !== "") ? $_POST["campo"] : null;
```

Por qué sirve de base:

- resuelve bien campos opcionales
- deja preparado el flujo típico de alta
- permite verificar inmediatamente qué se ha insertado

Reutilización recomendada:

- altas de registros
- formularios extensos
- inserciones con campos opcionales y claves foráneas

### 5. Actualización parcial conservando datos previos

Archivo: `002-Ejercicios/ejercicio_crud/actualizar_videojuego.php`

Qué hace:

- exige un `id`
- recupera primero la fila actual
- usa el valor recibido si existe
- si un campo llega vacío, mantiene el valor anterior
- ejecuta el `UPDATE`
- vuelve a consultar la fila para mostrar el resultado final

Base reutilizable:

```php
$sqlActual = "SELECT * FROM tabla WHERE id = :id";
$stmtActual = $pdo->prepare($sqlActual);
$stmtActual->execute([":id" => $id]);
$filaActual = $stmtActual->fetch(PDO::FETCH_ASSOC);
```

```php
$campo = ($_POST["campo"] !== "")
    ? $_POST["campo"]
    : $filaActual["campo"];
```

```php
$sql = "UPDATE tabla SET campo = :campo WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":id" => $id,
    ":campo" => $campo
]);
```

Por qué sirve de base:

- evita sobreescribir con vacíos cuando el formulario no rellena todos los campos
- es una forma cómoda de hacer actualizaciones parciales
- separa claramente lectura previa y escritura final

Reutilización recomendada:

- formularios de edición
- paneles de administración
- procesos donde no siempre se cambian todos los campos

### 6. Formulario HTML separado del procesamiento

Archivo: `002-Ejercicios/ejercicio_crud/añadir_videojuego.html`

Qué hace:

- contiene un formulario de inserción
- contiene otro formulario de actualización
- envía cada uno a su script PHP correspondiente
- usa distintos tipos de input: `text`, `date`, `number`, `select`, `textarea`

Base reutilizable:

```html
<form action="insertar_videojuego.php" method="POST">
  ...
</form>

<form action="actualizar_videojuego.php" method="POST">
  ...
</form>
```

Por qué sirve de base:

- separa interfaz y lógica
- hace más claro el mantenimiento
- permite escalar el CRUD sin mezclar demasiado HTML y PHP

Reutilización recomendada:

- paneles administrativos
- formularios de alta y edición
- proyectos donde cada acción tenga su script específico

## Patrones base que conviene reutilizar

### Conexión PDO

```php
$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### Insertar con parámetros nombrados

```php
$stmt = $pdo->prepare("
    INSERT INTO tabla (nombre, precio)
    VALUES (:nombre, :precio)
");

$stmt->execute([
    ':nombre' => $nombre,
    ':precio' => $precio
]);
```

### Actualizar por ID

```php
$stmt = $pdo->prepare("
    UPDATE tabla
    SET nombre = :nombre
    WHERE id = :id
");

$stmt->execute([
    ':id' => $id,
    ':nombre' => $nombre
]);
```

### Eliminar por ID

```php
$stmt = $pdo->prepare("
    DELETE FROM tabla
    WHERE id = :id
");

$stmt->execute([
    ':id' => $id
]);
```

### Convertir vacíos en `null`

```php
$valor = ($_POST["valor"] !== "") ? $_POST["valor"] : null;
```

### Obtener el ID recién insertado

```php
$idNuevo = $pdo->lastInsertId();
```

### Mantener valor anterior en una edición

```php
$campo = ($_POST["campo"] !== "")
    ? $_POST["campo"]
    : $filaActual["campo"];
```

### Transacción segura

```php
$pdo->beginTransaction();

try {
    // operaciones
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
```

## Qué usar como base según necesidad

### Si quieres una base mínima y rápida

Usa:

- `crudSimple.php`

Porque:

- resuelve altas, cambios y borrados en un solo archivo
- es la forma más directa de montar un CRUD funcional

### Si quieres una base más ordenada y separada

Usa:

- `ejercicio_crud/conexion.php`
- `ejercicio_crud/añadir_videojuego.html`
- `ejercicio_crud/insertar_videojuego.php`
- `ejercicio_crud/actualizar_videojuego.php`

Porque:

- separa conexión, formulario y procesamiento
- es una estructura mejor para ampliar y mantener

### Si quieres una base robusta para varias operaciones dependientes

Usa:

- `transaccion.php`

Porque:

- introduce control transaccional
- añade seguridad cuando una operación depende de otra

## Resumen útil por archivo

### `crudSimple.php`

- base compacta para CRUD completo

### `transaccion.php`

- base para operaciones atómicas con confirmación o reversión

### `ejercicio_crud/conexion.php`

- base compartida de conexión

### `ejercicio_crud/insertar_videojuego.php`

- base para altas con validación y recuperación del registro insertado

### `ejercicio_crud/actualizar_videojuego.php`

- base para ediciones parciales sin perder valores previos

### `ejercicio_crud/añadir_videojuego.html`

- base de interfaz para formularios CRUD

## Idea clave de esta carpeta

El mayor valor reutilizable aquí no está solo en el CRUD, sino en estos tres cimientos:

- escribir datos de forma segura con `prepare()`
- organizar la lógica separando conexión, formulario y proceso
- proteger operaciones delicadas mediante transacciones

Con esas tres bases, esta carpeta sirve como plantilla para montar casi cualquier flujo de inserción, edición o borrado sobre una base de datos.
