<?php

header("Content-Type: text/html; charset=utf-8");// no recibe json

$categoria = $_GET["categoria"] ?? "";

$categoriasPermitidas = [
    "",
    "Rol",
    "Carreras",
    "Aventura",
    "Construcción"
];

// Si no es válida la resetea a vacío (muestra todas)
if (!in_array($categoria, $categoriasPermitidas, true)) {
    $categoria = "";
}

$urlProveedor = construirUrlProveedor($categoria);

$respuestaRemota = obtenerContenidoRemoto($urlProveedor);

if ($respuestaRemota["error"] !== "") {
    mostrarError("No se pudo conectar con el proveedor remoto.");
    exit;
}

$ofertas = json_decode($respuestaRemota["contenido"], true);

if (!is_array($ofertas)) {
    mostrarError("El proveedor remoto no ha devuelto un JSON válido.");
    exit;
}

if (count($ofertas) === 0) {
    echo '<p class="mensaje">No hay ofertas para la categoría seleccionada.</p>';
    exit;
}

mostrarOfertas($ofertas);


// --------------------------------------------------------------------
// Construye la URL absoluta del proveedor remoto simulado
// --------------------------------------------------------------------

function construirUrlProveedor($categoria) {
    $protocolo = "http";

    // 1. Detectar protocolo http o https
    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") {
        $protocolo = "https";
    }
    // 2. Obtener el host actual
    $host = $_SERVER["HTTP_HOST"];

   // 3. Obtener la carpeta del proyecto y codificar cada parte
    //   rawurlencode() evita problemas con tildes, espacios y caracteres especiales

    $carpetaProyecto = dirname(dirname($_SERVER["SCRIPT_NAME"]));

    // Codificamos cada parte de la ruta para evitar problemas con espacios, tildes, etc.
    $partesRuta = explode("/", trim($carpetaProyecto, "/"));
    $rutaCodificada = "";

    foreach ($partesRuta as $parte) {
        $rutaCodificada .= "/" . rawurlencode($parte);
    }

        // 4. Montar la URL completa
    $url = $protocolo . "://" . $host . $rutaCodificada . "/proveedorExterno/ofertas.php";

        // 5. Añadir el parámetro si llega categoría
    if ($categoria !== "") {
        $url .= "?categoria=" . urlencode($categoria);
    }

    return $url;
    // → "http://localhost/carpeta/proveedorExterno/ofertas.php?categoria=Rol"
}


// --------------------------------------------------------------------
// Obtiene contenido remoto con cURL
// --------------------------------------------------------------------
function obtenerContenidoRemoto($url) {
    // Comprueba que cURL esté disponible en PHP (libreria como PDO)
    if (!function_exists("curl_init")) {
        return [
            "contenido" => "",
            "error" => "La extensión cURL no está activada en PHP."
        ];
    }
    //1 Iniciar cURL

    $ch = curl_init();

    //2. Configurar 
    curl_setopt($ch, CURLOPT_URL, $url); //UDL a llamar 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // devuleve la respuesta como string

    // Tiempo máximo para conectar.
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    // Tiempo máximo total de la petición.
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    //3. Ejecutar la peticion 
    $contenido = curl_exec($ch);
    $error = curl_error($ch); // "" si no hay error
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // 200 404 500

    // 4. Comprobar error de conexion
    if ($contenido === false || $error !== "") {
        return [
            "contenido" => "",
            "error" => $error
        ];
    }

    //5. Comprobar codigo HTTP del proveedor 

    if ($codigoHttp < 200 || $codigoHttp >= 300) {
        return [
            "contenido" => "",
            "error" => "Código HTTP no válido: " . $codigoHttp
        ];
    }
    // 6. todo bien  devolver el contenido 
    return [
        "contenido" => $contenido,
        "error" => ""
    ];
}


// --------------------------------------------------------------------
// Genera el HTML que recibirá el cliente
// --------------------------------------------------------------------
// Para qué sirve: convierte el array de ofertas en HTML
// ✅ htmlspecialchars() en TODOS los campos — el contenido viene de fuera
//    evita que datos maliciosos del proveedor rompan tu HTML

function mostrarOfertas($ofertas) {
    echo '<div class="ofertas">';

    foreach ($ofertas as $oferta) {
        echo '<article class="oferta">';
        echo '<h3>' . htmlspecialchars($oferta["titulo"]) . '</h3>';
        echo '<p>Categoría: ' . htmlspecialchars($oferta["categoria"]) . '</p>';
        echo '<p class="descuento">Descuento: ' . htmlspecialchars($oferta["descuento"]) . '%</p>';
        echo '<p>' . htmlspecialchars($oferta["descripcion"]) . '</p>';
        echo '</article>';
    }

    echo '</div>';
}


// --------------------------------------------------------------------
// Muestra errores en formato HTML
// --------------------------------------------------------------------
function mostrarError($mensaje) {
    echo '<p class="error">' . htmlspecialchars($mensaje) . '</p>';
}