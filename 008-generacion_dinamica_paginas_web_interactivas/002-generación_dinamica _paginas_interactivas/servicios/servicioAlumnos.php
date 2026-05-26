<?php

/*
    SERVICIO DE ALUMNOS

    Este archivo contiene la logica concreta de alumnos.
    El controlador lo carga cuando la ruta es:

    controlador.php/alumnos

    Flujo:
    controlador.php
      -> servicioAlumnos($metodo, $datosEntrada)
      -> GET consulta datos/alumnos.json
      -> POST valida, anade y guarda en datos/alumnos.json
      -> devuelve codigo HTTP y datos al controlador
*/

function servicioAlumnos($metodo, $datosEntrada) {
    // Archivo donde se guardan los alumnos. Actua como "base de datos" sencilla.
    $archivo = "datos/alumnos.json";

    // GET /alumnos: consultar todos los alumnos.
    if ($metodo === "GET") {
        return consultarAlumnos($archivo);
    }

    // POST /alumnos: crear un alumno nuevo con los datos recibidos del cliente.
    if ($metodo === "POST") {
        return anadirAlumno($archivo, $datosEntrada);
    }

    // Cualquier otro metodo, como PUT o DELETE, no esta implementado.
    return [
        "codigo" => 405,
        "datos" => [
            "error" => "Metodo no permitido para alumnos"
        ]
    ];
}


function consultarAlumnos($archivo) {
    // Lee el JSON y lo devuelve tal cual para que cliente.html lo pinte.
    $alumnos = leerJsonAlumnos($archivo);

    return [
        "codigo" => 200,
        "datos" => $alumnos
    ];
}


function anadirAlumno($archivo, $datosEntrada) {
    /*
        Validacion de entrada.
        El cliente debe enviar:
        {
            "nombre": "...",
            "curso": "..."
        }
    */
    if (!isset($datosEntrada["nombre"]) || trim($datosEntrada["nombre"]) === "") {
        return [
            "codigo" => 400,
            "datos" => [
                "error" => "El nombre del alumno es obligatorio"
            ]
        ];
    }

    if (!isset($datosEntrada["curso"]) || trim($datosEntrada["curso"]) === "") {
        return [
            "codigo" => 400,
            "datos" => [
                "error" => "El curso del alumno es obligatorio"
            ]
        ];
    }

    // Recuperamos los alumnos actuales antes de anadir el nuevo.
    $alumnos = leerJsonAlumnos($archivo);

    // Creamos el nuevo alumno con un id consecutivo.
    $nuevoAlumno = [
        "id" => generarNuevoIdAlumnos($alumnos),
        "nombre" => $datosEntrada["nombre"],
        "curso" => $datosEntrada["curso"]
    ];

    // Anadimos el nuevo elemento al array completo.
    $alumnos[] = $nuevoAlumno;

    // Guardamos otra vez todo el array en el archivo JSON.
    guardarJsonAlumnos($archivo, $alumnos);

    // 201 significa "creado correctamente".
    return [
        "codigo" => 201,
        "datos" => [
            "mensaje" => "Alumno anadido correctamente",
            "alumno" => $nuevoAlumno
        ]
    ];
}


function leerJsonAlumnos($archivo) {
    // Si el archivo no existe, devolvemos una lista vacia para evitar errores.
    if (!file_exists($archivo)) {
        return [];
    }

    $contenido = file_get_contents($archivo);
    $datos = json_decode($contenido, true);

    // Si el JSON esta vacio o mal formado, usamos una lista vacia.
    if ($datos === null) {
        return [];
    }

    return $datos;
}


function guardarJsonAlumnos($archivo, $datos) {
    // Convierte el array PHP a JSON legible y lo escribe en disco.
    file_put_contents(
        $archivo,
        json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}


function generarNuevoIdAlumnos($datos) {
    /*
        Busca el id mas alto existente y devuelve el siguiente.
        Si no hay alumnos, el mayor sera 0 y el primer id sera 1.
    */
    $mayorId = 0;

    foreach ($datos as $elemento) {
        if ($elemento["id"] > $mayorId) {
            $mayorId = $elemento["id"];
        }
    }

    return $mayorId + 1;
}
