# 🧠 Chuleta Global — Servicios Web

## ⚡ La regla de oro de todo el tema

```
HTML nunca toca MySQL directamente.
HTML → fetch() → PHP → (MySQL o lógica) → respuesta → HTML muestra
```

---

## 🗺️ Mapa del tema completo

| Carpeta | Tecnología                        | Qué hace                                    |
| ------- | --------------------------------- | ------------------------------------------- |
| `001`   | PHP + MySQL + JSON                | Servicios simples: catálogo, stock, pedidos |
| `002`   | SOAP + WSDL                       | Calculadora XML sin Header                  |
| `003`   | SOAP + WSDL + Header sesión       | Préstamos con token obligatorio             |
| `004`   | SOAP + WSDL + Header trazabilidad | Envío postal y acceso examen                |
| `005`   | REST + JSON + MySQL + YAML        | APIs completas con CRUD                     |

---

## 🔀 ¿Cuándo usar qué?

| El enunciado dice               | Usa                                        |
| ------------------------------- | ------------------------------------------ |
| "servicio PHP + JSON + MySQL"   | 001                                        |
| "SOAP" / "XML" / "WSDL"         | 002-004                                    |
| "cabecera de sesión" / "token"  | 003                                        |
| "requestId" / "trazar petición" | 004                                        |
| "API REST" / "endpoints"        | 005                                        |
| "documenta la API" / "OpenAPI"  | YAML (005)                                 |
| "sin base de datos"             | JSON local (005 api_libros / gestorTareas) |

---

## 📦 Estructura de mensajes — REST vs SOAP

### REST

```
HTTP POST /servicio_stock.php
Content-Type: application/json

{ "id_producto": 1 }
```

### SOAP

```
HTTP POST /calculadora-soap.php
Content-Type: text/xml

<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Header>          ← opcional
        <token>ABC123</token>
    </soap:Header>
    <soap:Body>            ← obligatorio
        <sumar>
            <a>8</a>
            <b>3</b>
        </sumar>
    </soap:Body>
</soap:Envelope>
```

---

## 🔧 Funciones PHP reutilizables — dónde van

```
╔══════════════════════════╦══════════╦══════════╗
║ Función                  ║ REST     ║ SOAP     ║
╠══════════════════════════╬══════════╬══════════╣
║ header("Content-Type")   ║ JSON ✅  ║ XML  ✅  ║
║ file_get_contents(input) ║ ✅       ║ ✅       ║
║ responder()              ║ ✅       ║ ❌       ║
║ leerJSONBody()           ║ ✅       ║ ❌       ║
║ detectar método + ruta   ║ ✅       ║ ⚠️ solo POST ║
║ responderSOAP()          ║ ❌       ║ ✅       ║
║ responderFault()         ║ ❌       ║ ✅       ║
║ DOMDocument + DOMXPath   ║ ❌       ║ ✅       ║
║ json_decode()            ║ ✅       ║ ❌       ║
║ loadXML()                ║ ❌       ║ ✅       ║
╚══════════════════════════╩══════════╩══════════╝
```

> Lo único común a REST y SOAP es `file_get_contents("php://input")`.  
> La diferencia está en qué haces con lo que recibes:
>
> - REST → `json_decode()`
> - SOAP → `$dom->loadXML()`

---

## 🔧 Bloque PHP — funciones comunes REST (copiar siempre)

```php
<?php
header("Content-Type: application/json; charset=utf-8");

// ── Responder JSON con código HTTP ───────────────────────────────
function responder($codigo, $datos = null) {
    http_response_code($codigo);
    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ── Leer JSON del cliente ────────────────────────────────────────
function leerJSONBody() {
    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if ($raw !== "" && $data === null) responder(400, ["error" => "JSON inválido"]);
    return $data ?? [];
}

// ── Detectar método HTTP y ruta ──────────────────────────────────
$metodo  = $_SERVER["REQUEST_METHOD"];
$ruta    = $_SERVER["PATH_INFO"] ?? ($_GET["ruta"] ?? ""); // ← compatible XAMPP
$partes  = explode("/", trim($ruta, "/"));
$recurso = $partes[0] ?? "";   // "libros", "tareas"...
$id      = $partes[1] ?? null; // "1" o null
```

