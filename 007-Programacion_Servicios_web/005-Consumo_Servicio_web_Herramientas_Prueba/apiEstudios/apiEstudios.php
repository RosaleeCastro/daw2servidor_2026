<?php
/*
 * API REST de estudios de videojuegos.
 *
 * Este archivo es el servidor de la API. Recibe peticiones HTTP, consulta o
 * modifica la tabla estudio de la base de datos MySQL videojuegos_asir y
 * responde siempre en JSON.
 *
 * Rutas principales:
 * - GET    apiEstudios.php/estudios
 * - GET    apiEstudios.php/estudios/{id}
 * - POST   apiEstudios.php/estudios
 * - PATCH  apiEstudios.php/estudios/{id}
 * - DELETE apiEstudios.php/estudios/{id}
 *
 * Campos reales de la tabla estudio:
 * - id_estudio
 * - nombre
 * - pais
 * - ciudad
 * - fundado_en
 * - web
 */

header("Content-Type: application/json; charset=utf-8");

// ----------------------------------------------------
// Conexion a MySQL
// ----------------------------------------------------
function obtenerPDO() {
    $host = "127.0.0.1";
    $port = "3306";
    $dbname = "videojuegos_asir";
    $user = "root";
    $pass = "";

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
}

// ----------------------------------------------------
// Respuesta JSON uniforme
// ----------------------------------------------------
function responder($codigo, $datos = null) {
    http_response_code($codigo);

    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// ----------------------------------------------------
// Leer JSON del cuerpo de la peticion
// ----------------------------------------------------
function leerJSONBody() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if ($raw !== "" && $data === null) {
        responder(400, [
            "error" => "JSON invalido"
        ]);
    }

    return $data ?? [];
}

// ----------------------------------------------------
// Obtener ruta REST despues de apiEstudios.php
// Ejemplos:
// apiEstudios.php/estudios
// apiEstudios.php/estudios/1
// ----------------------------------------------------
function obtenerRuta() {
    $pathInfo = $_SERVER["PATH_INFO"] ?? "";

    if ($pathInfo !== "") {
        return trim($pathInfo, "/");
    }

    return "";
}

// ----------------------------------------------------
// Convertir tipos para que JSON devuelva numeros correctos
// ----------------------------------------------------
function normalizarEstudio($estudio) {
    if (!$estudio) {
        return null;
    }

    $estudio["id"] = (int)$estudio["id"];
    $estudio["fundado_en"] = $estudio["fundado_en"] !== null
        ? (int)$estudio["fundado_en"]
        : null;

    return $estudio;
}

// ----------------------------------------------------
// Buscar estudio por ID
// ----------------------------------------------------
function buscarEstudioPorId($pdo, $id) {
    $sql = "
        SELECT
            id_estudio AS id,
            nombre,
            pais,
            ciudad,
            fundado_en,
            web
        FROM estudio
        WHERE id_estudio = :id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":id" => $id
    ]);

    return normalizarEstudio($stmt->fetch(PDO::FETCH_ASSOC));
}

