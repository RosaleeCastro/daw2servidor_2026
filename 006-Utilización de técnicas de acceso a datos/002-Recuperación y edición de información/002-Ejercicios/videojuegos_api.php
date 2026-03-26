<?php
// Indicamos al cliente que esta API siempre responderá en formato JSON
// y usando codificación UTF-8 para evitar problemas con acentos y caracteres especiales.
header("Content-Type: application/json; charset=utf-8");

// Datos de conexión a la base de datos.
// Si el servidor, puerto, nombre de la BD o credenciales cambian,
// este es el bloque que habría que modificar.
$host = "127.0.0.1";
$port = "3307";
$dbname = "videojuegos_asir";
$user = "root";
$pass = "";

// Leemos el contenido bruto de la petición HTTP.
// Esperamos que el cliente haya enviado un JSON en el body.
$raw = file_get_contents("php://input");

// Convertimos ese JSON a un array asociativo de PHP.
// El segundo parámetro en true hace que json_decode devuelva arrays y no objetos.
$data = json_decode($raw, true);

// Extraemos los filtros recibidos desde el JSON:
// - precio_max: precio máximo permitido
// - fecha_min: fecha mínima de lanzamiento
//
// precio_max se convierte a float para asegurar que se trate como número.
$precioMax = (float)$data["precio_max"] ?? null;
$fechaMin  = $data["fecha_min"] ?? null;

try {
  // Creamos la conexión con MySQL usando PDO.
  // PDO permite trabajar con la base de datos de forma más segura y flexible.
  $pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass
  );

  // Configuramos PDO para que lance excepciones si ocurre algún error.
  // Así podremos capturarlas en el bloque catch.
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Preparamos una consulta SQL segura con parámetros nombrados.
  // También excluimos los registros que tengan precio o fecha nulos,
  // ya que no servirían para aplicar correctamente los filtros.
  $sql = "
    SELECT id_videojuego, titulo, fecha_lanzamiento, precio_base
    FROM videojuego
    WHERE precio_base IS NOT NULL
      AND fecha_lanzamiento IS NOT NULL
      AND precio_base < :precio_max
      AND fecha_lanzamiento > :fecha_min
    ORDER BY fecha_lanzamiento ASC, precio_base ASC
  ";

  // Preparamos la consulta antes de ejecutarla.
  // Esto ayuda a evitar inyecciones SQL.
  $stmt = $pdo->prepare($sql);

  // Ejecutamos la consulta sustituyendo los parámetros por los valores recibidos.
  $stmt->execute([
    ':precio_max' => $precioMax,
    ':fecha_min'  => $fechaMin
  ]);

  // Recuperamos todos los resultados como arrays asociativos.
  $juegos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Devolvemos una respuesta JSON con:
  // - ok: indica que todo ha ido bien
  // - filtro: los parámetros usados en la búsqueda
  // - total: cantidad de juegos encontrados
  // - juegos: listado de resultados
  echo json_encode([
    "ok" => true,
    "filtro" => [
      "precio_max" => $precioMax,
      "fecha_min" => $fechaMin
    ],
    "total" => count($juegos),
    "juegos" => $juegos
  ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
  // Si ocurre un error de base de datos, devolvemos código HTTP 500
  // y un JSON con información del fallo.
  http_response_code(500);
  echo json_encode([
    "error" => "Error DB",
    "detalle" => $e->getMessage()
  ], JSON_PRETTY_PRINT);
}
