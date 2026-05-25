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
