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

Consulta tambien el indice general: `../README.md`. Ahi estan reunidas las funciones copiables de SOAP, REST, JSON, MySQL y OpenAPI.

### Acceso rapido por archivo

| Archivo | Que puedes reutilizar | Para que sirve en otro ejercicio |
| --- | --- | --- |
| `envioPostal.php` | Leer `requestId` desde `soap:Header` | Trazar una peticion o identificarla en respuestas y errores. |
| `envioPostal.php` | Calculo de precio segun reglas | Separar reglas de negocio de la construccion SOAP. |
| `envioPostal.html` | Envio SOAP desde formulario HTML | Montar un cliente de pruebas rapido para cualquier servicio SOAP. |
| `envioPostal.wsdl` | Definicion de tipos y operacion | Explicar la interfaz del servicio. |
| `acceso_examen/accesoExamen.php` | Validar acceso y devolver decision | Crear servicios SOAP de autorizacion. |
| `acceso_examen/README.md` | Explicacion del ejercicio de acceso | Repasar el flujo completo antes del examen. |

### Cuando reutilizar esta carpeta

Usala como modelo cuando tengas que explicar o crear la "interfaz" de un servicio:

- que operacion se puede invocar,
- que datos entran,
- que datos salen,
- que errores se devuelven,
- como se describe todo en WSDL.

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
