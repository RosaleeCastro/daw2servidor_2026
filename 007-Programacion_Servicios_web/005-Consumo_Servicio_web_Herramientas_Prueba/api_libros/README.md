# API REST Libros

## Que hace

API REST sencilla para gestionar libros.

Permite:

- listar libros,
- consultar un libro por ID,
- crear libro,
- modificar disponibilidad,
- eliminar libro.

## Herramientas

- PHP
- HTML
- JavaScript `fetch()`
- JSON
- OpenAPI YAML
- Archivo `libros.json` como almacenamiento

## Archivos

```text
apiRestLibros.php
apiRestLibros.html
openApiLibros.yaml
libros.js
```

## Rutas

```text
GET    apiRestLibros.php/libros
GET    apiRestLibros.php/libros/{id}
POST   apiRestLibros.php/libros
PATCH  apiRestLibros.php/libros/{id}
DELETE apiRestLibros.php/libros/{id}
```

## Ejemplo de libro

```json
{
  "id": 1,
  "titulo": "El Quijote",
  "autor": "Miguel de Cervantes",
  "disponible": true
}
```

## Plantilla reutilizable para archivo JSON

```php
$archivoDatos = __DIR__ . "/datos.json";

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

## Para examen

Si no hay base de datos, puedes hacer una API REST usando un `.json`.

Es mas simple que MySQL y sirve para practicar metodos HTTP.
