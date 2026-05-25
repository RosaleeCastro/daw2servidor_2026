# 003 - Generacion de un servicio web

## Que contiene

Servicio SOAP de prestamos de biblioteca.

El ejercicio añade una idea importante: cabeceras SOAP de sesion.

## Herramientas

- HTML
- JavaScript
- PHP
- SOAP
- XML
- WSDL

## Archivos

```text
prestamos.html
prestamos.php
prestamos.wsdl
prestamos.txt
```

## Funcionalidad

El servicio recibe:

```xml
<consultarPrestamo>
  <dni>12345678A</dni>
  <codigoLibro>LIB001</codigoLibro>
</consultarPrestamo>
```

Y tambien recibe cabecera:

```xml
<soap:Header>
  <sesion>
    <token>ABC123</token>
  </sesion>
</soap:Header>
```

Devuelve:

```xml
<consultarPrestamoResponse>
  <puede_prestar>true</puede_prestar>
  <mensaje>Prestamo autorizado</mensaje>
  <dias_maximos>15</dias_maximos>
</consultarPrestamoResponse>
```

## Funciones reutilizables

Consulta tambien el indice general: `../README.md`. Ahi tienes un resumen completo de patrones reutilizables de todas las carpetas.

### Acceso rapido por archivo

| Archivo | Que puedes reutilizar | Para que sirve en otro ejercicio |
| --- | --- | --- |
| `prestamos.php` | Validacion de `Header`, `Body`, token y parametros | Crear servicios SOAP con autenticacion o datos de sesion. |
| `prestamos.php` | `responderFault()` | Devolver errores SOAP correctos cuando falta token, DNI o codigo. |
| `prestamos.php` | `responderPrestamoSOAP()` | Responder varios campos dentro de una operacion SOAP. |
| `prestamos.html` | `DOMParser` y lectura de respuesta XML | Interpretar XML SOAP recibido en el navegador. |
| `prestamos.wsdl` | Contrato WSDL de la operacion | Describir que pide y que devuelve el servicio. |

### Patron importante para examen

Este ejercicio es util cuando el enunciado diga:

- "validar sesion",
- "enviar cabecera",
- "controlar acceso",
- "devolver error si falta un dato",
- "interpretar una respuesta SOAP".

### Validar token SOAP

```php
$header = $xpath->query("//soap:Header")->item(0);
$tokenNode = $xpath->query(".//*[local-name()='token']", $header)->item(0);

if (!$tokenNode || trim($tokenNode->textContent) !== TOKEN_VALIDO) {
    responderFault("Token de sesion no valido.");
}
```

### Respuesta con varios campos

```php
function responderPrestamoSOAP($puedePrestar, $mensaje, $diasMaximos) {
    echo '<consultarPrestamoResponse>'
       . '<puede_prestar>' . ($puedePrestar ? "true" : "false") . '</puede_prestar>'
       . '<mensaje>' . htmlspecialchars($mensaje, ENT_XML1) . '</mensaje>'
       . '<dias_maximos>' . (int)$diasMaximos . '</dias_maximos>'
       . '</consultarPrestamoResponse>';
}
```

## Para examen

Si el enunciado dice "cabecera de sesion", usa `soap:Header`.

Si dice "datos de la operacion", van en `soap:Body`.
