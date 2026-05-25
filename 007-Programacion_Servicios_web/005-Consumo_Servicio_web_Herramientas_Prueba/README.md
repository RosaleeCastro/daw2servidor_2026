# 005 - Consumo de servicios web y herramientas de prueba

## Que contiene

APIs REST documentadas con OpenAPI.

Aqui se practica:

- consumo con `fetch()`,
- metodos HTTP,
- JSON,
- codigos de estado,
- documentacion YAML/OpenAPI.

## Herramientas

- HTML
- JavaScript `fetch()`
- PHP
- JSON
- MySQL o JSON local
- OpenAPI YAML
- Postman / Swagger / Insomnia como herramientas externas posibles

## Subcarpetas

```text
api_libros/
api_videojuegos/
apiEstudios/
gestorTareas_api/
```

## Estructura REST comun

```text
GET    /recurso
GET    /recurso/{id}
POST   /recurso
PATCH  /recurso/{id}
DELETE /recurso/{id}
```

## Funcion reutilizable: responder JSON

Consulta tambien el indice general: `../README.md`. Esa guia es la version mas completa para examen: compara REST, SOAP, JSON, MySQL y YAML/OpenAPI.

### Acceso rapido por subcarpeta

| Subcarpeta | Datos usados | Que puedes reutilizar |
| --- | --- | --- |
| `api_libros/` | JSON local | API REST sin base de datos: leer archivo, guardar archivo, listar, buscar, crear, editar y borrar. |
| `api_videojuegos/` | MySQL | API REST conectada a base de datos con `obtenerPDO()` y consultas preparadas. |
| `apiEstudios/` | MySQL | API REST con validacion y `PATCH` dinamico para editar solo campos enviados. |
| `gestorTareas_api/` | JSON local | API REST completa con filtros, prioridades, estados y ruta por `?ruta=` para XAMPP. |

### Acceso rapido por funcion reusable

| Funcion o patron | Archivo donde esta | Para que sirve |
| --- | --- | --- |
| `responder($codigo, $datos)` | `api_libros/apiRestLibros.php`, `apiEstudios/apiEstudios.php`, `gestorTareas_api/apiTareas.php` | Centralizar respuestas JSON y codigos HTTP. |
| `leerJSONBody()` | `api_libros/apiRestLibros.php`, `apiEstudios/apiEstudios.php`, `gestorTareas_api/apiTareas.php` | Leer el cuerpo JSON enviado por `POST`, `PUT` o `PATCH`. |
| `obtenerRuta()` | `gestorTareas_api/apiTareas.php` | Detectar rutas REST aunque XAMPP no envie bien `PATH_INFO`. |
| `leerTareas()` / `guardarTareas()` | `gestorTareas_api/apiTareas.php` | Persistir datos en archivo JSON. |
| `leerLibros()` / `guardarLibros()` | `api_libros/apiRestLibros.php` | Misma idea anterior aplicada a libros. |
| `obtenerPDO()` | `api_videojuegos/apiVideojuegos.php`, `apiEstudios/apiEstudios.php` | Abrir conexion MySQL reutilizable. |
| `buscarEstudioPorId()` | `apiEstudios/apiEstudios.php` | Buscar un registro por id antes de editarlo o borrarlo. |
| `validarPrioridad()` | `gestorTareas_api/apiTareas.php` | Validar campos con valores permitidos. |
| `construirUrl()` | `gestorTareas_api/gestorTareas.html` | Construir URLs con query string sin romper parametros. |
| `mostrarPeticion()` / `mostrarRespuesta()` | Clientes HTML REST | Mostrar en pantalla que se envio y que devolvio el servidor. |

### Que copiar segun el tipo de examen

Si el examen pide una API rapida sin MySQL, copia el estilo de `api_libros` o `gestorTareas_api`.

Si el examen pide base de datos, copia `obtenerPDO()` y las consultas preparadas de `api_videojuegos` o `apiEstudios`.

Si el examen pide documentacion OpenAPI, copia la estructura de `openApiLibros.yaml`, `openApiVideojuegos.yaml`, `openApiEstudio.yaml` u `openApiTareas.yaml`.

```php
function responder($codigo, $datos = null) {
    http_response_code($codigo);

    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    exit;
}
```

## Funcion reutilizable: leer body JSON

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

## OpenAPI YAML

El YAML no ejecuta codigo. Documenta la API.

Define:

- rutas,
- metodos,
- parametros,
- cuerpos JSON,
- respuestas,
- schemas.

## Para examen

Recuerda:

```text
PHP implementa la API.
HTML consume la API.
YAML documenta la API.
```
