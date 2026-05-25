# Guia de examen - Programacion de servicios web

Este README es el indice rapido de todo el tema `007-Programacion_Servicios_web`.

Sirve para encontrar rapido:

- que hace cada carpeta,
- que tecnologia usa,
- que archivo contiene cada patron reutilizable,
- que funciones puedes copiar en otros ejercicios,
- que estructura usar si el examen pide SOAP, REST, JSON, MySQL, YAML u OpenAPI.

## Mapa rapido

| Carpeta | Tema | Estructura | Herramientas |
| --- | --- | --- | --- |
| `001-Tecnologias_protocolos_implicaciones` | Servicios PHP + JSON + MySQL | HTML -> fetch -> PHP -> MySQL -> JSON | HTML, JS, PHP, PDO, MySQL |
| `002-Estandares_Arquitectura_actuales_formato_intercambio_Datos` | SOAP calculadora | SOAP XML + WSDL | XML, SOAP, WSDL, PHP |
| `003-Generacion_de _un_servicio_web` | SOAP con cabecera de sesion | SOAP Header + Body | XML, SOAP, WSDL, PHP |
| `004-Interface_servicio_web` | Interfaces SOAP | WSDL + PHP + cliente | XML, SOAP, WSDL |
| `005-Consumo_Servicio_web_Herramientas_Prueba` | APIs REST y OpenAPI | REST + JSON + YAML | PHP, JS, JSON, OpenAPI |

## Bloques de estudio rapido

Esta seccion esta pensada para estudiar sin perderte entre tecnologias.

### Bloque SOAP completo

Usa este bloque cuando el examen diga:

- SOAP,
- XML,
- WSDL,
- `soap:Envelope`,
- `soap:Header`,
- `soap:Body`,
- `soap:Fault`,
- servicio con contrato formal.

Archivos SOAP del tema:

| Uso SOAP | Archivo principal | Que mirar |
| --- | --- | --- |
| SOAP basico de calculadora | `002-.../calculadora-soap.php` | Leer XML, detectar operacion, responder XML. |
| Cliente SOAP desde HTML | `002-.../calculadora-soap.html` | Crear XML SOAP y enviarlo con `fetch()`. |
| Contrato WSDL | `002-.../calculadora.wsdl` | `types`, `message`, `portType`, `binding`, `service`. |
| SOAP con cabecera de sesion | `003-.../prestamos.php` | Leer `soap:Header` y validar token. |
| SOAP con respuesta de varios campos | `003-.../prestamos.php` | Devolver `puede_prestar`, `mensaje`, `dias_maximos`. |
| SOAP con `requestId` | `004-.../envioPostal.php` | Leer datos tecnicos de cabecera. |
| SOAP con validaciones | `004-.../acceso_examen/accesoExamen.php` | Validar campos y devolver `soap:Fault`. |

Plantilla mental SOAP:

```text
HTML crea XML
  -> fetch(..., Content-Type: text/xml)
  -> PHP lee php://input
  -> DOMDocument carga XML
  -> DOMXPath busca nodos
  -> PHP aplica regla de negocio
  -> PHP responde XML SOAP
```

Funciones/patrones SOAP que puedes copiar:

| Patron | Archivo donde verlo | Para que sirve |
| --- | --- | --- |
| `file_get_contents("php://input")` | `calculadora-soap.php`, `prestamos.php`, `accesoExamen.php` | Leer el XML recibido. |
| `DOMDocument` | `calculadora-soap.php` | Convertir texto XML en documento navegable. |
| `DOMXPath` | `prestamos.php`, `accesoExamen.php` | Buscar nodos como `token`, `dni`, `edad`. |
| `//*[local-name()='nombreNodo']` | `prestamos.php` | Buscar nodos sin depender del prefijo XML. |
| `responderSOAP()` | `calculadora-soap.php` | Devolver respuesta SOAP normal. |
| `responderFault()` | `calculadora-soap.php`, `prestamos.php`, `accesoExamen.php` | Devolver error SOAP formal. |
| `htmlspecialchars($texto, ENT_XML1)` | Servicios SOAP | Escapar texto antes de meterlo en XML. |

