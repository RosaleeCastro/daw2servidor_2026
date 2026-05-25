# 002 - SOAP + WSDL: Calculadora

## ⚡ Regla de oro

```
HTML construye XML → fetch(text/xml) → PHP parsea XML → PHP responde XML
```

> SOAP **no usa JSON**. Todo es XML. El error tampoco es JSON, es `soap:Fault`.

---

## 📦 La estructura SOAP — el "sobre de correos"

```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">

    <soap:Header>          ← OPCIONAL: token, sesión, requestId
    </soap:Header>

    <soap:Body>            ← OBLIGATORIO: la operación real
        <sumar>
            <a>8</a>
            <b>3</b>
        </sumar>
    </soap:Body>

</soap:Envelope>
```

| Parte      | Obligatorio | Para qué                 |
| ---------- | ----------- | ------------------------ |
| `Envelope` | ✅ Sí       | Contenedor de todo       |
| `Header`   | ❌ No       | Token, sesión, metadatos |
| `Body`     | ✅ Sí       | La operación y sus datos |

---

## 🗂️ Archivos del proyecto

```
calculadora-soap.html   ← cliente: construye XML y hace fetch
calculadora-soap.php    ← servidor: parsea XML y responde XML
calculadora.wsdl        ← contrato: describe qué hace el servicio
```

---

## 🌐 1. Cliente HTML — construir y enviar SOAP

```javascript
const operacion = "sumar"; // viene de un <select>
const a = 8,
  b = 3;

// 1. Construir el XML SOAP como string
const xmlSOAP = `<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <${operacion}>
            <a>${a}</a>
            <b>${b}</b>
        </${operacion}>
    </soap:Body>
</soap:Envelope>`;

// 2. Enviar — Content-Type: text/xml (NO application/json)
const respuesta = await fetch("calculadora-soap.php", {
  method: "POST",
  headers: {
    "Content-Type": "text/xml; charset=utf-8",
    Accept: "text/xml",
  },
  body: xmlSOAP, // string XML, NO JSON.stringify()
});

// 3. Leer como texto (NO .json())
const texto = await respuesta.text();

// 4. Parsear el XML de la respuesta
const parser = new DOMParser();
const xml = parser.parseFromString(texto, "text/xml");

// 5a. Respuesta normal → buscar <resultado>
const nodoResultado = xml.getElementsByTagName("resultado")[0];

// 5b. División → buscar también <resto>
const nodoResto = xml.getElementsByTagName("resto")[0];

// 5c. Error → buscar <faultstring>
const nodoFault = xml.getElementsByTagName("faultstring")[0];

if (nodoFault) {
  console.log("Error SOAP: " + nodoFault.textContent);
} else if (nodoResultado && nodoResto) {
  console.log("Resultado: " + nodoResultado.textContent);
  console.log("Resto: " + nodoResto.textContent);
} else if (nodoResultado) {
  console.log("Resultado: " + nodoResultado.textContent);
}
```

---

## 🖥️ 2. Servidor PHP — parsear SOAP y responder

### Paso a paso completo

```php
<?php
header("Content-Type: text/xml; charset=utf-8");  // ← siempre XML

// PASO 1 — leer el XML crudo
$xmlRecibido = file_get_contents("php://input");
if (trim($xmlRecibido) === "") responderFault("No se recibió XML.");

// PASO 2 — cargar como documento navegable
libxml_use_internal_errors(true);   // evita warnings en pantalla
$dom = new DOMDocument();
if (!$dom->loadXML($xmlRecibido)) responderFault("XML no válido.");

// PASO 3 — crear buscador XPath y registrar namespace soap
$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

// PASO 4 — encontrar el Body
$body = $xpath->query("//soap:Body")->item(0);
if (!$body) responderFault("No se encontró el Body.");

// PASO 5 — leer la operación (primer elemento dentro del Body)
$operacionNode = null;
foreach ($body->childNodes as $nodo) {
    if ($nodo instanceof DOMElement) { $operacionNode = $nodo; break; }
}
if (!$operacionNode) responderFault("No hay operación en el Body.");

$operacion = $operacionNode->localName;  // "sumar", "restar"...

// PASO 6 — leer parámetros
$a = (float)$operacionNode->getElementsByTagName("a")->item(0)->textContent;
$b = (float)$operacionNode->getElementsByTagName("b")->item(0)->textContent;

// PASO 7 — ejecutar y responder
switch ($operacion) {
    case "sumar":       responderSOAP("sumar",       $a + $b);
    case "restar":      responderSOAP("restar",      $a - $b);
    case "multiplicar": responderSOAP("multiplicar", $a * $b);
    case "dividir":
        if ($b == 0) responderFault("No se puede dividir entre 0.");
        responderDivisionSOAP($a / $b, fmod($a, $b));
        break;
    default: responderFault("Operación '$operacion' no soportada.");
}
?>
```

---

## 🔧 3. Funciones reutilizables PHP (copiar en cualquier ejercicio SOAP)

### Respuesta normal (un resultado)

