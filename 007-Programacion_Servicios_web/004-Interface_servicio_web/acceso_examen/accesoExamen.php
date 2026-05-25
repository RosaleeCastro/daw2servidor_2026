<?php
/*
 * Servidor SOAP didactico: validacion de acceso a examen.
 *
 * Este archivo implementa el ejercicio de accesoExamen.txt.
 *
 * Requisitos del ejercicio:
 * - Comunicacion con SOAP completo.
 * - El servicio recibe nombre, edad y si esta matriculado.
 * - El servicio devuelve si el acceso esta permitido y un mensaje explicativo.
 * - Un alumno esta permitido si tiene mas de 16 y esta matriculado.
 * - Es necesaria validacion de campos recibidos y mensaje de error.
 *
 * Este servicio sigue la misma idea que envioPostal.php:
 * - Recibe XML SOAP por POST.
 * - Lee una cabecera opcional del SOAP Header.
 * - Lee la operacion dentro del SOAP Body.
 * - Valida campos.
 * - Devuelve una respuesta SOAP o un soap:Fault.
 */

// La respuesta de un servicio SOAP debe ser XML.
header("Content-Type: text/xml; charset=utf-8");

/**
 * Devuelve una respuesta SOAP correcta para validarAccesoExamen.
 *
 * @param bool $permitido true si el alumno puede acceder al examen.
 * @param string $mensaje Explicacion del resultado.
 * @param string $requestId Identificador opcional recibido en SOAP Header.
 */
function responderSOAP($permitido, $mensaje, $requestId = "") {
    /*
     * En la respuesta incluimos tambien soap:Header.
     *
     * Esto no es obligatorio para resolver la logica, pero ayuda a practicar
     * SOAP completo: Envelope + Header + Body.
     *
     * El requestId sirve para demostrar que el servidor puede leer una cabecera
     * de la peticion y devolverla en la respuesta.
     */
    $permitidoXml = $permitido ? "true" : "false";
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, "UTF-8");
    $requestIdSeguro = htmlspecialchars($requestId, ENT_XML1 | ENT_QUOTES, "UTF-8");

    echo '<?xml version="1.0" encoding="UTF-8"?>'
        . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
        . '<soap:Header>'
        . '<respuestaInfo>'
        . '<servidor>ServicioAccesoExamenPHP</servidor>'
        . '<requestId>' . $requestIdSeguro . '</requestId>'
        . '</respuestaInfo>'
        . '</soap:Header>'
        . '<soap:Body>'
        . '<validarAccesoExamenResponse>'
        . '<permitido>' . $permitidoXml . '</permitido>'
        . '<mensaje>' . $mensajeSeguro . '</mensaje>'
        . '</validarAccesoExamenResponse>'
        . '</soap:Body>'
        . '</soap:Envelope>';

    exit;
}

/**
 * Devuelve un error SOAP.
 *
 * En SOAP los errores se devuelven con soap:Fault.
 * Lo usamos cuando falta algun dato, cuando el XML no es valido o cuando la
 * operacion solicitada no existe.
 *
 * @param string $mensaje Texto del error.
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

// Un servicio SOAP normalmente recibe la peticion por HTTP POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderFault("Este servicio SOAP solo acepta peticiones POST.");
}

/*
 * Leemos el XML crudo de la peticion.
 *
 * No usamos $_POST porque el cliente no envia un formulario.
 * Envia un documento XML completo en el cuerpo HTTP.
 */
$xmlRecibido = file_get_contents("php://input");

if (trim($xmlRecibido) === "") {
    responderFault("No se recibio ningun XML.");
}

// Evita que PHP imprima warnings de XML. Queremos responder siempre con SOAP.
libxml_use_internal_errors(true);

$dom = new DOMDocument();

if (!$dom->loadXML($xmlRecibido)) {
    responderFault("El XML recibido no es valido.");
}

