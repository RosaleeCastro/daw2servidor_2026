# 002 - Estandares, arquitectura y formatos de intercambio

## Que contiene

Ejemplo SOAP de calculadora.

SOAP usa XML y una estructura formal:

```xml
<soap:Envelope>
  <soap:Header>...</soap:Header>
  <soap:Body>...</soap:Body>
</soap:Envelope>
```

## Herramientas

- HTML
- JavaScript `fetch()`
- PHP
- XML
- SOAP
- WSDL

## Archivos

```text
calculadora-soap.html
calculadora-soap.php
calculadora.wsdl
ampliacionCalculadora.txt
```

## Funcionalidades

La calculadora SOAP permite:

- sumar,
- restar,
- multiplicar,
- dividir.

La division devuelve:

- resultado,
- resto.

Si se divide entre 0, devuelve `soap:Fault`.

## Funciones reutilizables

Consulta tambien el indice general: `../README.md`. Ahi tienes las plantillas SOAP completas y comparacion con REST.

### Acceso rapido por archivo

| Archivo | Que puedes reutilizar | Para que sirve en otro ejercicio |
| --- | --- | --- |
| `calculadora-soap.php` | `DOMDocument`, `DOMXPath`, `responderSOAP()`, `responderDivisionSOAP()`, `responderFault()` | Crear un servidor SOAP que lee XML, ejecuta operaciones y responde XML. |
| `calculadora-soap.html` | Construccion de XML SOAP con `fetch()` | Crear un cliente web que envia peticiones SOAP desde JavaScript. |
| `calculadora.wsdl` | `types`, `message`, `portType`, `binding`, `service` | Documentar oficialmente las operaciones disponibles del servicio SOAP. |
| `ampliacionCalculadora.txt` | Enunciado de ampliacion | Ver que cambios se anadieron respecto al servicio inicial. |

### Partes que se copian mucho en SOAP

- Leer el XML recibido: `file_get_contents("php://input")`.
- Cargarlo como XML: `$dom->loadXML($xmlRecibido)`.
- Buscar nodos sin depender del prefijo: `//*[local-name()='sumar']`.
- Escapar texto de salida: `htmlspecialchars($texto, ENT_XML1)`.
- Devolver errores formales: `<soap:Fault>`.

### Respuesta SOAP simple

```php
function responderSOAP($operacion, $resultado) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<' . $operacion . 'Response>'
       . '<resultado>' . $resultado . '</resultado>'
       . '</' . $operacion . 'Response>'
       . '</soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

### Error SOAP

```php
function responderFault($mensaje) {
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");
    echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<soap:Fault>'
       . '<faultcode>SOAP-ENV:Client</faultcode>'
       . '<faultstring>' . $mensajeSeguro . '</faultstring>'
       . '</soap:Fault>'
       . '</soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

### Leer XML recibido

```php
$xmlRecibido = file_get_contents("php://input");
$dom = new DOMDocument();
$dom->loadXML($xmlRecibido);
```

### Buscar Body con XPath

```php
$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");
$body = $xpath->query("//soap:Body")->item(0);
```

## WSDL

El WSDL es el contrato del servicio SOAP.

Define:

- operaciones,
- parametros de entrada,
- respuestas,
- endpoint del servicio.

## Para examen

SOAP trabaja con XML; REST normalmente trabaja con JSON.

```text
SOAP = Envelope + Header + Body + WSDL
REST = rutas + metodos HTTP + JSON + OpenAPI
```
