<?php
/*
 * Servicio web: disponibilidad de videojuegos.
 *
 * Este servicio tiene dos usos:
 *
 * 1. Consultar tiendas donde está disponible un videojuego.
 *    - Lo usa videoJuegos.html en la función consultarDisponibilidad().
 *    - Recibe id_videojuego.
 *
 * 2. Restar unidades del stock total de un videojuego.
 *    - Lo usa videoJuegos.html en la función restarStock().
 *    - Recibe id_videojuego, cantidad y accion = "restar_stock".
 *
 * Entrada para consultar disponibilidad:
 * {
 *   "id_videojuego": 1
 * }
 *
 * Entrada para restar stock:
 * {
 *   "accion": "restar_stock",
 *   "id_videojuego": 1,
 *   "cantidad": 2
 * }
 *
 * Se conecta con:
 * - conexion_videojuegos.php para obtener una conexión PDO.
 * - Tabla disponibilidad para consultar precio, stock y relación tienda/juego.
 * - Tabla tienda para mostrar el nombre de la tienda.
 * - Tabla videojuego para comprobar que el videojuego existe.
 */

// Indicamos que este servicio siempre responde JSON.
header("Content-Type: application/json; charset=utf-8");

require_once "conexion_videojuegos.php";

// Leemos el JSON enviado por fetch() desde videoJuegos.html.
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Datos comunes a las dos operaciones.
$accion = $data["accion"] ?? "consultar";
$idVideojuego = $data["id_videojuego"] ?? null;
$cantidad = $data["cantidad"] ?? null;

// Validamos que llegue un id de videojuego correcto.
if (!$idVideojuego || !is_numeric($idVideojuego)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes enviar un id_videojuego válido"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Si se quiere restar stock, también validamos cantidad.
if ($accion === "restar_stock" && (!$cantidad || !is_numeric($cantidad) || (int)$cantidad < 1)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes enviar una cantidad válida"
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // Abrimos conexión a la base videojuegos_asir.
    $pdo = obtenerPDO();

    /*
     * Comprobamos primero que el videojuego existe.
     * Así podemos devolver 404 si el id no corresponde a ningún juego.
     */
    $sqlVideojuego = "
        SELECT id_videojuego, titulo
        FROM videojuego
        WHERE id_videojuego = :id_videojuego
    ";

    $stmtVideojuego = $pdo->prepare($sqlVideojuego);
    $stmtVideojuego->execute([
        ":id_videojuego" => (int)$idVideojuego
    ]);

    $videojuego = $stmtVideojuego->fetch(PDO::FETCH_ASSOC);

    if (!$videojuego) {
        http_response_code(404);

        echo json_encode([
            "ok" => false,
            "mensaje" => "Videojuego no encontrado"
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /*
     * Si no se ha pedido restar stock, solo consultamos las tiendas donde el
     * videojuego aparece en la tabla disponibilidad.
     */
    if ($accion !== "restar_stock") {
        $sqlDisponibilidad = "
            SELECT
                t.id_tienda,
                t.nombre AS tienda,
                t.tipo,
                t.pais,
                t.ciudad,
                d.precio,
                d.stock,
                d.url_producto
            FROM disponibilidad d
            INNER JOIN tienda t ON d.id_tienda = t.id_tienda
            WHERE d.id_videojuego = :id_videojuego
            ORDER BY t.nombre ASC
        ";

        $stmtDisponibilidad = $pdo->prepare($sqlDisponibilidad);
        $stmtDisponibilidad->execute([
            ":id_videojuego" => (int)$idVideojuego
        ]);

        $tiendas = $stmtDisponibilidad->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "ok" => true,
            "videojuego" => $videojuego["titulo"],
            "tiendas" => $tiendas
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /*
     * Para restar stock usamos una transacción.
     *
     * Motivo:
     * - Primero comprobamos cuánto stock total hay.
     * - Después actualizamos una o varias tiendas.
     * - Si algo falla, se deshace todo con rollBack().
     */
    $pdo->beginTransaction();

    /*
     * Bloqueamos las filas de disponibilidad de este videojuego.
     *
     * FOR UPDATE evita que dos peticiones descuenten el mismo stock a la vez.
     */
    $sqlStock = "
        SELECT id_tienda, stock
        FROM disponibilidad
        WHERE id_videojuego = :id_videojuego
          AND stock > 0
        ORDER BY stock DESC, id_tienda ASC
        FOR UPDATE
    ";

    $stmtStock = $pdo->prepare($sqlStock);
    $stmtStock->execute([
        ":id_videojuego" => (int)$idVideojuego
    ]);

    $filasStock = $stmtStock->fetchAll(PDO::FETCH_ASSOC);

    $stockTotal = 0;

    foreach ($filasStock as $fila) {
        $stockTotal += (int)$fila["stock"];
    }

    $cantidad = (int)$cantidad;

    // Si no hay unidades suficientes en total, cancelamos la operación.
    if ($cantidad > $stockTotal) {
        $pdo->rollBack();

        http_response_code(400);

        echo json_encode([
            "ok" => false,
            "mensaje" => "No hay stock suficiente",
            "stock_actual" => $stockTotal
        ], JSON_PRETTY_PRINT);
        exit;
    }

    /*
     * Restamos la cantidad entre las tiendas disponibles.
     *
     * Ejemplo:
     * - Se quieren restar 7 unidades.
     * - Tienda A tiene 5, tienda B tiene 4.
     * - Restamos 5 en A y 2 en B.
     */
    $cantidadPendiente = $cantidad;

    $sqlActualizar = "
        UPDATE disponibilidad
        SET stock = stock - :cantidad
        WHERE id_videojuego = :id_videojuego
          AND id_tienda = :id_tienda
    ";

    $stmtActualizar = $pdo->prepare($sqlActualizar);

    foreach ($filasStock as $fila) {
        if ($cantidadPendiente === 0) {
            break;
        }

        $stockTienda = (int)$fila["stock"];
        $cantidadARestar = min($stockTienda, $cantidadPendiente);

        $stmtActualizar->execute([
            ":cantidad" => $cantidadARestar,
            ":id_videojuego" => (int)$idVideojuego,
            ":id_tienda" => (int)$fila["id_tienda"]
        ]);

        $cantidadPendiente -= $cantidadARestar;
    }

    // Confirmamos los descuentos de stock.
    $pdo->commit();

    $stockRestante = $stockTotal - $cantidad;

    echo json_encode([
        "ok" => true,
        "mensaje" => "Stock actualizado correctamente",
        "videojuego" => $videojuego["titulo"],
        "cantidad_restada" => $cantidad,
        "stock_restante" => $stockRestante
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // Si falla algo durante la transacción, deshacemos cualquier cambio.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al consultar disponibilidad",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
