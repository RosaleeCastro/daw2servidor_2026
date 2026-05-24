<?php
/*
 * Servicio web: consulta de stock.
 *
 * Recibe un JSON con el id del producto y devuelve cuántas unidades quedan.
 *
 * Entrada esperada:
 * {
 *   "id_producto": 1
 * }
 *
 * Se conecta con:
 * - conexion_mysql.php para obtener una conexión PDO.
 * - Tabla producto para saber el nombre.
 * - Tabla stock para saber las unidades disponibles.
 *
 * Lo consume:
 * - tienda.html, función JavaScript consultarStock().
 */

header("Content-Type: application/json; charset=utf-8");

require_once "conexion_mysql.php";

// Leemos el cuerpo raw de la petición y lo convertimos de JSON a array PHP.
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$idProducto = $data["id_producto"] ?? null;

// Validación básica antes de consultar la base de datos.
if (!$idProducto || !is_numeric($idProducto)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes enviar un id_producto válido"
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = obtenerPDO();

    // Unimos producto y stock para devolver nombre y unidades en una consulta.
    $sql = "
        SELECT p.id_producto, p.nombre, s.unidades
        FROM producto p
        INNER JOIN stock s ON p.id_producto = s.id_producto
        WHERE p.id_producto = :id_producto
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":id_producto" => (int)$idProducto
    ]);

    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no existe ese producto, respondemos con 404.
    if (!$producto) {
        http_response_code(404);

        echo json_encode([
            "ok" => false,
            "mensaje" => "Producto no encontrado"
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // Respuesta correcta: nombre del producto y stock como número entero.
    echo json_encode([
        "ok" => true,
        "producto" => $producto["nombre"],
        "stock" => (int)$producto["unidades"]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al consultar stock",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
