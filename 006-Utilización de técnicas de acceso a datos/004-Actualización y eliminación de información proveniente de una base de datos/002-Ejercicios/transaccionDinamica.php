<?php

// Datos de conexión con el servidor MySQL.
// Se separan en variables para que la cadena DSN quede más clara
// y para facilitar futuros cambios de configuración.
$host = "127.0.0.1";
$port = "3307";
$dbname = "videojuegos_asir";
$user = "root";
$pass = "";

// Variable donde guardaremos mensajes informativos o de error
// para mostrarlos después en la parte HTML.
$mensaje = "";

// Aquí almacenaremos el resultado del SELECT posterior al UPDATE.
// Empezamos con un array vacío para evitar errores si todavía no
// se ha enviado el formulario o si la consulta no llega a ejecutarse.
$desarrolladores = [];

try {
    // Creamos la conexión PDO indicando:
    // - host y puerto del servidor
    // - nombre de la base de datos
    // - codificación utf8mb4 para soportar correctamente acentos
    //   y otros caracteres especiales.
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    // Configuramos PDO para que lance excepciones cuando ocurra
    // un error SQL. Esto permite que el bloque catch gestione
    // los fallos de forma centralizada.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Solo procesamos la lógica de actualización cuando el formulario
    // ha sido enviado mediante POST. Si la página se abre por primera vez,
    // simplemente se mostrará el formulario vacío.
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        // Recuperamos los datos enviados desde el formulario.
        // El operador ?? evita avisos si alguna clave no existe
        // y asigna una cadena vacía como valor por defecto.
        $idDesarrollador = $_POST["id_desarrollador"] ?? "";
        $campo = $_POST["campo"] ?? "";
        $valor = $_POST["valor"] ?? "";

        // Lista blanca de columnas permitidas.
        // Esto es especialmente importante porque el nombre del campo
        // se inserta directamente en la sentencia SQL y no puede
        // parametrizarse como sí hacemos con los valores.
        // Gracias a esta validación evitamos que el usuario intente
        // actualizar columnas no previstas o manipular la consulta.
        $camposPermitidos = [
            "nombre",
            "apellido",
            "email",
            "ciudad",
            "pais",
            "activo"
        ];

        // Verificamos que los tres datos necesarios hayan llegado
        // con algún contenido.
        // Si falta cualquiera de ellos, no tiene sentido continuar
        // con la operación de base de datos.
        if ($idDesarrollador !== "" && $campo !== "" && $valor !== "") {

            // Confirmamos que el campo elegido por el usuario forma parte
            // de la lista blanca definida anteriormente.
            if (in_array($campo, $camposPermitidos)) {

                // Iniciamos la transacción.
                // A partir de aquí, las operaciones SQL quedarán agrupadas:
                // si todo va bien se confirmarán con commit(), y si algo falla
                // se desharán con rollBack().
                $pdo->beginTransaction();

                // Si el usuario quiere actualizar el campo "activo",
                // convertimos el valor recibido a entero para que encaje
                // mejor con el tipo habitual de este tipo de columnas
                // (por ejemplo 0 o 1).
                if ($campo === "activo") {
                    $valor = (int)$valor;
                }

                // Construimos un UPDATE dinámico que modifica una sola columna.
                // El nombre de la columna va directamente en la consulta
                // porque SQL no permite usar parámetros enlazados para
                // nombres de columnas o tablas.
                // En cambio, sí enlazamos:
                // - :valor -> nuevo contenido que recibirá la columna
                // - :id_desarrollador -> registro concreto a modificar
                $sqlUpdate = "
                    UPDATE desarrollador
                    SET $campo = :valor
                    WHERE id_desarrollador = :id_desarrollador
                ";

                // Preparamos la sentencia para que PDO gestione el envío
                // seguro de los valores parametrizados.
                $stmtUpdate = $pdo->prepare($sqlUpdate);

                // Ejecutamos el UPDATE enviando los valores reales.
                // Convertimos el id a entero para reforzar que debe ser
                // un identificador numérico.
                $stmtUpdate->execute([
                    ":valor" => $valor,
                    ":id_desarrollador" => (int)$idDesarrollador
                ]);

                // Realizamos un SELECT completo todavía dentro de la transacción.
                // De este modo leemos el estado de la tabla en el mismo contexto
                // transaccional en el que acabamos de hacer la modificación.
                $stmtSelect = $pdo->query("
                    SELECT id_desarrollador, nombre, apellido, email, ciudad, pais, activo
                    FROM desarrollador
                    ORDER BY id_desarrollador ASC
                ");

                // Convertimos todas las filas obtenidas en un array asociativo,
                // donde cada columna se podrá acceder por su nombre.
                $desarrolladores = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

                // Si hemos llegado hasta aquí sin errores, confirmamos
                // definitivamente los cambios en la base de datos.
                $pdo->commit();

                // Mensaje de éxito que se mostrará al usuario.
                $mensaje = "Transacción realizada correctamente. Se ha actualizado el campo '$campo'.";
            } else {
                // Mensaje de seguridad/validación en caso de que el campo
                // recibido no esté entre los autorizados.
                $mensaje = "El campo seleccionado no está permitido.";
            }

        } else {
            // Mensaje de validación si el formulario no llega completo.
            $mensaje = "Debes rellenar todos los campos del formulario.";
        }
    }

} catch (PDOException $e) {

    // Si se produjo un error mientras la transacción estaba activa,
    // deshacemos los cambios para dejar la base de datos en un estado consistente.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Guardamos el mensaje técnico del error para depuración.
    $mensaje = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Transacción con UPDATE dinámico</title>
</head>
<body>

<!-- Título principal de la página. Resume la finalidad del ejercicio. -->
<h2>Actualizar un único campo con transacción</h2>

<?php if ($mensaje): ?>
    <!-- Si existe algún mensaje, lo mostramos escapado con htmlspecialchars
         para evitar problemas si contiene caracteres especiales. -->
    <p><strong><?php echo htmlspecialchars($mensaje); ?></strong></p>
<?php endif; ?>

<!-- Formulario que envía los datos a esta misma página mediante POST. -->
<form method="POST">

    <!-- Identificador del desarrollador que se desea modificar. -->
    <label>ID del desarrollador:</label><br>
    <input type="number" name="id_desarrollador" required><br><br>

    <!-- Selector del campo concreto que se va a actualizar.
         Solo ofrece las columnas permitidas por la lógica PHP. -->
    <label>Campo a actualizar:</label><br>
    <select name="campo" required>
        <option value="">-- Selecciona un campo --</option>
        <option value="nombre">nombre</option>
        <option value="apellido">apellido</option>
        <option value="email">email</option>
        <option value="ciudad">ciudad</option>
        <option value="pais">pais</option>
        <option value="activo">activo</option>
    </select><br><br>

    <!-- Nuevo valor que se guardará en el campo seleccionado. -->
    <label>Nuevo valor:</label><br>
    <input type="text" name="valor" required><br><br>

    <!-- Botón que lanza el envío del formulario. -->
    <button type="submit">Actualizar con transacción</button>
</form>

<?php if (!empty($desarrolladores)): ?>

    <!-- Esta tabla solo se muestra si, después de la operación,
         se han cargado registros en el array $desarrolladores. -->
    <h3>Tabla completa tras la transacción:</h3>

    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Ciudad</th>
            <th>País</th>
            <th>Activo</th>
        </tr>

        <?php foreach ($desarrolladores as $dev): ?>
            <!-- Recorremos cada fila devuelta por el SELECT y pintamos
                 sus columnas en una nueva fila de la tabla HTML. -->
            <tr>
                <!-- htmlspecialchars protege la salida HTML para mostrar
                     el contenido de forma segura en la página. -->
                <td><?php echo htmlspecialchars($dev["id_desarrollador"]); ?></td>
                <td><?php echo htmlspecialchars($dev["nombre"]); ?></td>
                <td><?php echo htmlspecialchars($dev["apellido"]); ?></td>
                <!-- El operador ?? "" evita errores si algún campo viniera nulo
                     o no estuviera definido en el array. -->
                <td><?php echo htmlspecialchars($dev["email"] ?? ""); ?></td>
                <td><?php echo htmlspecialchars($dev["ciudad"] ?? ""); ?></td>
                <td><?php echo htmlspecialchars($dev["pais"] ?? ""); ?></td>
                <td><?php echo htmlspecialchars($dev["activo"]); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

<?php endif; ?>

</body>
</html>
