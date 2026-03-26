<?php 
header("Content-Type: text/plain; charset=utf-8");

// -------------------------------------------------------------
// 1) POST simple
// -------------------------------------------------------------

if(isset($_POST["accion"]) && $_POST["accion"] === "post_form"){
  $nombre = $_POST["nombre"] ?? "No recibido";
  $edad = $_POST["edad"] ?? "No recibimos la edad";
  echo "Post  recibido  Nombre = $nombre Edad = $edad";
  exit;
}
// -------------------------------------------------------------
// 2) Get
// -------------------------------------------------------------

if(isset($_GET["accion"])&& $_GET["accion"]==="get_form"){
  $nombre2 = $_GET["nombre2"] ?? "No se recibio";
  $edad2 = $_GET["edad2"] ?? "tampoco se recibio edad";
  echo "GET  recibido  Nombre = $nombre2   Edad = $edad2";
  exit;
}
// -------------------------------------------------------------
// 3) POST con JSON → Respuesta en texto
// -------------------------------------------------------------
$rawJSON = file_get_contents("php://input");
$data = json_decode($rawJSON, true);

if (isset($data["accion"]) && $data["accion"] === "post_json") {
    $nombre = $data["persona"]["nombre"] ?? "Sin nombre";
    $edad = $data["persona"]["edad"] ?? -1;

       echo "Nombre :  $nombre, tienes $edad años";
    exit;
}

// -------------------------------------------------------------
// 4) POST con JSON → Respuesta en JSON (nuevo botón)
// -------------------------------------------------------------
if (isset($data["accion"]) && $data["accion"] === "json_datos_json") {

    header("Content-Type: application/json; charset=utf-8");

    $nombre = $data["persona"]["nombre"] ?? "Sin nombre";
    $edad = $data["persona"]["edad"] ?? -1;


    // Construimos una respuesta JSON
    $respuesta = [
        "nombre" => $nombre,
        "edad"   => $edad,
        
    ];

    echo json_encode($respuesta, JSON_PRETTY_PRINT);
    exit;
}

echo "No se ha reconocido la petición.";
?>