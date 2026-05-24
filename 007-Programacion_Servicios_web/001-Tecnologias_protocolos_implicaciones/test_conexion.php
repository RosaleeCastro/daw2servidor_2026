<?php
/*
 * Archivo de prueba rápida.
 *
 * Sirve para comprobar desde el navegador que conexion_mysql.php puede abrir
 * una conexión real con la base de datos tienda_servicios.
 *
 * URL típica en XAMPP:
 * http://localhost/daw2servidor_RCT/daw2servidor_2026/007-Programacion_Servicios_web/001-Tecnologias_protocolos_implicaciones/test_conexion.php
 */

require_once "conexion_mysql.php";

try {
    $pdo = obtenerPDO();
    echo "Conexión correcta";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
