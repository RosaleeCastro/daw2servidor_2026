<?php
/*
 * Servicio web: creación de pedidos.
 *
 * Recibe los datos de compra, comprueba que el producto existe y que hay stock,
 * guarda el pedido y descuenta las unidades vendidas.
 *
 * Entrada esperada:
 * {
 *   "cliente": "Ana",
 *   "id_producto": 1,
 *   "cantidad": 2
 * }
 *
 * Se conecta con:
 * - conexion_mysql.php para obtener una conexión PDO.
 * - Tabla producto para obtener nombre y precio.
 * - Tabla stock para consultar y descontar unidades.
 * - Tabla pedido para registrar la compra.
 *
 * Lo consume:
 * - tienda.html, función JavaScript crearPedido().
 */

header("Content-Type: application/json; charset=utf-8");

require_once "conexion_mysql.php";

// Leemos el JSON enviado por fetch() desde tienda.html.
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$cliente = trim($data["cliente"] ?? "");
$idProducto = $data["id_producto"] ?? null;
$cantidad = $data["cantidad"] ?? null;

// Validamos campos obligatorios.
if ($cliente === "" || !$idProducto || !$cantidad) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes enviar cliente, id_producto y cantidad"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validamos que producto y cantidad sean números y que la cantidad sea positiva.
if (!is_numeric($idProducto) || !is_numeric($cantidad) || (int)$cantidad < 1) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Los datos numéricos no son válidos"
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = obtenerPDO();

    /*
     * Iniciamos una transacción porque crear un pedido tiene dos operaciones
     * relacionadas: insertar el pedido y descontar stock. O se hacen ambas, o
     * no se hace ninguna.
     */
    $pdo->beginTransaction();

    $sqlProducto = "
        SELECT p.id_producto, p.nombre, p.precio, s.unidades
        FROM producto p
        INNER JOIN stock s ON p.id_producto = s.id_producto
        WHERE p.id_producto = :id_producto
        FOR UPDATE
    ";

    /*
     * FOR UPDATE bloquea esa fila mientras dura la transacción.
     * Es útil si dos clientes intentan comprar el mismo producto a la vez.
     */
    $stmtProducto = $pdo->prepare($sqlProducto);
    $stmtProducto->execute([
        ":id_producto" => (int)$idProducto
    ]);

    $producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

    // Si el producto no existe, cancelamos la transacción.
    if (!$producto) {
        $pdo->rollBack();

        http_response_code(404);
        echo json_encode([
            "ok" => false,
            "mensaje" => "Producto no encontrado"
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $stockActual = (int)$producto["unidades"];
    $cantidad = (int)$cantidad;

    // Si se piden más unidades de las disponibles, no se crea el pedido.
    if ($cantidad > $stockActual) {
        $pdo->rollBack();

        http_response_code(400);
        echo json_encode([
            "ok" => false,
            "mensaje" => "No hay stock suficiente",
            "stock_actual" => $stockActual
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $precioUnitario = (float)$producto["precio"];
    $total = $precioUnitario * $cantidad;

    // Registramos el pedido con los importes calculados en servidor.
    $sqlInsert = "
        INSERT INTO pedido (cliente, id_producto, cantidad, precio_unitario, total)
        VALUES (:cliente, :id_producto, :cantidad, :precio_unitario, :total)
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ":cliente" => $cliente,
        ":id_producto" => (int)$idProducto,
        ":cantidad" => $cantidad,
        ":precio_unitario" => $precioUnitario,
        ":total" => $total
    ]);

    // Descontamos del stock las unidades compradas.
    $sqlUpdateStock = "
        UPDATE stock
        SET unidades = unidades - :cantidad
        WHERE id_producto = :id_producto
    ";

    $stmtUpdateStock = $pdo->prepare($sqlUpdateStock);
    $stmtUpdateStock->execute([
        ":cantidad" => $cantidad,
        ":id_producto" => (int)$idProducto
    ]);

    // Confirmamos definitivamente los cambios en pedido y stock.
    $pdo->commit();

    // Devolvemos un resumen completo para que tienda.html pueda mostrarlo.
    echo json_encode([
        "ok" => true,
        "mensaje" => "Pedido realizado correctamente",
        "id_pedido" => $pdo->lastInsertId(),
        "producto" => $producto["nombre"],
        "cliente" => $cliente,
        "cantidad" => $cantidad,
        "precio_unitario" => $precioUnitario,
        "total" => $total,
        "stock_restante" => $stockActual - $cantidad
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // Si ocurre cualquier error dentro de la transacción, deshacemos cambios.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al crear pedido",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