/*
 * DOMXPath nos permite buscar elementos dentro del XML.
 * Registramos el namespace soap para encontrar soap:Header y soap:Body.
 */
$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

/*
 * SOAP Header.
 *
 * En este ejercicio no se pide autenticar usuario, pero envioPostal usaba
 * cabecera para requestId. Repetimos ese patron para practicar interfaces SOAP.
 */
$header = $xpath->query("//soap:Header")->item(0);
$requestId = "";

if ($header instanceof DOMElement) {
    $requestIdNode = $header->getElementsByTagName("requestId")->item(0);

    if ($requestIdNode) {
        $requestId = trim($requestIdNode->textContent);
    }
}

/*
 * SOAP Body.
 *
 * Aqui debe venir la operacion real:
 *
 * <validarAccesoExamen>
 *   <nombre>Ana</nombre>
 *   <edad>18</edad>
 *   <matriculado>true</matriculado>
 * </validarAccesoExamen>
 */
$body = $xpath->query("//soap:Body")->item(0);

if (!$body) {
    responderFault("No se encontro el elemento Body.");
}

// Buscamos la primera etiqueta real dentro del Body.
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

if ($operacion !== "validarAccesoExamen") {
    responderFault("La operacion '$operacion' no esta soportada.");
}

/*
 * Extraemos los campos recibidos.
 *
 * El enunciado pide:
 * - nombre
 * - edad
 * - si esta matriculado
 */
$nombreNode = $operacionNode->getElementsByTagName("nombre")->item(0);
$edadNode = $operacionNode->getElementsByTagName("edad")->item(0);
$matriculadoNode = $operacionNode->getElementsByTagName("matriculado")->item(0);

if (!$nombreNode || !$edadNode || !$matriculadoNode) {
    responderFault("Faltan parametros obligatorios: nombre, edad o matriculado.");
}

$nombre = trim($nombreNode->textContent);
$edadTexto = trim($edadNode->textContent);
$matriculadoTexto = strtolower(trim($matriculadoNode->textContent));

/*
 * Validacion de nombre.
 *
 * No permitimos nombre vacio porque el mensaje explicativo lo usa.
 */
if ($nombre === "") {
    responderFault("El nombre no puede estar vacio.");
}

/*
 * Validacion de edad.
 *
 * Debe ser numerica, entera y positiva.
 */
if (!is_numeric($edadTexto)) {
    responderFault("La edad debe ser numerica.");
}

$edad = (int)$edadTexto;

if ((string)$edad !== (string)(float)$edadTexto && strpos($edadTexto, ".") !== false) {
    responderFault("La edad debe ser un numero entero.");
}

if ($edad < 0) {
    responderFault("La edad no puede ser negativa.");
}

/*
 * Validacion de matriculado.
 *
 * Aceptamos solo true o false, que son los valores booleanos habituales en
 * XML/SOAP para este tipo de ejercicio.
 */
if ($matriculadoTexto !== "true" && $matriculadoTexto !== "false") {
    responderFault("El campo matriculado debe ser true o false.");
}

$matriculado = ($matriculadoTexto === "true");

/*
 * Regla de negocio del ejercicio:
 *
 * Un alumno puede acceder al examen si:
 * - tiene mas de 16 anos
 * - esta matriculado
 */
if ($edad <= 16 && !$matriculado) {
    responderSOAP(
        false,
        "$nombre no puede acceder al examen: debe tener mas de 16 anos y estar matriculado.",
        $requestId
    );
}

if ($edad <= 16) {
    responderSOAP(
        false,
        "$nombre no puede acceder al examen: debe tener mas de 16 anos.",
        $requestId
    );
}

if (!$matriculado) {
    responderSOAP(
        false,
        "$nombre no puede acceder al examen: no esta matriculado.",
        $requestId
    );
}

responderSOAP(
    true,
    "$nombre puede acceder al examen: cumple la edad minima y esta matriculado.",
    $requestId
);
?>
