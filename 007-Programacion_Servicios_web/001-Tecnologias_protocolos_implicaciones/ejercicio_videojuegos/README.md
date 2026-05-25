# Ejercicio Videojuegos

## Que hace

Implementa servicios separados para una tienda de videojuegos:

- listar videojuegos,
- consultar disponibilidad,
- restar stock,
- calcular precio final.

## Herramientas

- HTML
- JavaScript `fetch()`
- PHP
- MySQL
- JSON

## Archivos

```text
videoJuegos.html
conexion_videojuegos.php
servicio_videojuegos.php
servicio_disponibilidad.php
servicio_calculo_precio.php
servicioVideojuegos.txt
```

## Servicios

### servicio_videojuegos.php

Lista videojuegos y permite filtrar por precio.

Entrada opcional:

```json
{
  "precio_maximo": 50
}
```

Respuesta:

```json
{
  "ok": true,
  "videojuegos": []
}
```

### servicio_disponibilidad.php

Consulta tiendas donde hay stock o resta unidades.

Consultar:

```json
{
  "id_videojuego": 1
}
```

Restar stock:

```json
{
  "accion": "restar_stock",
  "id_videojuego": 1,
  "cantidad": 2
}
```

### servicio_calculo_precio.php

No usa MySQL. Solo calcula.

Entrada:

```json
{
  "precio": 59.99,
  "cantidad": 2,
  "descuento": 10,
  "iva": 21
}
```

## Funcion reutilizable de calculo

```php
$subtotal = $precio * $cantidad;
$importeDescuento = $subtotal * ($descuento / 100);
$baseImponible = $subtotal - $importeDescuento;
$importeIva = $baseImponible * ($iva / 100);
$total = $baseImponible + $importeIva;
```

## Para examen

Separa servicios por responsabilidad:

```text
uno lista
uno consulta stock
uno modifica stock
uno calcula
```