// ----------------------------------------------------
// Inicio del procesamiento REST
// ----------------------------------------------------
try {
    $pdo = obtenerPDO();

    /*
     * En REST, metodo HTTP + ruta deciden la operacion.
     *
     * Ejemplo:
     * - GET /estudios lista estudios.
     * - GET /estudios/2 consulta el estudio 2.
     * - POST /estudios crea un estudio.
     */
    $metodo = $_SERVER["REQUEST_METHOD"];
    $ruta = obtenerRuta();
    $partesRuta = explode("/", trim($ruta, "/"));

    $recurso = $partesRuta[0] ?? "";
    $id = $partesRuta[1] ?? null;

    if ($recurso !== "estudios") {
        responder(404, [
            "error" => "Recurso no encontrado. Usa /estudios o /estudios/{id}"
        ]);
    }

    // ----------------------------------------------------
    // GET /estudios
    // Filtros opcionales:
    // ?pais=Japon
    // ?ciudad=Kioto
    // ?fundadoDesde=1980
    // ----------------------------------------------------
    if ($metodo === "GET" && $id === null) {
        $sql = "
            SELECT
                id_estudio AS id,
                nombre,
                pais,
                ciudad,
                fundado_en,
                web
            FROM estudio
            WHERE 1 = 1
        ";

        $params = [];

        if (isset($_GET["pais"]) && trim($_GET["pais"]) !== "") {
            $sql .= " AND pais LIKE :pais";
            $params[":pais"] = "%" . trim($_GET["pais"]) . "%";
        }

        if (isset($_GET["ciudad"]) && trim($_GET["ciudad"]) !== "") {
            $sql .= " AND ciudad LIKE :ciudad";
            $params[":ciudad"] = "%" . trim($_GET["ciudad"]) . "%";
        }

        if (isset($_GET["fundadoDesde"]) && $_GET["fundadoDesde"] !== "") {
            if (!is_numeric($_GET["fundadoDesde"])) {
                responder(400, [
                    "error" => "fundadoDesde debe ser numerico"
                ]);
            }

            $sql .= " AND fundado_en IS NOT NULL AND fundado_en >= :fundadoDesde";
            $params[":fundadoDesde"] = (int)$_GET["fundadoDesde"];
        }

        $sql .= " ORDER BY id_estudio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($estudios as &$estudio) {
            $estudio = normalizarEstudio($estudio);
        }

        responder(200, [
            "total" => count($estudios),
            "estudios" => $estudios
        ]);
    }

    // ----------------------------------------------------
    // GET /estudios/{id}
    // ----------------------------------------------------
    if ($metodo === "GET" && $id !== null) {
        if (!is_numeric($id)) {
            responder(400, [
                "error" => "El ID debe ser numerico"
            ]);
        }

        $estudio = buscarEstudioPorId($pdo, (int)$id);

        if (!$estudio) {
            responder(404, [
                "error" => "Estudio no encontrado"
            ]);
        }

        responder(200, $estudio);
    }

    // ----------------------------------------------------
    // POST /estudios
    // Crea un estudio nuevo
    // ----------------------------------------------------
    if ($metodo === "POST" && $id === null) {
        $data = leerJSONBody();

        $nombre = trim($data["nombre"] ?? "");
        $pais = trim($data["pais"] ?? "");
        $ciudad = trim($data["ciudad"] ?? "");
        $fundadoEn = $data["fundado_en"] ?? null;
        $web = trim($data["web"] ?? "");

        if ($nombre === "") {
            responder(400, [
                "error" => "El campo nombre es obligatorio"
            ]);
        }

        if ($fundadoEn !== null && $fundadoEn !== "" && !is_numeric($fundadoEn)) {
            responder(400, [
                "error" => "El campo fundado_en debe ser numerico"
            ]);
        }

        $sql = "
            INSERT INTO estudio (nombre, pais, ciudad, fundado_en, web)
            VALUES (:nombre, :pais, :ciudad, :fundado_en, :web)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":nombre" => $nombre,
            ":pais" => $pais !== "" ? $pais : null,
            ":ciudad" => $ciudad !== "" ? $ciudad : null,
            ":fundado_en" => ($fundadoEn !== null && $fundadoEn !== "") ? (int)$fundadoEn : null,
            ":web" => $web !== "" ? $web : null
        ]);

        $nuevoId = (int)$pdo->lastInsertId();
        $estudioCreado = buscarEstudioPorId($pdo, $nuevoId);

        responder(201, $estudioCreado);
    }

    // ----------------------------------------------------
    // PATCH /estudios/{id}
    // Actualiza solo los campos recibidos
    // ----------------------------------------------------
    if ($metodo === "PATCH" && $id !== null) {
        if (!is_numeric($id)) {
            responder(400, [
                "error" => "El ID debe ser numerico"
            ]);
        }

        $estudioActual = buscarEstudioPorId($pdo, (int)$id);

        if (!$estudioActual) {
            responder(404, [
                "error" => "Estudio no encontrado"
            ]);
        }

        $data = leerJSONBody();

        if (empty($data)) {
            responder(400, [
                "error" => "Debes enviar al menos un campo para actualizar"
            ]);
        }

        $campos = [];
        $params = [
            ":id" => (int)$id
        ];

        if (isset($data["nombre"])) {
            $nombre = trim($data["nombre"]);

            if ($nombre === "") {
                responder(400, [
                    "error" => "El campo nombre no puede estar vacio"
                ]);
            }

            $campos[] = "nombre = :nombre";
            $params[":nombre"] = $nombre;
        }

        if (isset($data["pais"])) {
            $pais = trim($data["pais"]);
            $campos[] = "pais = :pais";
            $params[":pais"] = $pais !== "" ? $pais : null;
        }

        if (isset($data["ciudad"])) {
            $ciudad = trim($data["ciudad"]);
            $campos[] = "ciudad = :ciudad";
            $params[":ciudad"] = $ciudad !== "" ? $ciudad : null;
        }

        if (isset($data["fundado_en"])) {
            if ($data["fundado_en"] !== null && $data["fundado_en"] !== "" && !is_numeric($data["fundado_en"])) {
                responder(400, [
                    "error" => "El campo fundado_en debe ser numerico"
                ]);
            }

            $campos[] = "fundado_en = :fundado_en";
            $params[":fundado_en"] = ($data["fundado_en"] !== null && $data["fundado_en"] !== "")
                ? (int)$data["fundado_en"]
                : null;
        }

        if (isset($data["web"])) {
            $web = trim($data["web"]);
            $campos[] = "web = :web";
            $params[":web"] = $web !== "" ? $web : null;
        }

        if (empty($campos)) {
            responder(400, [
                "error" => "No se ha enviado ningun campo valido para actualizar"
            ]);
        }

        $sql = "
            UPDATE estudio
            SET " . implode(", ", $campos) . "
            WHERE id_estudio = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        responder(200, buscarEstudioPorId($pdo, (int)$id));
    }

    // ----------------------------------------------------
    // DELETE /estudios/{id}
    // ----------------------------------------------------
    if ($metodo === "DELETE" && $id !== null) {
        if (!is_numeric($id)) {
            responder(400, [
                "error" => "El ID debe ser numerico"
            ]);
        }

        $estudioActual = buscarEstudioPorId($pdo, (int)$id);

        if (!$estudioActual) {
            responder(404, [
                "error" => "Estudio no encontrado"
            ]);
        }

        try {
            $stmt = $pdo->prepare("
                DELETE FROM estudio
                WHERE id_estudio = :id
            ");

            $stmt->execute([
                ":id" => (int)$id
            ]);

            responder(204);
        } catch (PDOException $e) {
            responder(409, [
                "error" => "No se puede eliminar el estudio porque esta relacionado con otros datos",
                "detalle" => $e->getMessage()
            ]);
        }
    }

    responder(405, [
        "error" => "Metodo no permitido para esta ruta"
    ]);

} catch (PDOException $e) {
    responder(500, [
        "error" => "Error interno del servidor",
        "detalle" => $e->getMessage()
    ]);
}
?>
