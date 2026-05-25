# API REST Videojuegos

Esta carpeta contiene una API REST para trabajar con videojuegos guardados en
la base de datos MySQL `videojuegos_asir`.

## Archivos

- `apiVideojuegos.php`: servidor REST. Recibe peticiones HTTP, consulta o
  modifica MySQL y responde JSON.
- `apiVideoJuegos.html`: cliente web de prueba. Usa `fetch()` para llamar a la
  API y mostrar la peticion y la respuesta.
- `openApiVideojuegos.yaml`: contrato/documentacion OpenAPI de la API.

## Rutas principales

```text
GET    apiVideojuegos.php/videojuegos
GET    apiVideojuegos.php/videojuegos/{id}
POST   apiVideojuegos.php/videojuegos
PATCH  apiVideojuegos.php/videojuegos/{id}
DELETE apiVideojuegos.php/videojuegos/{id}
```

## Filtros de listado

```text
apiVideojuegos.php/videojuegos?precioMax=60&pegiMax=18&multijugador=true
```

## Flujo

```text
apiVideoJuegos.html
  -> fetch()
  -> apiVideojuegos.php
  -> MySQL videojuegos_asir
  -> JSON
  -> respuesta visible en pantalla
```
