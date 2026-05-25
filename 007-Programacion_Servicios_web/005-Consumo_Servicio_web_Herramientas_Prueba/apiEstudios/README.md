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
