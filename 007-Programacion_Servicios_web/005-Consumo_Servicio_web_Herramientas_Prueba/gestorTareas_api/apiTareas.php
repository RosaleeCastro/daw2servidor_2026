<?php
/*
 * API REST Gestor de Tareas.
 *
 * Este archivo implementa el servidor REST del ejercicio gestorTareas.txt.
 * No usa MySQL: guarda las tareas en un archivo tareas.json, igual que el
 * ejemplo de api_libros.
 *
 * Rutas disponibles:
 * - GET    apiTareas.php/tareas
 * - GET    apiTareas.php/tareas/{id}
 * - POST   apiTareas.php/tareas
 * - PATCH  apiTareas.php/tareas/{id}
 * - DELETE apiTareas.php/tareas/{id}
 *
 * Estructura de una tarea:
 * - id
 * - titulo
 * - descripcion
 * - completada
 * - prioridad
 * - fecha_creacion
 */

header("Content-Type: application/json; charset=utf-8");

// Archivo donde se guardan las tareas.
$archivoDatos = __DIR__ . "/tareas.json";

// ----------------------------------------------------
// Crear datos iniciales si tareas.json no existe
// ----------------------------------------------------
if (!file_exists($archivoDatos)) {
    $tareasIniciales = [
        [
            "id" => 1,
            "titulo" => "Repasar APIs REST",
            "descripcion" => "Leer ejemplos de libros, videojuegos y estudios.",
            "completada" => false,
            "prioridad" => "alta",
            "fecha_creacion" => date("Y-m-d H:i:s")
        ],
        [
            "id" => 2,
            "titulo" => "Probar gestor de tareas",
            "descripcion" => "Crear, actualizar y eliminar una tarea desde el cliente HTML.",
            "completada" => false,
            "prioridad" => "media",
            "fecha_creacion" => date("Y-m-d H:i:s")
        ]
    ];

    file_put_contents(
        $archivoDatos,
        json_encode($tareasIniciales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

// ----------------------------------------------------
// Leer tareas desde tareas.json
// ----------------------------------------------------
function leerTareas($archivoDatos) {
    $contenido = file_get_contents($archivoDatos);
    return json_decode($contenido, true) ?? [];
}

// ----------------------------------------------------
// Guardar tareas en tareas.json
// ----------------------------------------------------
function guardarTareas($archivoDatos, $tareas) {
    file_put_contents(
        $archivoDatos,
        json_encode($tareas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

// ----------------------------------------------------
// Responder siempre en JSON
// ----------------------------------------------------
function responder($codigo, $datos = null) {
    http_response_code($codigo);

    if ($datos !== null) {
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// ----------------------------------------------------
// Leer JSON recibido en el cuerpo de la peticion
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
// Validar prioridad
// ----------------------------------------------------
function validarPrioridad($prioridad) {
    return in_array($prioridad, ["baja", "media", "alta"], true);
}

// ----------------------------------------------------
// Obtener metodo HTTP y ruta
// ----------------------------------------------------
$metodo = $_SERVER["REQUEST_METHOD"];

/*
 * Normalmente usamos PATH_INFO:
 * apiTareas.php/tareas
 *
 * Pero algunos entornos XAMPP pueden no pasar bien PATH_INFO. Por eso tambien
 * aceptamos una ruta por query string:
 * apiTareas.php?ruta=/tareas
 */
$ruta = $_SERVER["PATH_INFO"] ?? ($_GET["ruta"] ?? "");
$partesRuta = explode("/", trim($ruta, "/"));

$recurso = $partesRuta[0] ?? "";
$id = $partesRuta[1] ?? null;

if ($recurso !== "tareas") {
    responder(404, [
        "error" => "Recurso no encontrado. Usa /tareas o /tareas/{id}"
    ]);
}

$tareas = leerTareas($archivoDatos);

// ----------------------------------------------------
// GET /tareas
// Devuelve todas las tareas
// Filtros opcionales:
// ?completada=true
// ?prioridad=alta
// ----------------------------------------------------
if ($metodo === "GET" && $id === null) {
    $resultado = $tareas;

    if (isset($_GET["completada"]) && $_GET["completada"] !== "") {
        $valor = strtolower($_GET["completada"]);

        if ($valor !== "true" && $valor !== "false") {
            responder(400, [
                "error" => "El filtro completada debe ser true o false"
            ]);
        }

        $completada = ($valor === "true");
        $resultado = array_values(array_filter($resultado, function ($tarea) use ($completada) {
            return (bool)$tarea["completada"] === $completada;
        }));
    }

    if (isset($_GET["prioridad"]) && $_GET["prioridad"] !== "") {
        $prioridad = strtolower(trim($_GET["prioridad"]));

        if (!validarPrioridad($prioridad)) {
            responder(400, [
                "error" => "La prioridad debe ser baja, media o alta"
            ]);
        }

        $resultado = array_values(array_filter($resultado, function ($tarea) use ($prioridad) {
            return $tarea["prioridad"] === $prioridad;
        }));
    }

    responder(200, [
        "total" => count($resultado),
        "tareas" => $resultado
    ]);
}

// ----------------------------------------------------
// GET /tareas/{id}
// Devuelve una tarea concreta
// ----------------------------------------------------
if ($metodo === "GET" && $id !== null) {
    if (!is_numeric($id)) {
        responder(400, [
            "error" => "El ID debe ser numerico"
        ]);
    }

    foreach ($tareas as $tarea) {
        if ($tarea["id"] == $id) {
            responder(200, $tarea);
        }
    }

    responder(404, [
        "error" => "Tarea no encontrada"
    ]);
}

// ----------------------------------------------------
// POST /tareas
// Crea una tarea nueva
// ----------------------------------------------------
if ($metodo === "POST" && $id === null) {
    $data = leerJSONBody();

    $titulo = trim($data["titulo"] ?? "");
    $descripcion = trim($data["descripcion"] ?? "");
    $completada = $data["completada"] ?? false;
    $prioridad = strtolower(trim($data["prioridad"] ?? "media"));

    if ($titulo === "") {
        responder(400, [
            "error" => "El campo titulo es obligatorio"
        ]);
    }

    if (!validarPrioridad($prioridad)) {
        responder(400, [
            "error" => "La prioridad debe ser baja, media o alta"
        ]);
    }

    $ids = array_column($tareas, "id");
    $nuevoId = empty($ids) ? 1 : max($ids) + 1;

    $nuevaTarea = [
        "id" => $nuevoId,
        "titulo" => $titulo,
        "descripcion" => $descripcion,
        "completada" => (bool)$completada,
        "prioridad" => $prioridad,
        "fecha_creacion" => date("Y-m-d H:i:s")
    ];

    $tareas[] = $nuevaTarea;
    guardarTareas($archivoDatos, $tareas);

    responder(201, $nuevaTarea);
}

// ----------------------------------------------------
// PATCH /tareas/{id}
// Actualiza parcialmente una tarea
// ----------------------------------------------------
if ($metodo === "PATCH" && $id !== null) {
    if (!is_numeric($id)) {
        responder(400, [
            "error" => "El ID debe ser numerico"
        ]);
    }

    $data = leerJSONBody();

    if (empty($data)) {
        responder(400, [
            "error" => "Debes enviar al menos un campo para actualizar"
        ]);
    }

    foreach ($tareas as $indice => $tarea) {
        if ($tarea["id"] == $id) {
            if (isset($data["titulo"])) {
                $titulo = trim($data["titulo"]);

                if ($titulo === "") {
                    responder(400, [
                        "error" => "El titulo no puede estar vacio"
                    ]);
                }

                $tareas[$indice]["titulo"] = $titulo;
            }

            if (isset($data["descripcion"])) {
                $tareas[$indice]["descripcion"] = trim($data["descripcion"]);
            }

            if (isset($data["completada"])) {
                $tareas[$indice]["completada"] = (bool)$data["completada"];
            }

            if (isset($data["prioridad"])) {
                $prioridad = strtolower(trim($data["prioridad"]));

                if (!validarPrioridad($prioridad)) {
                    responder(400, [
                        "error" => "La prioridad debe ser baja, media o alta"
                    ]);
                }

                $tareas[$indice]["prioridad"] = $prioridad;
            }

            guardarTareas($archivoDatos, $tareas);
            responder(200, $tareas[$indice]);
        }
    }

    responder(404, [
        "error" => "Tarea no encontrada"
    ]);
}

// ----------------------------------------------------
// DELETE /tareas/{id}
// Elimina una tarea
// ----------------------------------------------------
if ($metodo === "DELETE" && $id !== null) {
    if (!is_numeric($id)) {
        responder(400, [
            "error" => "El ID debe ser numerico"
        ]);
    }

    foreach ($tareas as $indice => $tarea) {
        if ($tarea["id"] == $id) {
            array_splice($tareas, $indice, 1);
            guardarTareas($archivoDatos, $tareas);
            responder(204);
        }
    }

    responder(404, [
        "error" => "Tarea no encontrada"
    ]);
}

responder(405, [
    "error" => "Metodo no permitido para esta ruta"
]);
?>
