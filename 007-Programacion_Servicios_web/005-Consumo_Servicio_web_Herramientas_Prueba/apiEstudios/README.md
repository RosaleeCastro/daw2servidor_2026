# API REST Estudios

## Que hace

API REST para trabajar con la tabla `estudio` de la base de datos
`videojuegos_asir`.

Permite:

- listar estudios,
- filtrar por pais, ciudad y anio de fundacion,
- consultar por ID,
- crear,
- actualizar parcialmente,
- eliminar.

## Herramientas

- PHP
- PDO
- MySQL
- HTML
- JavaScript `fetch()`
- JSON
- OpenAPI YAML

## Archivos

```text
apiEstudios.php
apiEstudios.html
openApiEstudio.yaml
apiEStudios.txt
README.md
```

## Rutas

Consulta tambien el indice general: `../README.md` y `../../README.md`.

## Acceso rapido a funciones reutilizables

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| `obtenerPDO()` | `apiEstudios.php` | Conectar a MySQL con PDO y reutilizar la conexion. |
| `responder()` | `apiEstudios.php` | Enviar JSON con codigo HTTP y terminar la ejecucion. |
| `leerJSONBody()` | `apiEstudios.php` | Leer el JSON recibido en `POST` y `PATCH`. |
| `normalizarEstudio()` | `apiEstudios.php` | Adaptar los nombres reales de la base de datos al JSON que usa el cliente. |
| `buscarEstudioPorId()` | `apiEstudios.php` | Reutilizar una busqueda por id antes de consultar, editar o borrar. |
| `PATCH dinamico` | `apiEstudios.php` | Actualizar solo los campos que llegan en el JSON. |
| `fetch()` REST | `apiEstudios.html` | Probar `GET`, `POST`, `PATCH` y `DELETE` desde navegador. |
| Contrato OpenAPI | `openApiEstudio.yaml` | Documentar rutas, parametros, bodies y respuestas. |

## Que copiar en otro ejercicio

Para una API REST con MySQL, copia la estructura de `apiEstudios.php`:

1. Cabecera `Content-Type: application/json`.
2. Funcion `obtenerPDO()`.
3. Funcion `responder()`.
4. Funcion `leerJSONBody()`.
5. Deteccion del metodo con `$_SERVER["REQUEST_METHOD"]`.
6. Deteccion de ruta con `PATH_INFO`.
7. `switch` o `if` por metodo y ruta.
8. Consultas preparadas con `prepare()` y `execute()`.

```text
GET    apiEstudios.php/estudios
GET    apiEstudios.php/estudios/{id}
POST   apiEstudios.php/estudios
PATCH  apiEstudios.php/estudios/{id}
DELETE apiEstudios.php/estudios/{id}
```

## Filtros

```text
apiEstudios.php/estudios?pais=Japon&ciudad=Kioto&fundadoDesde=1980
```

## Ejemplo de estudio

```json
{
  "id": 1,
  "nombre": "Larian Studios",
  "pais": "Belgica",
  "ciudad": "Ghent",
  "fundado_en": 1996,
  "web": "https://larian.com"
}
```

## Funcion reutilizable: PATCH dinamico

```php
$campos = [];
$params = [":id" => $id];

if (isset($data["nombre"])) {
    $campos[] = "nombre = :nombre";
    $params[":nombre"] = trim($data["nombre"]);
}

$sql = "UPDATE tabla SET " . implode(", ", $campos) . " WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

## Para examen

PATCH no tiene que recibir todos los campos. Solo modifica los campos enviados.
