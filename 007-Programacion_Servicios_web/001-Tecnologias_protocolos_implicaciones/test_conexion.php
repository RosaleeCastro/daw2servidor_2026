<?php
require_once "conexion_mysql.php";

try {
    $pdo = obtenerPDO();
    echo "Conexión correcta";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
