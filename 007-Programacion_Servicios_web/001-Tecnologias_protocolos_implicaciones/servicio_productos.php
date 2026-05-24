<?php
/*
 * Servicio web: catálogo de productos.
 *
 * Este archivo actúa como endpoint para el cliente tienda.html.
 * No recibe parámetros. Consulta la tabla producto y devuelve todos los
 * productos en formato JSON.
 *
 * Se conecta con:
 * - conexion_mysql.php para obtener una conexión PDO.
 * - Tabla producto de la base de datos tienda_servicios.
 *
 * Lo consume:
 * - tienda.html, función JavaScript cargarProductos().
 */

// Indicamos al navegador que la respuesta será JSON en UTF-8.
header("Content-Type: application/json; charset=utf-8");

require_once "conexion_mysql.php";

try {
    // Abrimos la conexión reutilizable definida en conexion_mysql.php.
    $pdo = obtenerPDO();

    // Obtenemos el id, nombre y precio de todos los productos.
    $sql = "SELECT id_producto, nombre, precio FROM producto ORDER BY nombre ASC";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Respuesta correcta para que el cliente pueda rellenar el select.
    echo json_encode([
        "ok" => true,
        "productos" => $productos
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // Si falla la base de datos, devolvemos error HTTP 500 y un JSON uniforme.
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener productos",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