```php
function responderSOAP($operacion, $resultado) {
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<' . $operacion . 'Response>'
       . '<resultado>' . $resultado . '</resultado>'
       . '</' . $operacion . 'Response>'
       . '</soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

### Respuesta con dos campos (división)

```php
function responderDivisionSOAP($resultado, $resto) {
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<dividirResponse>'
       . '<resultado>' . $resultado . '</resultado>'
       . '<resto>'     . $resto     . '</resto>'
       . '</dividirResponse>'
       . '</soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

### Error SOAP (el "ok:false" de SOAP)

```php
function responderFault($mensaje) {
    // htmlspecialchars evita que caracteres como < > rompan el XML
    $seguro = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<soap:Fault>'
       . '<faultcode>SOAP-ENV:Client</faultcode>'
       . '<faultstring>' . $seguro . '</faultstring>'
       . '</soap:Fault>'
       . '</soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

---

## 📄 4. WSDL — el contrato del servicio

> El WSDL **no ejecuta nada**. Es como el menú de un restaurante: describe qué puedes pedir, qué ingredientes lleva y qué te traen.

### Sus 5 partes (de arriba a abajo)

```xml
<definitions ...>

  <!-- 1. TYPES: define la forma del XML (qué campos hay y de qué tipo) -->
  <types>
    <xsd:element name="sumar">
      <xsd:complexType>
        <xsd:sequence>
          <xsd:element name="a" type="xsd:double"/>
          <xsd:element name="b" type="xsd:double"/>
        </xsd:sequence>
      </xsd:complexType>
    </xsd:element>
    <xsd:element name="sumarResponse">
      <xsd:complexType>
        <xsd:sequence>
          <xsd:element name="resultado" type="xsd:double"/>
        </xsd:sequence>
      </xsd:complexType>
    </xsd:element>
  </types>

  <!-- 2. MESSAGE: nombra los mensajes de entrada y salida -->
  <message name="sumarRequest">
    <part name="parameters" element="tns:sumar"/>
  </message>
  <message name="sumarResponse">
    <part name="parameters" element="tns:sumarResponse"/>
  </message>

  <!-- 3. PORT TYPE: lista las operaciones disponibles -->
  <portType name="CalculadoraPortType">
    <operation name="sumar">
      <input  message="tns:sumarRequest"/>
      <output message="tns:sumarResponse"/>
    </operation>
  </portType>

  <!-- 4. BINDING: dice cómo se accede (SOAP sobre HTTP) -->
  <binding name="CalculadoraBinding" type="tns:CalculadoraPortType">
    <soap:binding style="document" transport="http://schemas.xmlsoap.org/soap/http"/>
    <operation name="sumar">
      <soap:operation soapAction="urn:Calculadora#sumar"/>
      <input>  <soap:body use="literal"/> </input>
      <output> <soap:body use="literal"/> </output>
    </operation>
  </binding>

  <!-- 5. SERVICE: URL donde está publicado el PHP -->
  <service name="CalculadoraService">
    <port name="CalculadoraPort" binding="tns:CalculadoraBinding">
      <soap:address location="http://localhost/calculadora-soap.php"/>
    </port>
  </service>

</definitions>
```

### Resumen de las 5 partes

| Parte      | Qué define                        | Analogía                    |
| ---------- | --------------------------------- | --------------------------- |
| `types`    | Forma del XML (campos y tipos)    | Formulario en blanco        |
| `message`  | Nombre del mensaje entrada/salida | Cómo se llama el formulario |
| `portType` | Qué operaciones existen           | Lista de servicios del menú |
| `binding`  | Cómo se envía (SOAP/HTTP)         | Forma de hacer el pedido    |
| `service`  | URL del servidor                  | Dirección del restaurante   |

---

## ⚔️ SOAP vs REST — diferencias clave para el examen

|                   | SOAP                     | REST (001)         |
| ----------------- | ------------------------ | ------------------ |
| Formato datos     | XML                      | JSON               |
| `Content-Type`    | `text/xml`               | `application/json` |
| Leer respuesta JS | `.text()` + `DOMParser`  | `.json()`          |
| Error             | `<soap:Fault>`           | `{"ok": false}`    |
| Contrato          | WSDL (XML)               | OpenAPI (YAML)     |
| Estructura fija   | ✅ Siempre Envelope/Body | ❌ Libre           |
| Cabecera extra    | `soap:Header`            | No existe          |

---

## 🧠 Resumen mental para el examen

| Pregunta                         | Respuesta                                              |
| -------------------------------- | ------------------------------------------------------ |
| ¿Cómo lee PHP el XML recibido?   | `file_get_contents("php://input")` + `$dom->loadXML()` |
| ¿Cómo busco el Body?             | `$xpath->query("//soap:Body")->item(0)`                |
| ¿Cómo sé qué operación pidieron? | `$operacionNode->localName`                            |
| ¿Cómo leo un parámetro?          | `->getElementsByTagName("a")->item(0)->textContent`    |
| ¿Cómo respondo un error?         | `responderFault("mensaje")` → `<soap:Fault>`           |
| ¿Por qué uso `htmlspecialchars`? | Para que caracteres como `<` no rompan el XML          |
| ¿Qué hace el WSDL?               | Describe el servicio, no lo ejecuta                    |
| ¿Cuándo uso Header?              | Cuando hay token, sesión o metadatos extra             |
