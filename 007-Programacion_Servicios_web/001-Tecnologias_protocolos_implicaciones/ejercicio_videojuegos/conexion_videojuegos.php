<?php
/*
 * Archivo reutilizable de conexión a MySQL.
 *
 * Lo incluyen los servicios PHP que necesitan consultar o modificar la base
 * de datos. Centralizar la conexión evita repetir host, puerto, usuario,
 * contraseña y nombre de base de datos en cada servicio.
 */

/**
 * Crea y devuelve una conexión PDO preparada para trabajar con MySQL.
 *
 * Se conecta con:
 * - Servidor: 127.0.0.1
 * - Puerto: 3306
 *  * - Base de datos: tienda_servicios
 *
 * @return PDO conexión activa a la base de datos.
 * @throws PDOException si MySQL no está activo, el puerto no coincide,
 *                      la base de datos no existe o las credenciales fallan.
 */
function obtenerPDO(){
  $host   = "127.0.0.1";
  $port   = "3306";
  $dbname = "videojuegos_asir";
  $user   = "root";
  $pass   = "";

  $pdo = new PDO(
    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass
  );

  // Hace que PDO lance excepciones cuando haya errores de conexión o SQL.
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  return $pdo;
}

?>
