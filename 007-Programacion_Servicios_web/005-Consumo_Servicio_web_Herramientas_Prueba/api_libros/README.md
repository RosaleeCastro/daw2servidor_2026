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

Consulta tambien el indice general: `../README.md` y `../../README.md`.

## Acceso rapido a funciones reutilizables

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| `responder()` | `apiRestLibros.php` | Enviar JSON con codigo HTTP. |
| `leerJSONBody()` | `apiRestLibros.php` | Leer el cuerpo JSON de `POST` y `PATCH`. |
| `leerLibros()` | `apiRestLibros.php` | Cargar datos desde `libros.json`. |
| `guardarLibros()` | `apiRestLibros.php` | Guardar cambios en `libros.json`. |
| Buscar libro por `id` | `apiRestLibros.php` | Localizar un elemento dentro de un array. |
| Crear ID incremental | `apiRestLibros.php` | Crear registros nuevos sin base de datos. |
| Cliente `fetch()` | `apiRestLibros.html` | Probar rutas REST desde formulario HTML. |
| OpenAPI | `openApiLibros.yaml` | Documentar endpoints, parametros y respuestas. |

## Que copiar en otro ejercicio

Este ejercicio es ideal si el examen pide REST pero no pide MySQL. Cambias:

- `libros.json` por `productos.json`, `alumnos.json`, `tareas.json`, etc.
- Los campos `titulo`, `autor`, `disponible` por los campos del nuevo ejercicio.
- Las rutas `/libros` por `/productos`, `/alumnos`, `/tareas`, etc.

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
