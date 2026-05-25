# 004 - Interface de un servicio web

## Que contiene

Ejercicios SOAP donde se practica la interfaz del servicio:

- que operacion existe,
- que campos recibe,
- que campos devuelve,
- como se describe con WSDL.

## Herramientas

- HTML
- JavaScript
- PHP
- SOAP
- XML
- WSDL

## Carpetas y archivos

```text
envioPostal.html
envioPostal.php
envioPostal.wsdl
accesoExamen.txt
acceso_examen/
```

## envioPostal

Servicio SOAP que calcula precio y plazo de envio postal.

Recibe:

```xml
<calcularEnvio>
  <peso>2.5</peso>
  <zona>peninsula</zona>
  <urgente>true</urgente>
</calcularEnvio>
```

Devuelve:

```xml
<calcularEnvioResponse>
  <precio>13.00</precio>
  <plazoDias>1</plazoDias>
  <zona>peninsula</zona>
  <urgente>true</urgente>
</calcularEnvioResponse>
```

## Funcion reutilizable: leer requestId

```php
$header = $xpath->query("//soap:Header")->item(0);
$requestId = "";

if ($header instanceof DOMElement) {
    $requestIdNode = $header->getElementsByTagName("requestId")->item(0);
    if ($requestIdNode) {
        $requestId = trim($requestIdNode->textContent);
    }
}
```

## Para examen

La interfaz SOAP se entiende asi:

```text
WSDL dice que se puede pedir.
PHP ejecuta lo que se pide.
HTML crea el XML SOAP para pedirlo.
```
