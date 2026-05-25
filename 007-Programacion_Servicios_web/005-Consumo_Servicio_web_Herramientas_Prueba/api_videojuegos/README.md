# API REST Videojuegos

## Que hace

API REST conectada a MySQL para gestionar videojuegos.

Permite:

- listar videojuegos,
- filtrar por precio, PEGI y multijugador,
- consultar por ID,
- crear videojuego,
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
apiVideojuegos.php
apiVideoJuegos.html
openApiVideojuegos.yaml
README.md
```

## Rutas

Consulta tambien el indice general: `../README.md` y `../../README.md`.

## Acceso rapido a funciones reutilizables

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| `obtenerPDO()` | `apiVideojuegos.php` | Abrir conexion MySQL reutilizable. |
| `responder()` | `apiVideojuegos.php` | Responder siempre en JSON con codigo HTTP. |
| `leerJSONBody()` | `apiVideojuegos.php` | Recibir datos de `POST` y `PATCH`. |
| `buscarVideojuegoPorId()` | `apiVideojuegos.php` | Comprobar si existe un registro antes de consultarlo, editarlo o borrarlo. |
| Filtros con query string | `apiVideojuegos.php` | Filtrar resultados por `precioMax`, `pegiMax` y `multijugador`. |
| Consultas preparadas | `apiVideojuegos.php` | Evitar SQL injection y separar SQL de datos. |
| Cliente REST | `apiVideoJuegos.html` | Probar la API desde navegador. |
| OpenAPI | `openApiVideojuegos.yaml` | Documentar la API para pruebas externas. |

## Que copiar en otro ejercicio

Este ejercicio es el modelo para una API REST con MySQL. Para adaptarlo:

- cambia la tabla `videojuego` por tu tabla,
- cambia los campos del `SELECT`, `INSERT` y `UPDATE`,
- conserva `obtenerPDO()`, `responder()` y `leerJSONBody()`,
- conserva las consultas preparadas con `prepare()` y `execute()`.

```text
GET    apiVideojuegos.php/videojuegos
GET    apiVideojuegos.php/videojuegos/{id}
POST   apiVideojuegos.php/videojuegos
PATCH  apiVideojuegos.php/videojuegos/{id}
DELETE apiVideojuegos.php/videojuegos/{id}
```

## Filtros

```text
apiVideojuegos.php/videojuegos?precioMax=60&pegiMax=18&multijugador=true
```

## Ejemplo de videojuego

```json
{
  "titulo": "Juego de prueba REST",
  "fecha_lanzamiento": "2026-05-10",
  "pegi": 12,
  "precio_base": 29.99,
  "motor": "Motor REST",
  "es_multijugador": false
}
```

## Funcion reutilizable: buscar por ID

```php
function buscarPorId($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM tabla WHERE id = :id");
    $stmt->execute([":id" => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

## Para examen

Cuando uses MySQL en REST:

```text
valida datos
usa prepare/execute
devuelve codigo HTTP correcto
responde JSON
```
