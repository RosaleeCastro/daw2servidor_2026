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

    // TODO 2:
    // Seleccionar la base de datos llamada Videojuegos.

    // TODO 3:
    // Seleccionar la colección llamada JuegosBase.

    $filtro = [
        "precio_base" => ['$lte' => $precioMax],
        "fecha_lanzamiento" => ['$gt' => $fechaMin]
    ];

    // TODO 4:
    // Ejecutar la consulta sobre la colección usando el filtro anterior (pasaselo como parámetro)
    // El resultado debe guardarse en una variable para poder recorrerlo después.

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