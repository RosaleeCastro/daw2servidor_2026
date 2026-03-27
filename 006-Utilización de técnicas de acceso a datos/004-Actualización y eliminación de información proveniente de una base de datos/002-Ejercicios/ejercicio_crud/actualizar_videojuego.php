<?php
require_once "conexion.php";

if (!isset($pdo)) {
    die("No se pudo establecer la conexión. " . $mensaje);
}

$id = $_POST["id"] ?? "";

if ($id === "") {
    die("El ID es obligatorio.");
}

// Primero recuperamos la fila actual para conservar los valores
// de los campos que el usuario no haya modificado.
$sqlActual = "SELECT * FROM videojuego WHERE id_videojuego = :id";
$stmtActual = $pdo->prepare($sqlActual);
$stmtActual->execute([
    ":id" => $id
]);

$filaActual = $stmtActual->fetch(PDO::FETCH_ASSOC);

if (!$filaActual) {
    die("No se encontró el videojuego a modificar.");
}

$titulo = trim($_POST["titulo"] ?? "");
$titulo = ($titulo !== "") ? $titulo : $filaActual["titulo"];

$fecha_lanzamiento = ($_POST["fecha_lanzamiento"] !== "")
    ? $_POST["fecha_lanzamiento"]
    : $filaActual["fecha_lanzamiento"];

$pegi = ($_POST["pegi"] !== "")
    ? $_POST["pegi"]
    : $filaActual["pegi"];

$precio_base = ($_POST["precio_base"] !== "")
    ? $_POST["precio_base"]
    : $filaActual["precio_base"];

$motor = trim($_POST["motor"] ?? "");
$motor = ($motor !== "") ? $motor : $filaActual["motor"];

$es_multijugador = isset($_POST["es_multijugador"])
    ? $_POST["es_multijugador"]
    : $filaActual["es_multijugador"];

$id_estudio = ($_POST["id_estudio"] !== "")
    ? $_POST["id_estudio"]
    : $filaActual["id_estudio"];

$id_juego_padre = ($_POST["id_juego_padre"] !== "")
    ? $_POST["id_juego_padre"]
    : $filaActual["id_juego_padre"];

$descripcion = trim($_POST["descripcion"] ?? "");
$descripcion = ($descripcion !== "") ? $descripcion : $filaActual["descripcion"];

$sql = "UPDATE videojuego
        SET titulo = :titulo,
            fecha_lanzamiento = :fecha_lanzamiento,
            pegi = :pegi,
            precio_base = :precio_base,
            motor = :motor,
            es_multijugador = :es_multijugador,
            id_estudio = :id_estudio,
            id_juego_padre = :id_juego_padre,
            descripcion = :descripcion
        WHERE id_videojuego = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":id" => $id,
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

$sqlSelect = "SELECT * FROM videojuego WHERE id_videojuego = :id";
$stmtSelect = $pdo->prepare($sqlSelect);
$stmtSelect->execute([
    ":id" => $id
]);

$fila = $stmtSelect->fetch(PDO::FETCH_ASSOC);

if (!$fila) {
    die("No se encontró el videojuego actualizado.");
}

echo "<h2>Videojuego actualizado</h2>";
echo "ID: " . $fila["id_videojuego"] . "<br>";
echo "Título: " . $fila["titulo"] . "<br>";
echo "Fecha: " . $fila["fecha_lanzamiento"] . "<br>";
echo "PEGI: " . $fila["pegi"] . "<br>";
echo "Precio: " . $fila["precio_base"] . "<br>";
echo "Motor: " . $fila["motor"] . "<br>";
echo "Multijugador: " . $fila["es_multijugador"] . "<br>";
echo "Estudio: " . $fila["id_estudio"] . "<br>";
echo "Juego padre: " . $fila["id_juego_padre"] . "<br>";
echo "Descripción: " . $fila["descripcion"] . "<br>";
?>
