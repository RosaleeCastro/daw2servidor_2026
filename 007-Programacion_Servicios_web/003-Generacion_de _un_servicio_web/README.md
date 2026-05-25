# 003 - SOAP con Header de sesión: Préstamos

## ⚡ Qué añade esta carpeta respecto a 002

```
002 → Envelope [ Body ]
003 → Envelope [ Header(token) + Body ]
```

> El servidor **primero valida el token** del Header.  
> Si falla → `Fault` inmediato. No llega ni a leer el Body.

---

## 📦 Estructura SOAP con Header

```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">

    <soap:Header>                  ← NUEVO en 003
        <sesion>
            <token>ABC123</token>  ← el servidor lo valida primero
        </sesion>
    </soap:Header>

    <soap:Body>
        <consultarPrestamo>
            <dni>12345678A</dni>
            <codigoLibro>LIB001</codigoLibro>
        </consultarPrestamo>
    </soap:Body>

</soap:Envelope>
```

---

## 🗂️ Archivos del proyecto

```
prestamos.html    ← cliente: construye XML con Header + Body
prestamos.php     ← servidor: valida token, aplica reglas, responde XML
prestamos.wsdl    ← contrato: declara el Header como parte del servicio
```

---

## 🌐 1. Cliente HTML — construir SOAP con Header

```javascript
const token = "ABC123";
const dni = "12345678A";
const codigoLibro = "LIB001";

// Diferencia con 002: añadimos <soap:Header> antes del Body
const xmlSOAP = `<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Header>
        <sesion>
            <token>${token}</token>
        </sesion>
    </soap:Header>
    <soap:Body>
        <consultarPrestamo>
            <dni>${dni}</dni>
            <codigoLibro>${codigoLibro}</codigoLibro>
        </consultarPrestamo>
    </soap:Body>
</soap:Envelope>`;

// El fetch es idéntico a 002
const respuesta = await fetch("prestamos.php", {
  method: "POST",
  headers: {
    "Content-Type": "text/xml; charset=utf-8",
    Accept: "text/xml",
  },
  body: xmlSOAP,
});

const texto = await respuesta.text();
const xml = new DOMParser().parseFromString(texto, "text/xml");

// Buscar nodos por local-name() → más robusto que getElementsByTagName
// cuando hay namespaces en la respuesta
const buscar = (nombre) =>
  xml.evaluate(
    "//*[local-name()='" + nombre + "']",
    xml,
    null,
    XPathResult.FIRST_ORDERED_NODE_TYPE,
    null,
  ).singleNodeValue;

const fault = buscar("faultstring");
const puedePrestar = buscar("puede_prestar");
const mensaje = buscar("mensaje");
const dias = buscar("dias_maximos");

if (fault) {
  console.log("Error SOAP: " + fault.textContent);
} else {
  console.log("Puede prestar: " + puedePrestar.textContent); // "true" / "false"
  console.log("Mensaje: " + mensaje.textContent);
  console.log("Días: " + dias.textContent);
}
```

> **`//*[local-name()='nombre']`** busca el nodo sin importar el prefijo XML.  
> Más fiable que `getElementsByTagName` cuando hay namespaces.

---

## 🖥️ 2. Servidor PHP — orden de validación

```
1. ¿Es POST?           → si no → Fault
2. ¿Llega XML?         → si no → Fault
3. ¿XML válido?        → si no → Fault
4. ¿Existe Header?     → si no → Fault   ← NUEVO en 003
5. ¿Existe token?      → si no → Fault   ← NUEVO en 003
6. ¿Token correcto?    → si no → Fault   ← NUEVO en 003
7. ¿Existe Body?       → si no → Fault
8. ¿Operación válida?  → si no → Fault
9. ¿Parámetros OK?     → si no → Fault
10. Ejecutar lógica → responder
```

