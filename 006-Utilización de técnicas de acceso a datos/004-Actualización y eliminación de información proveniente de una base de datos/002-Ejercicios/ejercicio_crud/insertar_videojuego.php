<?php
require_once "conexion.php";

if (!isset($pdo)) {
    die("No se pudo establecer la conexión. " . $mensaje);
}

$titulo = trim($_POST["titulo"] ?? "");
$fecha_lanzamiento = $_POST["fecha_lanzamiento"] ?: null;
$pegi = ($_POST["pegi"] !== "") ? $_POST["pegi"] : null;
$precio_base = ($_POST["precio_base"] !== "") ? $_POST["precio_base"] : null;
$motor = trim($_POST["motor"] ?? "") ?: null;
$es_multijugador = $_POST["es_multijugador"] ?? 0;
$id_estudio = ($_POST["id_estudio"] !== "") ? $_POST["id_estudio"] : null;
$id_juego_padre = ($_POST["id_juego_padre"] !== "") ? $_POST["id_juego_padre"] : null;
$descripcion = trim($_POST["descripcion"] ?? "") ?: null;

if ($titulo === "") {
    die("El título es obligatorio.");
}

$sql = "INSERT INTO videojuego
        (titulo, fecha_lanzamiento, pegi, precio_base, motor, es_multijugador, id_estudio, id_juego_padre, descripcion)
        VALUES
        (:titulo, :fecha_lanzamiento, :pegi, :precio_base, :motor, :es_multijugador, :id_estudio, :id_juego_padre, :descripcion)";

// obtener el id del ultimo elemento insertado
$idNuevo = $pdo->lastInsertId();
$sqlSelect = "SELECT * FROM videojuego WHERE id_videojuego = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":titulo" => $titulo,
    ":fecha_lanzamiento" => $fecha_lanzamiento,
    ":pegi" => $pegi,
    ":precio_base" => $precio_base,
    ":motor" => $motor,
    ":es_multijugador" => $es_multijugador,
    ":id_estudio" => $id_estudio,
    ":id_juego_padre" => $id_juego_padre,
    ":descripcion" => $descripcion
]);

$stmtSelect = $pdo->prepare($sqlSelect);
$stmtSelect->execute([
    ":id" => $idNuevo
]);

$fila = $stmtSelect->fetch(PDO::FETCH_ASSOC);

echo "Videojuego insertado correctamente.";
?>