---

## 🔧 Bloque PHP — funciones comunes SOAP (copiar siempre)

```php
<?php
header("Content-Type: text/xml; charset=utf-8");

// ── Responder error SOAP ─────────────────────────────────────────
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

// ── Leer y parsear el XML recibido ───────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "POST") responderFault("Solo POST.");

$xmlRecibido = file_get_contents("php://input"); // ← igual que REST
if (trim($xmlRecibido) === "") responderFault("No se recibió XML.");

libxml_use_internal_errors(true);
$dom = new DOMDocument();
if (!$dom->loadXML($xmlRecibido)) responderFault("XML no válido."); // ← distinto a REST

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

// ── Leer Header (opcional — 004) o obligatorio (003) ────────────
$header    = $xpath->query("//soap:Header")->item(0);
$requestId = "";
if ($header instanceof DOMElement) {
    $node = $header->getElementsByTagName("requestId")->item(0);
    if ($node) $requestId = trim($node->textContent);
}

// ── Leer Body ────────────────────────────────────────────────────
$body = $xpath->query("//soap:Body")->item(0);
if (!$body) responderFault("No se encontró el Body.");

$operacionNode = null;
foreach ($body->childNodes as $n) {
    if ($n instanceof DOMElement) { $operacionNode = $n; break; }
}
if (!$operacionNode) responderFault("No hay operación en el Body.");

$operacion = $operacionNode->localName; // "sumar", "consultarPrestamo"...
```

---

## 🔧 Bloque PHP — conexión PDO (REST con MySQL)

```php
function obtenerPDO() {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=mi_base;charset=utf8mb4",
        "root", ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// Buscar por ID — evita repetir el SELECT en GET, PATCH y DELETE
function buscarPorId($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM tabla WHERE id = :id");
    $stmt->execute([":id" => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// PATCH dinámico — modifica solo los campos enviados
$campos = [];
$params = [":id" => (int)$id];

if (isset($data["nombre"])) {
    $campos[]          = "nombre = :nombre";
    $params[":nombre"]  = trim($data["nombre"]);
}
// ... más campos

if (empty($campos)) responder(400, ["error" => "Sin campos válidos"]);

$pdo->prepare("UPDATE tabla SET " . implode(", ", $campos) . " WHERE id = :id")
    ->execute($params);
```

---

## 🌐 Bloque JS — fetch para REST

```javascript
// GET — sin body
const resp = await fetch("api.php/recursos");
const data = await resp.json();

// POST — con body
const resp = await fetch("api.php/recursos", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ nombre: "Nuevo" }),
});

// PATCH — solo campos que cambian
const resp = await fetch("api.php/recursos/1", {
  method: "PATCH",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ nombre: "Actualizado" }),
});

// DELETE — sin body
const resp = await fetch("api.php/recursos/1", { method: "DELETE" });
if (resp.status !== 204) {
  const data = await resp.json();
}

// Filtros con URLSearchParams
const params = new URLSearchParams();
params.append("filtro", "valor");
const resp = await fetch("api.php/recursos?" + params.toString());
```

---

## 🌐 Bloque JS — fetch para SOAP

```javascript
// Construir XML SOAP
const xmlSOAP = `<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Header>
        <clienteInfo>
            <requestId>REQ-${Date.now()}</requestId>
        </clienteInfo>
    </soap:Header>
    <soap:Body>
        <miOperacion>
            <param1>${valor1}</param1>
            <param2>${valor2}</param2>
        </miOperacion>
    </soap:Body>
</soap:Envelope>`;

// Enviar — text/xml, NO application/json
const resp = await fetch("servicio.php", {
  method: "POST",
  headers: { "Content-Type": "text/xml; charset=utf-8", Accept: "text/xml" },
  body: xmlSOAP,
});

// Leer como texto, NO .json()
const texto = await resp.text();
const xml = new DOMParser().parseFromString(texto, "text/xml");

// Opción A — simple (tus ejercicios XAMPP sin prefijos)
const resultado = xml.getElementsByTagName("resultado")[0];
const fault = xml.getElementsByTagName("faultstring")[0];

// Opción B — robusta (si el XML tiene namespaces externos)
const buscar = (n) =>
  xml.evaluate(
    "//*[local-name()='" + n + "']",
    xml,
    null,
    XPathResult.FIRST_ORDERED_NODE_TYPE,
    null,
  ).singleNodeValue;

if (fault) {
  console.log("Error: " + fault.textContent);
} else {
  console.log("Resultado: " + resultado.textContent);
}
```

