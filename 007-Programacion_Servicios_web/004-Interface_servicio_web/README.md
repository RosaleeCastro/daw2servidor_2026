# 004 - SOAP: Interfaz del servicio web

## ⚡ Qué añade esta carpeta respecto a 003

```
003 → Header con token    → AUTENTICAR (obligatorio, si falla = Fault)
004 → Header con requestId → TRAZAR    (opcional, si no llega funciona igual)
```

> El `requestId` es como un **número de ticket**: identifica la petición  
> y el servidor lo devuelve en la respuesta para que sepas a qué petición corresponde.

---

## 🗂️ Dos ejercicios

| Ejercicio       | Qué hace                                   | Lo nuevo                                      |
| --------------- | ------------------------------------------ | --------------------------------------------- |
| `envioPostal`   | Calcula precio y plazo de envío            | Header opcional con `requestId`               |
| `acceso_examen` | Valida si un alumno puede entrar al examen | Validaciones estrictas + regla edad/matrícula |

---

## 📦 Estructura SOAP de ambos ejercicios

```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">

    <soap:Header>
        <clienteInfo>
            <aplicacion>DAW2</aplicacion>
            <requestId>REQ-1716624000000</requestId>  ← generado con Date.now()
        </clienteInfo>
    </soap:Header>

    <soap:Body>
        <!-- envioPostal -->
        <calcularEnvio>
            <peso>2.5</peso>
            <zona>peninsula</zona>
            <urgente>true</urgente>
        </calcularEnvio>

        <!-- O acceso_examen -->
        <validarAccesoExamen>
            <nombre>Ana</nombre>
            <edad>18</edad>
            <matriculado>true</matriculado>
        </validarAccesoExamen>
    </soap:Body>

</soap:Envelope>
```

---

## 🌐 1. Cliente JS — patrón común a los dos ejercicios

```javascript
// Generar ID único para cada petición
const requestId = "REQ-" + Date.now();

const xmlSOAP = `<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Header>
        <clienteInfo>
            <aplicacion>DAW2</aplicacion>
            <requestId>${requestId}</requestId>
        </clienteInfo>
    </soap:Header>
    <soap:Body>
        <calcularEnvio>
            <peso>${peso}</peso>
            <zona>${zona}</zona>
            <urgente>${urgente}</urgente>
        </calcularEnvio>
    </soap:Body>
</soap:Envelope>`;

const respuesta = await fetch("envioPostal.php", {
  method: "POST",
  headers: { "Content-Type": "text/xml; charset=utf-8", Accept: "text/xml" },
  body: xmlSOAP,
});

const texto = await respuesta.text();
const xml = new DOMParser().parseFromString(texto, "text/xml");

// Buscar nodos por local-name() → siempre más robusto con namespaces
const buscar = (nombre) =>
  xml.evaluate(
    "//*[local-name()='" + nombre + "']",
    xml,
    null,
    XPathResult.FIRST_ORDERED_NODE_TYPE,
    null,
  ).singleNodeValue;

const fault = buscar("faultstring");
const precio = buscar("precio");
const plazo = buscar("plazoDias");
const requestIdR = buscar("requestId"); // el servidor lo devuelve

if (fault) {
  console.log("Error: " + fault.textContent);
} else {
  console.log("Precio: " + precio.textContent + " €");
  console.log("Plazo: " + plazo.textContent + " días");
  console.log("RequestId: " + requestIdR.textContent);
}
```

---

## 🖥️ 2. Servidor PHP — patrón común a los dos ejercicios

