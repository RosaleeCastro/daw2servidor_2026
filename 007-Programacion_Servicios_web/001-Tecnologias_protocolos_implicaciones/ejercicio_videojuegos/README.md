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

Consulta tambien el indice general de todo el tema: `../../README.md`.

## Acceso rapido a funciones reutilizables

| Funcion o patron | Archivo | Para que sirve |
| --- | --- | --- |
| `obtenerPDO()` | `conexion_videojuegos.php` | Conectar a MySQL desde cualquier servicio PHP. |
| `cargarVideojuegos()` | `videoJuegos.html` | Llenar un `select` con datos recibidos desde PHP. |
| `filtrarPrecio()` | `videoJuegos.html` | Enviar un filtro al servidor y actualizar el listado. |
| `consultarDisponibilidad()` | `videoJuegos.html` | Consultar stock antes de hacer una compra. |
| `restarStock()` | `videoJuegos.html` | Pedir al servidor que descuente unidades. |
| `calcularPrecio()` | `videoJuegos.html` | Enviar cantidad, descuento e IVA para obtener total. |
| Transaccion con `beginTransaction()` | `servicio_disponibilidad.php` | Bloquear stock, comprobar unidades y actualizar sin errores de concurrencia. |
| Calculo puro sin base de datos | `servicio_calculo_precio.php` | Resolver una operacion de negocio solo con datos de entrada. |

## Que copiar en otro ejercicio

Si necesitas un formulario con `select`, copia la estructura de `videoJuegos.html`: primero cargas datos con `cargarVideojuegos()` y luego el usuario elige por `id`.

Si necesitas descontar inventario, copia el patron de `servicio_disponibilidad.php`: validar entrada, abrir transaccion, comprobar stock, actualizar y confirmar.

Si necesitas calcular importes, copia `servicio_calculo_precio.php`: precio unitario, cantidad, descuento, subtotal, IVA y total.

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
