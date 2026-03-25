<?php
header("Content-Type: text/plain; charset=utf-8");
$accion = $_GET["accion"] ?? "";

// -------------------------------------------------------------
// ACCIÓN 1: Leer cookie que viene del cliente y responder con texto
// -------------------------------------------------------------
if ($accion === "leer_cookie") {
    if (isset($_COOKIE["nombre"]) && isset($_COOKIE["edad"])) {
      $nombre = $_COOKIE["nombre"] ?? "sin nombre";
      $edad = $_COOKIE["edad"] ?? "sin edad";

        echo "El servidor recibió la cookie nombre = " . $nombre." edad = ". $edad;
    } else {
        echo "No se recibieron cookies llamadas 'nombre y edad'";
    }
    exit;
}

// -------------------------------------------------------------
// ACCIÓN 2: El servidor crea una cookie y se la manda al cliente
// -------------------------------------------------------------
if ($accion === "servidor_set_cookie") {
    $nombre = $_COOKIE["nombre"] ?? "no ingresaste tu nombre";
    $edad = $_COOKIE["edad"] ?? "no sabemos tu edad";
 
    // Creamos el mensaje que queremos enviar "en forma de cookie"
    $mensaje = "Hola $nombre. Este mensaje viene desde una cookie creada por el servidor. tu edad es $edad";

    // IMPORTANTE: setcookie() debe ejecutarse ANTES de imprimir nada (antes de cualquier echo)
    setcookie("mensaje_servidor", $mensaje, [
        "expires"  => time() + 3600,
        "path"     => "/",
        "secure"   => false,   // pon true si usas https
        "httponly" => false,   // false para que JS pueda leerla con document.cookie/getCookie
        "samesite" => "Lax"
    ]);

    echo "Cookie 'mensaje_servidor' enviada al cliente (Set-Cookie).";
    exit;
}

echo "Acción no reconocida.";


 ?>
