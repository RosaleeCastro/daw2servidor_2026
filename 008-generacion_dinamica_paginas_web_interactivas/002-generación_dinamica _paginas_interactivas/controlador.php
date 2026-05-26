<?php

/*
    CONTROLADOR PRINCIPAL DE LA API

    Este archivo es el punto de entrada para las peticiones del cliente.

    Flujo:
    cliente.html
      -> fetch("controlador.php/alumnos")
      -> controlador.php detecta que se pide "alumnos"
      -> carga servicios/servicioAlumnos.php
      -> ejecuta servicioAlumnos()
      -> devuelve JSON al navegador
*/

// Todas las respuestas de este controlador seran JSON.
header("Content-Type: application/json; charset=utf-8");

/*
    Mapa de servicios permitidos.

    La clave ("videojuegos" o "alumnos") debe coincidir con la parte final
    de la URL:

    controlador.php/videojuegos
    controlador.php/alumnos

    Cada servicio indica:
    - archivo: PHP que contiene la logica.
    - funcion: funcion principal que se debe ejecutar.
*/
$servicios = [
    "videojuegos" => [
        "archivo" => "servicios/servicioVideojuegos.php",
        "funcion" => "servicioVideojuegos"
    ],
    "alumnos" => [
        "archivo" => "servicios/servicioAlumnos.php",
        "funcion" => "servicioAlumnos"
    ]
];

// Metodo HTTP usado por el cliente: GET para consultar, POST para crear.
$metodo = $_SERVER["REQUEST_METHOD"];

/*
    Ruta solicitada despues de controlador.php.

    Ejemplo:
    - URL: controlador.php/videojuegos
    - PATH_INFO: /videojuegos
    - despues de trim(): videojuegos
*/
$ruta = $_SERVER["PATH_INFO"] ?? "";
$ruta = trim($ruta, "/");

// Si no se ha indicado ningun servicio, no sabemos a que archivo delegar.
if ($ruta === "") {
    responderJson([
        "error" => "No se ha indicado ningun servicio",
        "ejemplos" => [
            "GET controlador.php/videojuegos",
            "POST controlador.php/videojuegos",
            "GET controlador.php/alumnos",
            "POST controlador.php/alumnos"
        ]
    ], 400);
}

// Si la ruta no esta en el mapa, la API no permite ese servicio.
if (!isset($servicios[$ruta])) {
    responderJson([
        "error" => "Servicio no encontrado"
    ], 404);
}

// A partir de la ruta elegimos que archivo cargar y que funcion ejecutar.
$archivoServicio = $servicios[$ruta]["archivo"];
$funcionServicio = $servicios[$ruta]["funcion"];

require_once $archivoServicio;

/*
    Leemos el cuerpo de la peticion.

    En los POST, cliente.html envia JSON con fetch():
    body: JSON.stringify({ nombre: nombre, curso: curso })

    PHP lo lee desde php://input y lo convierte a array asociativo.
*/
$entrada = file_get_contents("php://input");
$datosEntrada = json_decode($entrada, true);

// Si no se ha enviado JSON, trabajamos con un array vacio.
if ($datosEntrada === null) {
    $datosEntrada = [];
}

/*
    Delegacion al servicio.

    El controlador no sabe como consultar o guardar alumnos/videojuegos.
    Solo llama a la funcion del servicio y espera este formato:

    [
        "codigo" => 200,
        "datos" => [...]
    ]
*/
$resultado = $funcionServicio($metodo, $datosEntrada);

// Enviamos al navegador los datos y el codigo HTTP que decidio el servicio.
responderJson($resultado["datos"], $resultado["codigo"]);


/*
    Funcion comun para responder siempre igual:
    - establece el codigo HTTP.
    - convierte el array PHP a JSON.
    - termina la ejecucion con exit.
*/
function responderJson($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
