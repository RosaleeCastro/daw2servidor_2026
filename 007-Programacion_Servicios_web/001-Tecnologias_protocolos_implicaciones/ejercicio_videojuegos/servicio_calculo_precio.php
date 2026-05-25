<?php
/*
 * Servicio web: cálculo de precio.
 *
 * Este servicio NO necesita conectarse a MySQL porque solo realiza cálculos
 * con los datos que recibe desde videoJuegos.html.
 *
 * Lo usa:
 * - videoJuegos.html, función JavaScript calcularPrecio().
 *
 * Entrada esperada:
 * {
 *   "precio": 59.99,
 *   "cantidad": 2,
 *   "descuento": 10,
 *   "iva": 21
 * }
 *
 * Significado:
 * - precio: precio unitario del videojuego.
 * - cantidad: número de unidades.
 * - descuento: porcentaje de descuento. Ejemplo: 10 significa 10%.
 * - iva: porcentaje de IVA. Ejemplo: 21 significa 21%.
 *
 * Respuesta:
 * - subtotal: precio * cantidad.
 * - importe_descuento: dinero descontado.
 * - base_imponible: subtotal después de aplicar descuento.
 * - importe_iva: IVA calculado sobre la base imponible.
 * - total: precio final a pagar.
 */

// Indicamos al navegador que la respuesta será JSON.
header("Content-Type: application/json; charset=utf-8");

// Leemos el JSON enviado por fetch().
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Recogemos los campos esperados.
$precio = $data["precio"] ?? null;
$cantidad = $data["cantidad"] ?? null;
$descuento = $data["descuento"] ?? null;
$iva = $data["iva"] ?? null;

// Validamos que todos los datos hayan llegado.
if ($precio === null || $cantidad === null || $descuento === null || $iva === null) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes enviar precio, cantidad, descuento e IVA"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Validamos que todos los datos sean numéricos.
if (!is_numeric($precio) || !is_numeric($cantidad) || !is_numeric($descuento) || !is_numeric($iva)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Todos los datos deben ser numéricos"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Convertimos los datos a tipos adecuados para calcular.
$precio = (float)$precio;
$cantidad = (int)$cantidad;
$descuento = (float)$descuento;
$iva = (float)$iva;

// Validaciones de rango.
if ($precio < 0) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "El precio no puede ser negativo"
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($cantidad < 1) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "La cantidad debe ser mayor o igual que 1"
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($descuento < 0 || $descuento > 100) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "El descuento debe estar entre 0 y 100"
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($iva < 0 || $iva > 100) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "El IVA debe estar entre 0 y 100"
    ], JSON_PRETTY_PRINT);
    exit;
}

/*
 * Cálculos:
 *
 * subtotal = precio unitario * cantidad
 * importe_descuento = subtotal * porcentaje descuento
 * base_imponible = subtotal - descuento
 * importe_iva = base imponible * porcentaje IVA
 * total = base imponible + IVA
 */
$subtotal = $precio * $cantidad;
$importeDescuento = $subtotal * ($descuento / 100);
$baseImponible = $subtotal - $importeDescuento;
$importeIva = $baseImponible * ($iva / 100);
$total = $baseImponible + $importeIva;

// Redondeamos a 2 decimales porque estamos trabajando con importes.
$subtotal = round($subtotal, 2);
$importeDescuento = round($importeDescuento, 2);
$baseImponible = round($baseImponible, 2);
$importeIva = round($importeIva, 2);
$total = round($total, 2);

// Respuesta correcta para que videoJuegos.html la pueda mostrar.
echo json_encode([
    "ok" => true,
    "precio_unitario" => $precio,
    "cantidad" => $cantidad,
    "descuento" => $descuento,
    "iva" => $iva,
    "subtotal" => $subtotal,
    "importe_descuento" => $importeDescuento,
    "base_imponible" => $baseImponible,
    "importe_iva" => $importeIva,
    "total" => $total
], JSON_PRETTY_PRINT);
?>
