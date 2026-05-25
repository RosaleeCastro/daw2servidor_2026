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

Consulta tambien el indice general: `../README.md` y `../../README.md`.

## Acceso rapido a funciones reutilizables

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| `responder()` | `apiTareas.php` | Enviar JSON y codigo HTTP desde un unico sitio. |
| `leerJSONBody()` | `apiTareas.php` | Leer datos enviados por `POST` o `PATCH`. |
| `obtenerRuta()` | `apiTareas.php` | Leer la ruta REST desde `?ruta=` o desde `PATH_INFO`. Muy util en XAMPP. |
| `leerTareas()` | `apiTareas.php` | Leer el archivo `tareas.json` y convertirlo a array PHP. |
| `guardarTareas()` | `apiTareas.php` | Guardar el array PHP otra vez como JSON. |
| `validarPrioridad()` | `apiTareas.php` | Comprobar que una prioridad sea `baja`, `media` o `alta`. |
| ID incremental con `array_column()` y `max()` | `apiTareas.php` | Crear ids nuevos sin base de datos. |
| `construirUrl()` | `gestorTareas.html` | Crear URLs con parametros sin escribir strings a mano. |
| `URLSearchParams` | `gestorTareas.html` | Anadir filtros como `estado` y `prioridad`. |
| `openApiTareas.yaml` | `openApiTareas.yaml` | Documentar la API para Swagger/Postman/Insomnia. |

## Que copiar en otro ejercicio

Si el examen pide una API REST sin MySQL, este es el modelo mas rapido:

1. Guardas los datos en un `.json`.
2. Lees el JSON con `leerTareas()`.
3. Segun el metodo HTTP haces una accion.
4. Guardas cambios con `guardarTareas()`.
5. Respondes siempre con `responder()`.

La funcion `obtenerRuta()` es especialmente reutilizable porque evita problemas cuando Apache/XAMPP no rellena bien `PATH_INFO`. Por eso esta API acepta:

- `apiTareas.php?ruta=/tareas`
- `apiTareas.php/tareas`

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