```php
<?php
header("Content-Type: text/xml; charset=utf-8");

// ── Leer XML (igual que siempre) ─────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "POST") responderFault("Solo POST.");

$xmlRecibido = file_get_contents("php://input");
if (trim($xmlRecibido) === "") responderFault("No se recibió XML.");

libxml_use_internal_errors(true);
$dom = new DOMDocument();
if (!$dom->loadXML($xmlRecibido)) responderFault("XML no válido.");

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

// ── NUEVO EN 004: leer requestId del Header (OPCIONAL) ───────────
$header    = $xpath->query("//soap:Header")->item(0);
$requestId = "";                              // valor por defecto si no llega

if ($header instanceof DOMElement) {          // ← solo si existe el Header
    $node = $header->getElementsByTagName("requestId")->item(0);
    if ($node) {
        $requestId = trim($node->textContent);
    }
}
// No llamamos a responderFault() si no hay Header → es opcional

// ── Leer Body y operación (igual que siempre) ────────────────────
$body = $xpath->query("//soap:Body")->item(0);
if (!$body) responderFault("No se encontró el Body.");

$operacionNode = null;
foreach ($body->childNodes as $n) {
    if ($n instanceof DOMElement) { $operacionNode = $n; break; }
}
if (!$operacionNode) responderFault("No hay operación en el Body.");
?>
```

---

## 📮 Ejercicio 1 — Envío Postal: lógica de cálculo

```php
$peso   = (float)$operacionNode->getElementsByTagName("peso")->item(0)->textContent;
$zona   = trim($operacionNode->getElementsByTagName("zona")->item(0)->textContent);
$urgTxt = strtolower(trim($operacionNode->getElementsByTagName("urgente")->item(0)->textContent));

// Validaciones
if (!is_numeric($peso) || $peso <= 0) responderFault("Peso no válido.");
$zonasOK = ["peninsula", "baleares", "canarias", "internacional"];
if (!in_array($zona, $zonasOK, true))  responderFault("Zona no válida.");
if ($urgTxt !== "true" && $urgTxt !== "false") responderFault("urgente debe ser true o false.");

$urgente = ($urgTxt === "true");

// Precio base y plazo según zona
$tabla = [
    "peninsula"     => ["base" => 4.50,  "plazo" => 3],
    "baleares"      => ["base" => 7.00,  "plazo" => 4],
    "canarias"      => ["base" => 9.50,  "plazo" => 5],
    "internacional" => ["base" => 15.00, "plazo" => 7],
];

$precioBase = $tabla[$zona]["base"];
$plazoBase  = $tabla[$zona]["plazo"];

// Recargo por peso
if      ($peso <= 1) $recargoPeso = 0;
elseif  ($peso <= 5) $recargoPeso = 2.50;
else                 $recargoPeso = 5.00;

// Recargo urgente + reducción de plazo
$recargoUrgente = $urgente ? 6.00 : 0;
$plazoFinal     = $urgente ? max(1, $plazoBase - 2) : $plazoBase;
$precioFinal    = number_format($precioBase + $recargoPeso + $recargoUrgente, 2, ".", "");

responderEnvioSOAP($precioFinal, $plazoFinal, $zona, $urgente, $requestId);
```

### Función de respuesta — devuelve el requestId en el Header

```php
function responderEnvioSOAP($precio, $dias, $zona, $urgente, $requestId = "") {
    $rId = htmlspecialchars($requestId, ENT_XML1 | ENT_QUOTES, "UTF-8");
    $z   = htmlspecialchars($zona,      ENT_XML1 | ENT_QUOTES, "UTF-8");
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Header>'
       . '<respuestaInfo>'
       . '<servidor>EnvioPostalPHP</servidor>'
       . '<requestId>' . $rId . '</requestId>'   // ← devolvemos el mismo ID
       . '</respuestaInfo>'
       . '</soap:Header>'
       . '<soap:Body><calcularEnvioResponse>'
       . '<precio>'    . $precio              . '</precio>'
       . '<plazoDias>' . $dias                . '</plazoDias>'
       . '<zona>'      . $z                   . '</zona>'
       . '<urgente>'   . ($urgente ? 'true' : 'false') . '</urgente>'
       . '</calcularEnvioResponse></soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

---

## 🎓 Ejercicio 2 — Acceso Examen: validaciones y regla de negocio

```php
$nombre       = trim($operacionNode->getElementsByTagName("nombre")->item(0)->textContent);
$edadTexto    = trim($operacionNode->getElementsByTagName("edad")->item(0)->textContent);
$matricTxt    = strtolower(trim(
    $operacionNode->getElementsByTagName("matriculado")->item(0)->textContent
));

