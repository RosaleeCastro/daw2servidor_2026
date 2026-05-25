# API REST Gestor de Tareas

## Que hace

API REST para gestionar tareas. Usa un archivo `tareas.json` como almacenamiento.

Permite:

- consultar todas las tareas,
- consultar una tarea concreta,
- anadir una tarea,
- actualizar una tarea,
- eliminar una tarea.

## Herramientas

- PHP
- HTML
- JavaScript `fetch()`
- JSON
- OpenAPI YAML
- Archivo local `tareas.json`

## Archivos

```text
apiTareas.php
gestorTareas.html
openApiTareas.yaml
tareas.json
README.md
```

## Rutas

```text
GET    apiTareas.php?ruta=/tareas
GET    apiTareas.php?ruta=/tareas/1
POST   apiTareas.php?ruta=/tareas
PATCH  apiTareas.php?ruta=/tareas/1
DELETE apiTareas.php?ruta=/tareas/1
```

Tambien acepta formato PATH_INFO:

```text
apiTareas.php/tareas
apiTareas.php/tareas/1
```

## Estructura de tarea

```json
{
  "id": 1,
  "titulo": "Repasar APIs REST",
  "descripcion": "Leer documentacion y probar endpoints.",
  "completada": false,
  "prioridad": "alta",
  "fecha_creacion": "2026-05-25 08:30:00"
}
```

## Funciones reutilizables

### Validar opciones cerradas

```php
function validarPrioridad($prioridad) {
    return in_array($prioridad, ["baja", "media", "alta"], true);
}
```

### Crear ID incremental

```php
$ids = array_column($tareas, "id");
$nuevoId = empty($ids) ? 1 : max($ids) + 1;
```

### Filtrar arrays

```php
$resultado = array_values(array_filter($tareas, function ($tarea) {
    return $tarea["completada"] === false;
}));
```

## Para examen

Esta carpeta es ideal para recordar una API REST completa sin MySQL:

```text
GET lista
GET id
POST crea
PATCH modifica
DELETE elimina
YAML documenta
```
