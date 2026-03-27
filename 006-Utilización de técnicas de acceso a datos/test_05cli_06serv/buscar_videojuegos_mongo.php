<?php
header("Content-Type: application/json; charset=utf-8");

require 'vendor/autoload.php';

use MongoDB\Client;


$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$precioMax = (float)($data["precio_max"] ?? 0);
$anioMin = (int)($data["anio_min"] ?? 0);
$fechaMin = $anioMin . "-12-31";

try {
    // TODO 1:
    // Crear la conexión con el servidor MongoDB local.
    $client = new Client("mongodb://localhost:27017");
    // TODO 2:
    // Seleccionar la base de datos llamada Videojuegos.
    $db = $client->Videojuegos;
    // TODO 3:
    // Seleccionar la colección llamada JuegosBase.
    $collection = $db->JuegosBase;

    $filtro = [
        "precio_base" => ['$lte' => $precioMax],
        "fecha_lanzamiento" => ['$gt' => $fechaMin]
    ];

    // TODO 4:
    // Ejecutar la consulta sobre la colección usando el filtro anterior (pasaselo como parámetro)
    // El resultado debe guardarse en una variable para poder recorrerlo después.
    $cursor = $collection->find($filtro);

    $juegos = [];

    // TODO 5:
    // Recorrer todos los documentos devueltos por MongoDB
    // y construir el array $juegos.
    //
    // De cada documento se deben extraer estos campos:
    // - titulo
    // - fecha_lanzamiento
    // - pegi
    // - precio_base
    // - motor
    // - genero
    // - descripcion
    //
    // Si algún campo no existe, devolver cadena vacía como valor por defecto.
$juegos = [];

foreach ($cursor as $juego) {
    $juegos[] = [
        "titulo" => isset($juego["titulo"]) ? $juego["titulo"] : "",
        "fecha_lanzamiento" => isset($juego["fecha_lanzamiento"]) ? $juego["fecha_lanzamiento"] : "",
        "pegi" => isset($juego["pegi"]) ? $juego["pegi"] : "",
        "precio_base" => isset($juego["precio_base"]) ? $juego["precio_base"] : "",
        "motor" => isset($juego["motor"]) ? $juego["motor"] : "",
        "genero" => isset($juego["genero"]) ? $juego["genero"] : "",
        "descripcion" => isset($juego["descripcion"]) ? $juego["descripcion"] : ""
    ];
}

    echo json_encode([
        "ok" => true,
        "total" => count($juegos),
        "juegos" => $juegos
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}