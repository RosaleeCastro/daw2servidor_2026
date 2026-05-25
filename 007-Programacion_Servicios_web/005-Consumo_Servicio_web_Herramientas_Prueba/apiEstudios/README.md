# API REST Estudios

Esta carpeta contiene una API REST para trabajar con la tabla `estudio` de la
base de datos MySQL `videojuegos_asir`.

## Archivos

- `apiEstudios.php`: servidor REST.
- `apiEstudios.html`: cliente web de prueba.
- `openApiEstudio.yaml`: documentacion OpenAPI.
- `apiEStudios.txt`: enunciado y notas del ejercicio.

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

## Campos

```text
id
nombre
pais
ciudad
fundado_en
web
```

## Probar en navegador

```text
http://localhost/daw2servidor_RCT/daw2servidor_2026/007-Programacion_Servicios_web/005-Consumo_Servicio_web_Herramientas_Prueba/apiEstudios/apiEstudios.html
```
