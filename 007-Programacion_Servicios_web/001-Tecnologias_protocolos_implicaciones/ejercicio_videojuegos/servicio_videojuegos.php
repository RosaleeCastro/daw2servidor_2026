<?php
/*
 * Servicio web: videojuegos.
 *
 * Este servicio tiene dos usos:
 *
 * 1. Devolver todos los videojuegos.
 *    - Lo usa videoJuegos.html en la función cargarVideojuegos().
 *    - No necesita recibir datos.
 *
 * 2. Devolver videojuegos filtrados por precio máximo.
 *    - Lo usa videoJuegos.html en la función filtrarPrecio().
 *    - Recibe un JSON con precio_maximo.
 *
 * Entrada opcional para filtrar:
 * {
 *   "precio_maximo": 50
 * }
 *
 * Se conecta con:
 * - conexion_videojuegos.php para obtener una conexión PDO.
 * - Tabla videojuego de la base de datos videojuegos_asir.
 */

// Indicamos que este archivo siempre responde JSON.
header("Content-Type: application/json; charset=utf-8");

require_once "conexion_videojuegos.php";

// Leemos el cuerpo de la petición por si llega JSON desde fetch().
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Si llega precio_maximo, el servicio filtrará. Si no llega, listará todo.
$precioMaximo = $data["precio_maximo"] ?? null;

// También permitimos probar el filtro desde navegador con ?precio_maximo=50.
if ($precioMaximo === null && isset($_GET["precio_maximo"])) {
    $precioMaximo = $_GET["precio_maximo"];
}

// Validación del filtro solo si se ha enviado.
if ($precioMaximo !== null && (!is_numeric($precioMaximo) || (float)$precioMaximo < 0)) {
    http_response_code(400);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Debes enviar un precio_maximo válido"
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // Abrimos conexión a MySQL usando la función reutilizable.
    $pdo = obtenerPDO();

    /*
     * Consulta base.
     *
     * Si tu tabla tiene otros nombres, cambia aquí:
     * La base videojuegos_asir tiene:
     * - tabla: videojuego
     * - id: id_videojuego
     * - título: titulo
     * - precio: precio_base
     *
     * Usamos "precio_base AS precio" para que el HTML pueda trabajar con un
     * campo sencillo llamado precio.
     */
    $sql = "
        SELECT id_videojuego, titulo, precio_base AS precio
        FROM videojuego
    ";

    $parametros = [];

    // Si hay precio máximo, añadimos WHERE y usamos consulta preparada.
    if ($precioMaximo !== null) {
        $sql .= " WHERE precio_base <= :precio_maximo";
        $parametros[":precio_maximo"] = (float)$precioMaximo;
    }

    // Ordenamos el resultado para que el select salga fácil de leer.
    $sql .= " ORDER BY titulo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $videojuegos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "ok" => true,
        "videojuegos" => $videojuegos
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al obtener videojuegos",
        "detalle" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