// Validar cada campo antes de usarlo
if ($nombre === "")                              responderFault("Nombre vacío.");
if (!is_numeric($edadTexto))                     responderFault("Edad no numérica.");
$edad = (int)$edadTexto;
if ($edad < 0)                                   responderFault("Edad negativa.");
if ($matricTxt !== "true" && $matricTxt !== "false") responderFault("matriculado: true o false.");

$matriculado = ($matricTxt === "true");

// ── Regla de negocio: edad > 16 AND matriculado ──────────────────
if ($edad <= 16 && !$matriculado) {
    responderAccesoSOAP(false, "$nombre: necesita más de 16 años y estar matriculado.", $requestId);
}
if ($edad <= 16) {
    responderAccesoSOAP(false, "$nombre: necesita más de 16 años.", $requestId);
}
if (!$matriculado) {
    responderAccesoSOAP(false, "$nombre: no está matriculado.", $requestId);
}
responderAccesoSOAP(true, "$nombre puede acceder al examen.", $requestId);
```

### Función de respuesta

```php
function responderAccesoSOAP($permitido, $mensaje, $requestId = "") {
    $p   = $permitido ? "true" : "false";
    $m   = htmlspecialchars($mensaje,   ENT_XML1 | ENT_QUOTES, "UTF-8");
    $rId = htmlspecialchars($requestId, ENT_XML1 | ENT_QUOTES, "UTF-8");
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Header><respuestaInfo>'
       . '<requestId>' . $rId . '</requestId>'
       . '</respuestaInfo></soap:Header>'
       . '<soap:Body><validarAccesoExamenResponse>'
       . '<permitido>' . $p . '</permitido>'
       . '<mensaje>'   . $m . '</mensaje>'
       . '</validarAccesoExamenResponse></soap:Body>'
       . '</soap:Envelope>';
    exit;
}
```

---

## 📄 WSDL — qué tiene de especial en 004

El WSDL de acceso_examen declara el Header como **opcional** en el input:

```xml
<!-- types: estructura del Header -->
<xsd:element name="clienteInfo">
    <xsd:complexType>
        <xsd:sequence>
            <xsd:element name="aplicacion" type="xsd:string"/>
            <xsd:element name="requestId"  type="xsd:string"/>
        </xsd:sequence>
    </xsd:complexType>
</xsd:element>

<!-- message: nombrar el Header -->
<message name="ClienteInfoHeader">
    <part name="clienteInfo" element="tns:clienteInfo"/>
</message>

<!-- binding: añadir soap:header al input -->
<operation name="validarAccesoExamen">
    <input>
        <soap:header message="tns:ClienteInfoHeader" part="clienteInfo" use="literal"/>
        <soap:body use="literal"/>
    </input>
    <output>
        <soap:body use="literal"/>
    </output>
</operation>
```

---

## ⚔️ Comparativa final de las 3 carpetas SOAP

|                    | 002 Calculadora | 003 Préstamos          | 004 Envío/Acceso         |
| ------------------ | --------------- | ---------------------- | ------------------------ |
| Header             | ❌ No           | ✅ Token (obligatorio) | ✅ requestId (opcional)  |
| ¿Falla sin Header? | —               | ✅ Sí → Fault          | ❌ No, sigue             |
| ¿Devuelve Header?  | ❌ No           | ❌ No                  | ✅ Sí, con requestId     |
| Campos respuesta   | 1 `resultado`   | 3 campos               | 2-4 campos               |
| Lógica             | Matemática      | Arrays fijos           | Cálculo / Regla booleana |

---

## 🧠 Resumen mental para el examen

| Pregunta                                       | Respuesta                                               |
| ---------------------------------------------- | ------------------------------------------------------- |
| ¿Cómo genero un requestId en JS?               | `"REQ-" + Date.now()`                                   |
| ¿Cómo leo el Header sin que falle si no llega? | `if ($header instanceof DOMElement)`                    |
| ¿Cómo valido un booleano SOAP?                 | `strtolower($texto) === "true"` / `=== "false"`         |
| ¿Cómo devuelvo el requestId al cliente?        | En `<soap:Header>` de la respuesta                      |
| ¿Diferencia Header 003 vs 004?                 | 003 autentica (falla si no llega), 004 traza (opcional) |
| Regla acceso examen                            | `edad > 16 AND matriculado === true`                    |
