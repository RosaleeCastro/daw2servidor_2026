<?php

/*
    SERVICIO DE VIDEOJUEGOS

    Este archivo contiene la logica concreta de videojuegos.
    El controlador lo carga cuando la ruta es:

    controlador.php/videojuegos

    Flujo:
    controlador.php
      -> servicioVideojuegos($metodo, $datosEntrada)
      -> GET consulta datos/videojuegos.json
      -> POST valida, anade y guarda en datos/videojuegos.json
      -> devuelve codigo HTTP y datos al controlador
*/

function servicioVideojuegos($metodo, $datosEntrada) {
    // Archivo donde se guardan los videojuegos.
    $archivo = "datos/videojuegos.json";

    // GET /videojuegos: consultar todos los videojuegos.
    if ($metodo === "GET") {
        return consultarVideojuegos($archivo);
    }

    // POST /videojuegos: crear un videojuego nuevo con los datos recibidos.
    if ($metodo === "POST") {
        return anadirVideojuego($archivo, $datosEntrada);
    }

    // Cualquier otro metodo no esta permitido en este ejercicio.
    return [
        "codigo" => 405,
        "datos" => [
            "error" => "Metodo no permitido para videojuegos"
        ]
    ];
}


function consultarVideojuegos($archivo) {
    // Lee el JSON y lo devuelve al controlador.
    $videojuegos = leerJson($archivo);

    return [
        "codigo" => 200,
        "datos" => $videojuegos
    ];
}


function anadirVideojuego($archivo, $datosEntrada) {
    /*
        Validacion de entrada.
        El cliente debe enviar:
        {
            "titulo": "...",
            "genero": "..."
        }
    */
    if (!isset($datosEntrada["titulo"]) || trim($datosEntrada["titulo"]) === "") {
        return [
            "codigo" => 400,
            "datos" => [
                "error" => "El titulo del videojuego es obligatorio"
            ]
        ];
    }

    if (!isset($datosEntrada["genero"]) || trim($datosEntrada["genero"]) === "") {
        return [
            "codigo" => 400,
            "datos" => [
                "error" => "El genero del videojuego es obligatorio"
            ]
        ];
    }

    // Recuperamos los videojuegos actuales.
    $videojuegos = leerJson($archivo);

    // Creamos el nuevo videojuego con un id consecutivo.
    $nuevoVideojuego = [
        "id" => generarNuevoId($videojuegos),
        "titulo" => $datosEntrada["titulo"],
        "genero" => $datosEntrada["genero"]
    ];

    // Anadimos el nuevo registro al array.
    $videojuegos[] = $nuevoVideojuego;

    // Guardamos el array completo otra vez en el archivo JSON.
    guardarJson($archivo, $videojuegos);

    // 201 significa "creado correctamente".
    return [
        "codigo" => 201,
        "datos" => [
            "mensaje" => "Videojuego anadido correctamente",
            "videojuego" => $nuevoVideojuego
        ]
    ];
}


function leerJson($archivo) {
    // Si el archivo todavia no existe, el servicio responde con lista vacia.
    if (!file_exists($archivo)) {
        return [];
    }

    $contenido = file_get_contents($archivo);
    $datos = json_decode($contenido, true);

    // Si el JSON no se puede convertir a array, evitamos romper la respuesta.
    if ($datos === null) {
        return [];
    }

    return $datos;
}


function guardarJson($archivo, $datos) {
    // Convierte el array PHP a JSON legible y lo escribe en disco.
    file_put_contents(
        $archivo,
        json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}


function generarNuevoId($datos) {
    /*
        Busca el id mas alto y devuelve el siguiente.
        Si el JSON esta vacio, el primer id sera 1.
    */
    $mayorId = 0;

    foreach ($datos as $elemento) {
        if ($elemento["id"] > $mayorId) {
            $mayorId = $elemento["id"];
        }
    }

    return $mayorId + 1;
}
