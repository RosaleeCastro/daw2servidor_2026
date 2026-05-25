<?php
/*
 * Servidor SOAP didactico: servicio de prestamos de biblioteca.
 *
 * Este archivo recibe una peticion SOAP en XML, comprueba una cabecera de
 * sesion y responde si un usuario puede llevarse prestado un libro.
 *
 * Operacion soportada:
 * - consultarPrestamo
 *
 * Entrada esperada dentro del SOAP Body:
 *
 * <consultarPrestamo>
 *   <dni>12345678A</dni>
 *   <codigoLibro>LIB001</codigoLibro>
 * </consultarPrestamo>
 *
 * Cabecera SOAP esperada:
 *
 * <soap:Header>
 *   <sesion>
 *     <token>ABC123</token>
 *   </sesion>
 * </soap:Header>
 *
 * Respuesta correcta:
 *
 * <consultarPrestamoResponse>
 *   <puede_prestar>true</puede_prestar>
 *   <mensaje>Prestamo autorizado.</mensaje>
 *   <dias_maximos>15</dias_maximos>
 * </consultarPrestamoResponse>
 */

header("Content-Type: text/xml; charset=utf-8");

/*
 * Token didactico de sesion.
 *
 * En un sistema real este token vendria de login, base de datos, JWT,
 * variable de sesion, OAuth, etc. Aqui lo dejamos fijo para practicar SOAP
 * Header sin complicar el ejercicio.
 */
const TOKEN_VALIDO = "ABC123";

/**
 * Devuelve un error SOAP.
 *
 * @param string $mensaje Mensaje explicativo del error.
 */
function responderFault($mensaje) {
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");

    echo '<?xml version="1.0" encoding="UTF-8"?>'
        . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
        . '<soap:Body>'
        . '<soap:Fault>'
        . '<faultcode>SOAP-ENV:Client</faultcode>'
        . '<faultstring>' . $mensajeSeguro . '</faultstring>'
        . '</soap:Fault>'
        . '</soap:Body>'
        . '</soap:Envelope>';
    exit;
}

/**
 * Devuelve la respuesta SOAP de consultarPrestamo.
 *
 * @param bool $puedePrestar Indica si el prestamo esta permitido.
 * @param string $mensaje Explicacion para el usuario.
 * @param int $diasMaximos Dias maximos de prestamo.
 */
function responderPrestamoSOAP($puedePrestar, $mensaje, $diasMaximos) {
    $puedePrestarXml = $puedePrestar ? "true" : "false";
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");

    echo '<?xml version="1.0" encoding="UTF-8"?>'
        . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
        . '<soap:Body>'
        . '<consultarPrestamoResponse>'
        . '<puede_prestar>' . $puedePrestarXml . '</puede_prestar>'
        . '<mensaje>' . $mensajeSeguro . '</mensaje>'
        . '<dias_maximos>' . (int)$diasMaximos . '</dias_maximos>'
        . '</consultarPrestamoResponse>'
        . '</soap:Body>'
        . '</soap:Envelope>';
    exit;
}

// El servicio SOAP solo acepta peticiones POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderFault("Este endpoint SOAP solo acepta peticiones POST.");
}

// Leemos el XML crudo enviado por el cliente.
$xmlRecibido = file_get_contents("php://input");

if (trim($xmlRecibido) === "") {
    responderFault("No se recibio ningun XML.");
}

// Controlamos nosotros los errores XML para responder con soap:Fault.
libxml_use_internal_errors(true);

$dom = new DOMDocument();

if (!$dom->loadXML($xmlRecibido)) {
    responderFault("El XML recibido no es valido.");
}

$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

/*
 * 1. Leemos la cabecera SOAP de sesion.
 *
 * El ejercicio pide incorporar cabeceras de sesion en el SOAP.
 * Por eso el servidor no solo mira el Body, tambien mira Header.
 */
$header = $xpath->query("//soap:Header")->item(0);

if (!$header) {
    responderFault("Falta la cabecera SOAP Header con la sesion.");
}

$tokenNode = $xpath->query(".//*[local-name()='token']", $header)->item(0);

if (!$tokenNode) {
    responderFault("Falta el token de sesion.");
}

$token = trim($tokenNode->textContent);

if ($token !== TOKEN_VALIDO) {
    responderFault("Token de sesion no valido.");
}

/*
 * 2. Leemos el Body, donde viene la operacion real.
 */
$body = $xpath->query("//soap:Body")->item(0);

if (!$body) {
    responderFault("No se encontro el elemento Body.");
}

$operacionNode = null;

foreach ($body->childNodes as $nodo) {
    if ($nodo instanceof DOMElement) {
        $operacionNode = $nodo;
        break;
    }
}

if (!$operacionNode) {
    responderFault("No se encontro ninguna operacion dentro del Body.");
}

$operacion = $operacionNode->localName;

if ($operacion !== "consultarPrestamo") {
    responderFault("La operacion '$operacion' no esta soportada.");
}

/*
 * 3. Extraemos parametros de la operacion.
 */
$dniNode = $operacionNode->getElementsByTagName("dni")->item(0);
$codigoLibroNode = $operacionNode->getElementsByTagName("codigoLibro")->item(0);

if (!$dniNode || !$codigoLibroNode) {
    responderFault("Faltan los parametros dni o codigoLibro.");
}

$dni = strtoupper(trim($dniNode->textContent));
$codigoLibro = strtoupper(trim($codigoLibroNode->textContent));

if ($dni === "" || $codigoLibro === "") {
    responderFault("dni y codigoLibro no pueden estar vacios.");
}

/*
 * 4. Reglas didacticas del prestamo.
 *
 * No hay base de datos en el enunciado, asi que usamos reglas simples:
 *
 * - Usuarios sancionados: no pueden pedir prestamos.
 * - Libros no prestables: solo consulta en sala.
 * - Libros con codigo desconocido: no se puede prestar.
 * - Si todo esta bien: prestamo autorizado 15 dias.
 */
$usuariosSancionados = [
    "00000000X",
    "11111111A"
];

$libros = [
    "LIB001" => [
        "titulo" => "Introduccion a SOAP",
        "prestable" => true,
        "dias" => 15
    ],
    "LIB002" => [
        "titulo" => "PHP y servicios web",
        "prestable" => true,
        "dias" => 10
    ],
    "REF001" => [
        "titulo" => "Enciclopedia de referencia",
        "prestable" => false,
        "dias" => 0
    ]
];

if (in_array($dni, $usuariosSancionados, true)) {
    responderPrestamoSOAP(
        false,
        "Prestamo denegado: el usuario tiene una sancion activa.",
        0
    );
}

if (!isset($libros[$codigoLibro])) {
    responderPrestamoSOAP(
        false,
        "Prestamo denegado: el codigo de libro no existe.",
        0
    );
}

$libro = $libros[$codigoLibro];

if (!$libro["prestable"]) {
    responderPrestamoSOAP(
        false,
        "Prestamo denegado: el libro '" . $libro["titulo"] . "' solo se puede consultar en sala.",
        0
    );
}

responderPrestamoSOAP(
    true,
    "Prestamo autorizado para el libro '" . $libro["titulo"] . "'.",
    $libro["dias"]
);
?>
