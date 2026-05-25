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
