<?php 
header("Content-Type: text/plain; charset=utf-8");

//Post simple

if(isset($_POST["accion"]) && $_POST["accion"] === "post_form"){
  $nombre = $_POST["nombre"] ?? "No recibido";
  $edad = $_POST["edad"] ?? "No recibimos la edad";
  echo "Post  recibido  Nombre = $nombre Edad = $edad";
  exit;
}

?>