---

## 📋 Códigos HTTP REST

| Código | Cuándo                                  |
| ------ | --------------------------------------- |
| `200`  | OK — consulta o actualización correcta  |
| `201`  | Created — recurso creado (POST)         |
| `204`  | No Content — borrado correcto, sin body |
| `400`  | Bad Request — datos inválidos           |
| `404`  | Not Found — no existe                   |
| `405`  | Method Not Allowed                      |
| `409`  | Conflict — no se puede borrar (FK)      |
| `500`  | Internal Server Error                   |

---

## ⚔️ SOAP vs REST — tabla definitiva

|                   | SOAP (002-004)           | REST (001 y 005)      |
| ----------------- | ------------------------ | --------------------- |
| Formato           | XML                      | JSON                  |
| `Content-Type`    | `text/xml`               | `application/json`    |
| Leer respuesta JS | `.text()` + `DOMParser`  | `.json()`             |
| Error             | `<soap:Fault>`           | `{"error": "..."}`    |
| Contrato          | WSDL (XML)               | OpenAPI (YAML)        |
| Estructura fija   | ✅ Envelope/Header/Body  | ❌ Libre              |
| Operación         | Dentro del Body XML      | URL + método HTTP     |
| Leer en PHP       | `loadXML()` + `DOMXPath` | `json_decode()`       |
| Función error PHP | `responderFault()`       | `responder(400, ...)` |

---

## 📄 WSDL vs OpenAPI YAML

|                  | WSDL                           | OpenAPI YAML              |
| ---------------- | ------------------------------ | ------------------------- |
| Para qué         | Documentar SOAP                | Documentar REST           |
| Formato          | XML                            | YAML                      |
| ¿Ejecuta código? | ❌ No                          | ❌ No                     |
| Lo usan          | SoapUI, Postman                | Swagger, Postman          |
| Describe         | operaciones + tipos + endpoint | rutas + métodos + schemas |

---

## 🧠 Preguntas rápidas de examen

| Pregunta                               | Respuesta                                                    |
| -------------------------------------- | ------------------------------------------------------------ |
| ¿Cómo lee PHP lo que manda el cliente? | `file_get_contents("php://input")` — REST y SOAP             |
| ¿Cómo parsea REST ese input?           | `json_decode($raw, true)`                                    |
| ¿Cómo parsea SOAP ese input?           | `$dom->loadXML($raw)`                                        |
| ¿Cómo sabe PHP el método HTTP?         | `$_SERVER["REQUEST_METHOD"]`                                 |
| ¿Cómo sabe PHP la ruta REST?           | `$_SERVER["PATH_INFO"]`                                      |
| ¿Cómo evito SQL injection?             | `prepare()` + `execute([":param" => $valor])`                |
| ¿Cuándo uso transacción?               | Cuando dos operaciones deben ir juntas o ninguna             |
| ¿Qué hace `FOR UPDATE`?                | Bloquea filas para evitar concurrencia                       |
| ¿Qué devuelve el DELETE correcto?      | `204` sin body                                               |
| ¿Qué pasa si no puedo borrar por FK?   | `PDOException` → `responder(409, ...)`                       |
| ¿Qué hace `htmlspecialchars` en SOAP?  | Evita que `<` `>` `&` rompan el XML                          |
| ¿Cuándo uso Header SOAP?               | Token (003) o requestId (004)                                |
| ¿Diferencia token vs requestId?        | Token autentica (Fault si falla), requestId traza (opcional) |
| ¿Qué es el WSDL?                       | Contrato SOAP — describe, no ejecuta                         |
| ¿Qué es el YAML?                       | Contrato REST — describe, no ejecuta                         |