```php
<?php
header("Content-Type: text/xml; charset=utf-8");

const TOKEN_VALIDO = "ABC123"; // en producción vendría de BD / JWT / sesión

// ── Funciones ────────────────────────────────────────────────────

function responderFault($mensaje) {
    $s = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body><soap:Fault>'
       . '<faultcode>SOAP-ENV:Client</faultcode>'
       . '<faultstring>' . $s . '</faultstring>'
       . '</soap:Fault></soap:Body></soap:Envelope>';
    exit;
}

// Respuesta con TRES campos (distinto a la calculadora que solo devolvía uno)
function responderPrestamoSOAP($puedePrestar, $mensaje, $dias) {
    $p = $puedePrestar ? "true" : "false";
    $m = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
       . '<soap:Body>'
       . '<consultarPrestamoResponse>'
       . '<puede_prestar>' . $p        . '</puede_prestar>'
       . '<mensaje>'       . $m        . '</mensaje>'
       . '<dias_maximos>'  . (int)$dias . '</dias_maximos>'
       . '</consultarPrestamoResponse>'
       . '</soap:Body></soap:Envelope>';
    exit;
}

// ── Recibir y parsear XML (igual que 002) ────────────────────────

if ($_SERVER["REQUEST_METHOD"] !== "POST") responderFault("Solo POST.");

$xmlRecibido = file_get_contents("php://input");
if (trim($xmlRecibido) === "") responderFault("No se recibió XML.");

libxml_use_internal_errors(true);
$dom = new DOMDocument();
if (!$dom->loadXML($xmlRecibido)) responderFault("XML no válido.");

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

// ── NUEVO EN 003: validar el Header ─────────────────────────────

$header = $xpath->query("//soap:Header")->item(0);
if (!$header) responderFault("Falta el Header con sesión.");

// Busca <token> dentro del Header sin importar su namespace
$tokenNode = $xpath->query(".//*[local-name()='token']", $header)->item(0);
if (!$tokenNode) responderFault("Falta el token.");

if (trim($tokenNode->textContent) !== TOKEN_VALIDO) {
    responderFault("Token no válido.");
}

// ── Leer Body (igual que 002) ────────────────────────────────────

$body = $xpath->query("//soap:Body")->item(0);
if (!$body) responderFault("No se encontró el Body.");

$operacionNode = null;
foreach ($body->childNodes as $n) {
    if ($n instanceof DOMElement) { $operacionNode = $n; break; }
}
if (!$operacionNode) responderFault("No hay operación en el Body.");

if ($operacionNode->localName !== "consultarPrestamo") {
    responderFault("Operación no soportada.");
}

// ── Leer parámetros ──────────────────────────────────────────────

$dni         = strtoupper(trim(
    $operacionNode->getElementsByTagName("dni")->item(0)->textContent
));
$codigoLibro = strtoupper(trim(
    $operacionNode->getElementsByTagName("codigoLibro")->item(0)->textContent
));

if ($dni === "" || $codigoLibro === "") responderFault("Faltan parámetros.");

// ── Lógica de negocio ────────────────────────────────────────────

$sancionados = ["00000000X", "11111111A"];

$libros = [
    "LIB001" => ["titulo" => "Introducción a SOAP", "prestable" => true,  "dias" => 15],
    "LIB002" => ["titulo" => "PHP y servicios web", "prestable" => true,  "dias" => 10],
    "REF001" => ["titulo" => "Enciclopedia",         "prestable" => false, "dias" => 0 ],
];

if (in_array($dni, $sancionados, true)) {
    responderPrestamoSOAP(false, "Usuario sancionado.", 0);
}
if (!isset($libros[$codigoLibro])) {
    responderPrestamoSOAP(false, "Código de libro no existe.", 0);
}
if (!$libros[$codigoLibro]["prestable"]) {
    responderPrestamoSOAP(false, "Solo consulta en sala.", 0);
}

responderPrestamoSOAP(
    true,
    "Préstamo autorizado: " . $libros[$codigoLibro]["titulo"],
    $libros[$codigoLibro]["dias"]
);
?>
```

---

## 📄 3. WSDL — qué cambia respecto a 002

Solo hay **tres diferencias** en el WSDL cuando hay Header:

```xml
<!-- DIFERENCIA 1: declarar la estructura del Header en types -->
<xsd:element name="sesion">
    <xsd:complexType>
        <xsd:sequence>
            <xsd:element name="token" type="xsd:string"/>
        </xsd:sequence>
    </xsd:complexType>
</xsd:element>

<!-- DIFERENCIA 2: crear un message para el Header -->
<message name="SesionHeader">
    <part name="sesion" element="tns:sesion"/>
</message>

<!-- DIFERENCIA 3: añadir soap:header dentro del input del binding -->
<operation name="consultarPrestamo">
    <input>
        <soap:header message="tns:SesionHeader" part="sesion" use="literal"/>
        <soap:body use="literal"/>
    </input>
    <output>
        <soap:body use="literal"/>
    </output>
</operation>
```

> Todo lo demás del WSDL es igual que en 002.

---

## ⚔️ 002 vs 003 — diferencias clave

|                   | 002 Calculadora        | 003 Préstamos                           |
| ----------------- | ---------------------- | --------------------------------------- |
| Header            | ❌ No                  | ✅ Sí → `<sesion><token>`               |
| Validación token  | ❌ No                  | ✅ Primero que nada                     |
| Campos respuesta  | 1 → `resultado`        | 3 → `puede_prestar`, `mensaje`, `dias`  |
| Lógica de negocio | Matemática pura        | Reglas con arrays (sancionados, libros) |
| WSDL Header       | ❌ No                  | ✅ `soap:header` en binding/input       |
| Buscar nodo JS    | `getElementsByTagName` | `evaluate("//*[local-name()=...]")`     |

---

## 🧠 Resumen mental para el examen

| Pregunta                               | Respuesta                                                       |
| -------------------------------------- | --------------------------------------------------------------- |
| ¿Cuándo uso Header?                    | Cuando hay token, sesión o datos de control                     |
| ¿Cómo leo el token en PHP?             | `$xpath->query(".//*[local-name()='token']", $header)->item(0)` |
| ¿Qué pasa si el token es incorrecto?   | `responderFault()` → fin, no se lee el Body                     |
| ¿Cómo respondo varios campos?          | Una función con tantos `<campo>valor</campo>` como necesite     |
| ¿Cómo busco nodos en JS con namespace? | `evaluate("//*[local-name()='nombre']", ...)`                   |
| ¿Qué añade el WSDL para el Header?     | `types` + `message` + `soap:header` en binding                  |
| ¿El token es seguro hardcodeado?       | No, en producción va en BD / JWT / sesión PHP                   |
