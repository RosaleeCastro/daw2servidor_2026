<?php
/*
 * Servidor SOAP didactico: calculadora.
 *
 * Este archivo recibe peticiones SOAP en XML mediante HTTP POST.
 * No recibe JSON ni formularios normales: recibe un documento XML completo.
 *
 * Operaciones soportadas:
 * - sumar
 * - restar
 * - multiplicar
 * - dividir
 *
 * La division es especial porque devuelve dos datos:
 * - resultado: cociente de la division
 * - resto: resto de la division
 *
 * Si se intenta dividir entre 0, el servicio devuelve un soap:Fault.
 */

// SOAP responde XML, no HTML ni JSON.
header("Content-Type: text/xml; charset=utf-8");

/**
 * Devuelve una respuesta SOAP correcta para operaciones con un solo resultado.
 *
 * Se usa para:
 * - sumar
 * - restar
 * - multiplicar
 *
 * @param string $operacion Nombre de la operacion recibida.
 * @param float|int $resultado Resultado calculado.
 */
function responderSOAP($operacion, $resultado) {
    /*
     * En SOAP la respuesta tambien va dentro de:
     * - soap:Envelope
     * - soap:Body
     *
     * Por convencion:
     * - sumar responde con sumarResponse
     * - restar responde con restarResponse
     * - multiplicar responde con multiplicarResponse
     */
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
            <' . $operacion . 'Response>
            <resultado>' . $resultado . '</resultado>
            </' . $operacion . 'Response>
        </soap:Body>
        </soap:Envelope>';

    exit;
}

/**
 * Devuelve una respuesta SOAP especial para la division.
 *
 * La division necesita una estructura distinta porque devuelve:
 * - resultado
 * - resto
 *
 * @param float|int $resultado Cociente de la division.
 * @param float|int $resto Resto de la division.
 */
function responderDivisionSOAP($resultado, $resto) {
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
            <dividirResponse>
            <resultado>' . $resultado . '</resultado>
            <resto>' . $resto . '</resto>
            </dividirResponse>
        </soap:Body>
        </soap:Envelope>';

    exit;
}

/**
 * Devuelve un error SOAP.
 *
 * En SOAP los errores se envian con la etiqueta soap:Fault.
 *
 * @param string $mensaje Explicacion del error.
 */
function responderFault($mensaje) {
    /*
     * htmlspecialchars evita que un mensaje con caracteres especiales rompa
     * el XML. ENT_XML1 indica que escapamos texto para un documento XML.
     */
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_XML1 | ENT_QUOTES, 'UTF-8');

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
        <soap:Body>
            <soap:Fault>
            <faultcode>SOAP-ENV:Client</faultcode>
            <faultstring>' . $mensajeSeguro . '</faultstring>
            </soap:Fault>
        </soap:Body>
        </soap:Envelope>';

    exit;
}

// Este endpoint esta pensado solo para recibir peticiones HTTP POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responderFault("Este endpoint SOAP solo acepta peticiones POST.");
}

/*
 * Leemos el cuerpo completo de la peticion.
 *
 * En SOAP no esperamos $_POST["campo"], porque no llega un formulario.
 * Llega XML crudo, por eso usamos php://input.
 */
$xmlRecibido = file_get_contents("php://input");

// Si no llega nada, respondemos con un Fault.
if (trim($xmlRecibido) === "") {
    responderFault("No se recibio ningun XML.");
}

/*
 * Evita que PHP imprima warnings XML directamente en pantalla.
 * Preferimos controlar nosotros el error y devolver un soap:Fault.
 */
libxml_use_internal_errors(true);

// DOMDocument permite cargar y recorrer documentos XML en PHP.
$dom = new DOMDocument();

// Intentamos interpretar el texto recibido como XML.
if (!$dom->loadXML($xmlRecibido)) {
    responderFault("El XML recibido no es valido.");
}

/*
 * DOMXPath permite buscar nodos dentro del XML usando rutas.
 * Registramos el namespace soap porque Body esta dentro del espacio de nombres
 * http://schemas.xmlsoap.org/soap/envelope/.
 */
$xpath = new DOMXPath($dom);
$xpath->registerNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");

// Buscamos el elemento soap:Body, que es donde va la operacion real.
$body = $xpath->query("//soap:Body")->item(0);

if (!$body) {
    responderFault("No se encontro el elemento Body.");
}

/*
 * Dentro de soap:Body esperamos encontrar una operacion:
 *
 * <sumar>...</sumar>
 * <restar>...</restar>
 * <multiplicar>...</multiplicar>
 * <dividir>...</dividir>
 */
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

// localName devuelve el nombre sin prefijo de namespace: sumar, restar, etc.
$operacion = $operacionNode->localName;

// Buscamos los dos parametros esperados dentro de la operacion.
$aNode = $operacionNode->getElementsByTagName("a")->item(0);
$bNode = $operacionNode->getElementsByTagName("b")->item(0);

if (!$aNode || !$bNode) {
    responderFault("Faltan los parametros a o b.");
}

// Extraemos el texto de <a> y <b>.
$a = $aNode->textContent;
$b = $bNode->textContent;

// Validamos que ambos parametros sean numeros.
if (!is_numeric($a) || !is_numeric($b)) {
    responderFault("Los parametros deben ser numericos.");
}

// Convertimos a float para poder operar con enteros y decimales.
$a = (float)$a;
$b = (float)$b;

/*
 * Ejecutamos la operacion solicitada.
 *
 * Las tres primeras operaciones devuelven solo <resultado>.
 * La division devuelve <resultado> y <resto>.
 */
switch ($operacion) {
    case "sumar":
        $resultado = $a + $b;
        responderSOAP("sumar", $resultado);
        break;

    case "restar":
        $resultado = $a - $b;
        responderSOAP("restar", $resultado);
        break;

    case "multiplicar":
        $resultado = $a * $b;
        responderSOAP("multiplicar", $resultado);
        break;

    case "dividir":
        if ($b == 0) {
            responderFault("No se puede dividir entre 0.");
        }

        $resultado = $a / $b;
        $resto = fmod($a, $b);
        responderDivisionSOAP($resultado, $resto);
        break;

    default:
        responderFault("La operacion '$operacion' no esta soportada.");
}
?>