### Bloque REST + JSON completo

Usa este bloque cuando el examen diga:

- API REST,
- endpoint,
- `GET`, `POST`, `PATCH`, `DELETE`,
- JSON,
- `fetch()`,
- codigo HTTP.

Archivos REST del tema:

| Uso REST | Archivo principal | Que mirar |
| --- | --- | --- |
| Servicios simples de tienda | `001-.../servicio_productos.php` | PHP devuelve JSON desde MySQL. |
| Stock y pedido | `001-.../servicio_stock.php`, `servicio_pedidos.php` | Consultar y modificar datos. |
| API REST con JSON local | `005-.../api_libros/apiRestLibros.php` | CRUD sin base de datos. |
| API REST con MySQL | `005-.../api_videojuegos/apiVideojuegos.php` | CRUD con PDO. |
| API REST con PATCH dinamico | `005-.../apiEstudios/apiEstudios.php` | Editar solo campos enviados. |
| API REST con filtros y JSON local | `005-.../gestorTareas_api/apiTareas.php` | Filtrar por query string. |

Plantilla mental REST:

```text
HTML/JS
  -> fetch()
  -> api.php/recurso
  -> PHP detecta metodo HTTP
  -> PHP detecta ruta
  -> PHP lee JSON si hace falta
  -> PHP responde JSON + codigo HTTP
```

Funciones/patrones REST que puedes copiar:

| Patron | Archivo donde verlo | Para que sirve |
| --- | --- | --- |
| `responder($codigo, $datos)` | `api_libros`, `apiEstudios`, `gestorTareas_api` | Responder JSON de forma uniforme. |
| `leerJSONBody()` | `api_libros`, `apiEstudios`, `gestorTareas_api` | Leer el JSON del cliente. |
| `$_SERVER["REQUEST_METHOD"]` | APIs REST | Saber si es `GET`, `POST`, `PATCH` o `DELETE`. |
| `PATH_INFO` | APIs REST | Leer rutas como `/libros/1`. |
| `obtenerRuta()` | `gestorTareas_api/apiTareas.php` | Alternativa robusta para XAMPP usando `?ruta=`. |
| `URLSearchParams` | Clientes HTML REST | Construir filtros como `?estado=pendiente`. |

### Bloque YAML/OpenAPI completo

Usa este bloque cuando el examen diga:

- documentar API,
- OpenAPI,
- Swagger,
- YAML,
- contrato REST.

Archivos YAML:

| API | YAML |
| --- | --- |
| Libros | `005-.../api_libros/openApiLibros.yaml` |
| Videojuegos | `005-.../api_videojuegos/openApiVideojuegos.yaml` |
| Estudios | `005-.../apiEstudios/openApiEstudio.yaml` |
| Tareas | `005-.../gestorTareas_api/openApiTareas.yaml` |

Recuerda:

```text
El YAML no ejecuta la API.
El YAML explica como se usa la API.
```

Partes importantes de OpenAPI:

| Parte | Para que sirve |
| --- | --- |
| `openapi` | Version del estandar. |
| `info` | Nombre, version y descripcion de la API. |
| `servers` | URL base. |
| `paths` | Rutas disponibles. |
| `parameters` | Datos que llegan por URL. |
| `requestBody` | JSON enviado en `POST` o `PATCH`. |
| `responses` | Respuestas posibles. |
| `components/schemas` | Modelos reutilizables. |

### Bloque MySQL/PDO completo

Usa este bloque cuando el examen diga:

- base de datos,
- MySQL,
- PDO,
- consulta preparada,
- `SELECT`, `INSERT`, `UPDATE`, `DELETE`.

Archivos con PDO:

| Archivo | Uso |
| --- | --- |
| `001-.../conexion_mysql.php` | Conexion reusable para tienda. |
| `001-.../ejercicio_videojuegos/conexion_videojuegos.php` | Conexion reusable para videojuegos. |
| `005-.../api_videojuegos/apiVideojuegos.php` | API REST con MySQL. |
| `005-.../apiEstudios/apiEstudios.php` | API REST con MySQL y PATCH dinamico. |

