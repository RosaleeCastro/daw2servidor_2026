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
