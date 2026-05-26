# 002 - Generacion dinamica de paginas interactivas

Esta carpeta muestra una pagina HTML que habla con un controlador PHP usando `fetch()`.

El servidor no usa base de datos. Guarda la informacion en archivos JSON:

- `datos/videojuegos.json`
- `datos/alumnos.json`

## Flujo general

```text
cliente.html
  -> fetch("controlador.php/videojuegos")
  -> controlador.php
  -> servicios/servicioVideojuegos.php
  -> datos/videojuegos.json
  -> respuesta JSON
  -> cliente.html actualiza el DOM
```

## Archivos

| Archivo | Funcion dentro del ejercicio |
| --- | --- |
| `cliente.html` | Interfaz del usuario. Tiene formularios, botones y JavaScript. |
| `controlador.php` | Punto de entrada de la API. Decide que servicio usar. |
| `servicios/servicioVideojuegos.php` | Logica de consultar y anadir videojuegos. |
| `servicios/servicioAlumnos.php` | Logica de consultar y anadir alumnos. |
| `datos/videojuegos.json` | Datos persistidos de videojuegos. |
| `datos/alumnos.json` | Datos persistidos de alumnos. |

## Controlador PHP

El archivo `controlador.php` tiene este papel:

```text
recibe peticion
  -> mira metodo HTTP: GET o POST
  -> mira ruta: videojuegos o alumnos
  -> carga el archivo del servicio correcto
  -> ejecuta la funcion del servicio
  -> responde JSON
```

## Patron reutilizable: mapa de servicios

Archivo: `controlador.php`.

```php
$servicios = [
    "videojuegos" => [
        "archivo" => "servicios/servicioVideojuegos.php",
        "funcion" => "servicioVideojuegos"
    ],
    "alumnos" => [
        "archivo" => "servicios/servicioAlumnos.php",
        "funcion" => "servicioAlumnos"
    ]
];
```

Para adaptarlo a otro ejercicio:

```php
$servicios = [
    "productos" => [
        "archivo" => "servicios/servicioProductos.php",
        "funcion" => "servicioProductos"
    ]
];
```

## Patron reutilizable: responder JSON

Archivo: `controlador.php`.

```php
function responderJson($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
```

Sirve para que todas las respuestas tengan el mismo formato.

## Patron reutilizable: leer body JSON

Archivo: `controlador.php`.

```php
$entrada = file_get_contents("php://input");
$datosEntrada = json_decode($entrada, true);

if ($datosEntrada === null) {
    $datosEntrada = [];
}
```

Sirve para recibir datos enviados desde JavaScript con:

```js
body: JSON.stringify({
  titulo: titulo,
  genero: genero,
});
```

## Servicios reutilizables

### `servicioVideojuegos($metodo, $datosEntrada)`

Archivo: `servicios/servicioVideojuegos.php`.

Decide que hacer segun el metodo:

```text
GET  -> consultarVideojuegos()
POST -> anadirVideojuego()
otro -> error 405
```

### `leerJson($archivo)`

Archivo: `servicios/servicioVideojuegos.php`.

Lee un archivo JSON y lo convierte en array PHP.

### `guardarJson($archivo, $datos)`

Archivo: `servicios/servicioVideojuegos.php`.

Convierte un array PHP en JSON y lo guarda.

### `generarNuevoId($datos)`

Archivo: `servicios/servicioVideojuegos.php`.

Busca el id mayor y devuelve el siguiente.

## Cliente HTML reutilizable

Archivo: `cliente.html`.

Funciones importantes:

| Funcion | Para que sirve |
| --- | --- |
| `cargarVideojuegos()` | Hace `GET` y muestra videojuegos. |
| `cargarAlumnos()` | Hace `GET` y muestra alumnos. |
| `anadirVideojuego()` | Hace `POST` con JSON. |
| `anadirAlumno()` | Hace `POST` con JSON. |
| `mostrarVideojuegos()` | Crea una lista HTML desde un array. |
| `mostrarAlumnos()` | Crea una lista HTML desde un array. |
| `mostrarMensaje()` | Muestra errores o mensajes correctos. |
| `limpiarMensaje()` | Limpia mensajes anteriores. |

## Chuleta para crear otro servicio

1. Crea un archivo JSON en `datos/`.
2. Crea un archivo PHP en `servicios/`.
3. En el servicio crea una funcion principal: `servicioProductos($metodo, $datosEntrada)`.
4. Anade el servicio al mapa de `controlador.php`.
5. En `cliente.html` crea una constante con la URL.
6. Usa `fetch()` para pedir o enviar datos.
7. Pinta la respuesta en el DOM.