Plantilla PDO:

```php
$pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### Bloque JSON local completo

Usa este bloque cuando el examen no pida base de datos.

Archivos con JSON local:

| Archivo | Uso |
| --- | --- |
| `005-.../api_libros/apiRestLibros.php` | CRUD de libros con archivo JSON. |
| `005-.../gestorTareas_api/apiTareas.php` | CRUD de tareas con archivo JSON y filtros. |

Plantilla:

```php
$datos = json_decode(file_get_contents($archivo), true) ?? [];

file_put_contents(
    $archivo,
    json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
```

## Si el examen pide REST

Usa esta estructura mental:

```text
HTML cliente
  -> fetch()
  -> api.php/recurso
  -> PHP detecta metodo y ruta
  -> PHP consulta datos
  -> PHP responde JSON
```

Metodos:

```text
GET    consultar
POST   crear
PATCH  modificar parcialmente
DELETE eliminar
```

Archivos donde tienes ejemplos:

- JSON local: `005-.../api_libros/apiRestLibros.php`
- JSON local con filtros: `005-.../gestorTareas_api/apiTareas.php`
- MySQL: `005-.../api_videojuegos/apiVideojuegos.php`
- MySQL con PATCH dinamico: `005-.../apiEstudios/apiEstudios.php`

## Si el examen pide SOAP

Usa esta estructura mental:

```text
HTML cliente
  -> crea XML SOAP
  -> fetch() con Content-Type text/xml
  -> PHP lee php://input
  -> DOMDocument carga XML
  -> DOMXPath busca Header y Body
  -> PHP responde XML SOAP
```

Estructura SOAP:

```xml
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
    <!-- datos de sesion, requestId, clienteInfo... -->
  </soap:Header>
  <soap:Body>
    <!-- operacion real -->
  </soap:Body>
</soap:Envelope>
```

Archivos donde tienes ejemplos:

- SOAP simple: `002-.../calculadora-soap.php`
- SOAP con cabecera de sesion: `003-.../prestamos.php`
- SOAP con requestId: `004-.../envioPostal.php`
- SOAP con validaciones: `004-.../acceso_examen/accesoExamen.php`

## Si el examen pide YAML/OpenAPI

Recuerda:

```text
El YAML no ejecuta nada.
El YAML documenta la API REST.
```

Sirve para describir:

- rutas,
- metodos HTTP,
- parametros,
- body JSON,
- respuestas,
- schemas reutilizables.

Archivos de ejemplo:

- `005-.../api_libros/openApiLibros.yaml`
- `005-.../api_videojuegos/openApiVideojuegos.yaml`
- `005-.../apiEstudios/openApiEstudio.yaml`
- `005-.../gestorTareas_api/openApiTareas.yaml`

## Funciones reutilizables PHP

### 1. Conexion PDO a MySQL

Donde esta:

- `001-.../conexion_mysql.php`
- `001-.../ejercicio_videojuegos/conexion_videojuegos.php`
- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`

Para que sirve:

Abrir conexion segura con MySQL usando PDO.

Plantilla:

```php
function obtenerPDO() {
    $host = "127.0.0.1";
    $port = "3306";
    $dbname = "nombre_base";
    $user = "root";
    $pass = "";

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
```

Que cambiar:

```text
$port
$dbname
$user
$pass
```

### 2. Responder JSON con codigo HTTP

Donde esta:

- `005-.../api_libros/apiRestLibros.php`
- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Devolver una respuesta uniforme y terminar la ejecucion.

```php
function responder($codigo, $datos = null) {
    http_response_code($codigo);

    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    exit;
}
```

Codigos utiles:

```text
200 OK
201 Created
204 No Content
400 Bad Request
404 Not Found
405 Method Not Allowed
409 Conflict
500 Internal Server Error
```

### 3. Leer body JSON

Donde esta:

- `005-.../api_libros/apiRestLibros.php`
- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Leer datos enviados por `POST` o `PATCH`.

```php
function leerJSONBody() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if ($raw !== "" && $data === null) {
        responder(400, ["error" => "JSON invalido"]);
    }

    return $data ?? [];
}
```

### 4. Leer y guardar datos en archivo JSON

Donde esta:

- `005-.../api_libros/apiRestLibros.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Crear APIs sin MySQL.

```php
function leerDatos($archivoDatos) {
    return json_decode(file_get_contents($archivoDatos), true) ?? [];
}

function guardarDatos($archivoDatos, $datos) {
    file_put_contents(
        $archivoDatos,
        json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}
```

### 5. Obtener ruta REST

Donde esta:

- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`
- `005-.../gestorTareas_api/apiTareas.php`

Para que sirve:

Separar `/tareas/1` en recurso `tareas` e id `1`.

```php
$metodo = $_SERVER["REQUEST_METHOD"];
$ruta = $_SERVER["PATH_INFO"] ?? ($_GET["ruta"] ?? "");
$partesRuta = explode("/", trim($ruta, "/"));

$recurso = $partesRuta[0] ?? "";
$id = $partesRuta[1] ?? null;
```

### 6. Buscar por ID en MySQL

Donde esta:

- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`

Para que sirve:

Evitar repetir el mismo `SELECT` en `GET`, `POST` y `PATCH`.

```php
function buscarPorId($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM tabla WHERE id = :id");
    $stmt->execute([":id" => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
```

### 7. PATCH dinamico

Donde esta:

- `005-.../api_videojuegos/apiVideojuegos.php`
- `005-.../apiEstudios/apiEstudios.php`

Para que sirve:

Modificar solo los campos enviados.

```php
$campos = [];
$params = [":id" => $id];

if (isset($data["nombre"])) {
    $campos[] = "nombre = :nombre";
    $params[":nombre"] = trim($data["nombre"]);
}

$sql = "UPDATE tabla SET " . implode(", ", $campos) . " WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

### 8. Transaccion para operaciones relacionadas

Donde esta:

- `001-.../servicio_pedidos.php`
- `001-.../ejercicio_videojuegos/servicio_disponibilidad.php`

Para que sirve:

Si una operacion modifica varias cosas, o se hacen todas o ninguna.

```php
$pdo->beginTransaction();

try {
    // INSERT
    // UPDATE
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
}
```

### 9. Bloqueo de filas con FOR UPDATE

Donde esta:

- `001-.../servicio_pedidos.php`
- `001-.../ejercicio_videojuegos/servicio_disponibilidad.php`

Para que sirve:

Evitar vender/restar el mismo stock dos veces si llegan peticiones simultaneas.

```sql
SELECT ...
FROM stock
WHERE id_producto = :id
FOR UPDATE
```

### 10. Responder SOAP

Donde esta:

- `002-.../calculadora-soap.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Crear una respuesta XML SOAP correcta.

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

### 11. Responder error SOAP

Donde esta:

- `002-.../calculadora-soap.php`
- `003-.../prestamos.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Responder errores SOAP formales.

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

### 12. Leer XML SOAP

Donde esta:

- `002-.../calculadora-soap.php`
- `003-.../prestamos.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Leer una peticion SOAP recibida por POST.

```php
$xmlRecibido = file_get_contents("php://input");

$dom = new DOMDocument();
$dom->loadXML($xmlRecibido);

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

$body = $xpath->query("//soap:Body")->item(0);
```

### 13. Leer SOAP Header

Donde esta:

- `003-.../prestamos.php`
- `004-.../envioPostal.php`
- `004-.../acceso_examen/accesoExamen.php`

Para que sirve:

Leer token, requestId o informacion de cliente.

```php
$header = $xpath->query("//soap:Header")->item(0);
$requestIdNode = $header->getElementsByTagName("requestId")->item(0);
$requestId = $requestIdNode ? trim($requestIdNode->textContent) : "";
```

## Funciones reutilizables JavaScript

### 1. fetch GET JSON

Donde esta:

- todos los clientes REST HTML del bloque 005.

```js
const resp = await fetch(url, {
  method: "GET",
  headers: {
    Accept: "application/json",
  },
});
```

### 2. fetch POST/PATCH JSON

```js
const resp = await fetch(url, {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  body: JSON.stringify(body),
});
```

### 3. Mostrar peticion

Donde esta:

- `005-.../api_libros/apiRestLibros.html`
- `005-.../api_videojuegos/apiVideoJuegos.html`
- `005-.../apiEstudios/apiEstudios.html`
- `005-.../gestorTareas_api/gestorTareas.html`

```js
function mostrarPeticion(metodo, url, body = null) {
  let texto = metodo + " " + url;

  if (body !== null) {
    texto += "\n\nBody:\n" + JSON.stringify(body, null, 2);
  }

  peticion.innerText = texto;
}
```

### 4. Mostrar respuesta

```js
async function mostrarRespuesta(resp) {
  const texto = await resp.text();

  respuesta.innerText =
    "Codigo HTTP: " +
    resp.status +
    "\n\n" +
    (texto || "Sin cuerpo de respuesta");
}
```

### 5. Construir URL compatible con XAMPP

Donde esta:

- `005-.../gestorTareas_api/gestorTareas.html`

```js
function construirUrl(ruta, parametros = null) {
  let url = baseUrl + "?ruta=" + encodeURIComponent(ruta);

  if (parametros !== null && parametros.toString() !== "") {
    url += "&" + parametros.toString();
  }

  return url;
}
```

### 6. Crear query string

Donde esta:

- `005-.../api_videojuegos/apiVideoJuegos.html`
- `005-.../apiEstudios/apiEstudios.html`
- `005-.../gestorTareas_api/gestorTareas.html`

```js
const parametros = new URLSearchParams();

if (precioMax !== "") {
  parametros.append("precioMax", precioMax);
}

if (parametros.toString() !== "") {
  url += "?" + parametros.toString();
}
```

### 7. fetch SOAP

Donde esta:

- `002-.../calculadora-soap.html`
- `003-.../prestamos.html`
- `004-.../envioPostal.html`
- `004-.../acceso_examen/accesoExamen.html`

```js
const respuesta = await fetch("servicio.php", {
  method: "POST",
  headers: {
    "Content-Type": "text/xml; charset=utf-8",
    Accept: "text/xml",
  },
  body: xmlSOAP,
});
```

### 8. Interpretar XML en navegador

```js
const parser = new DOMParser();
const xml = parser.parseFromString(textoRespuesta, "text/xml");
const nodoResultado = xml.getElementsByTagName("resultado")[0];
```

## Plantillas de examen

### API REST minima con JSON

```php
<?php
header("Content-Type: application/json; charset=utf-8");

function responder($codigo, $datos = null) {
    http_response_code($codigo);
    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$metodo = $_SERVER["REQUEST_METHOD"];
$ruta = $_SERVER["PATH_INFO"] ?? "";
$partes = explode("/", trim($ruta, "/"));
$recurso = $partes[0] ?? "";
$id = $partes[1] ?? null;

if ($recurso !== "recurso") {
    responder(404, ["error" => "Recurso no encontrado"]);
}

if ($metodo === "GET" && $id === null) {
    responder(200, ["datos" => []]);
}

responder(405, ["error" => "Metodo no permitido"]);
?>
```

### Servicio SOAP minimo

```php
<?php
header("Content-Type: text/xml; charset=utf-8");

$xmlRecibido = file_get_contents("php://input");
$dom = new DOMDocument();
$dom->loadXML($xmlRecibido);

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");
$body = $xpath->query("//soap:Body")->item(0);

echo '<?xml version="1.0" encoding="UTF-8"?>'
   . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
   . '<soap:Body>'
   . '<respuesta><mensaje>OK</mensaje></respuesta>'
   . '</soap:Body>'
   . '</soap:Envelope>';
?>
```

## Como decidir que usar

| Enunciado dice | Usa |
| --- | --- |
| "servicio SOAP" | XML + Envelope + Body + WSDL |
| "cabecera de sesion" | SOAP Header |
| "API REST" | metodos HTTP + rutas + JSON |
| "documenta la API" | OpenAPI YAML |
| "consume desde cliente" | HTML + fetch |
| "base MySQL" | PDO + prepare/execute |
| "sin base de datos" | archivo JSON |
