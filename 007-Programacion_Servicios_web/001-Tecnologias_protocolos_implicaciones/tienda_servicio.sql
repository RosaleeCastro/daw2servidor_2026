-- Script de creación de la base de datos de la tienda.
-- Ejecutar este archivo en phpMyAdmin o MySQL antes de probar la app.
-- OJO: DROP DATABASE borra la base anterior y sus datos.

DROP DATABASE IF EXISTS tienda_servicios;

-- Base de datos usada por conexion_mysql.php.
CREATE DATABASE IF NOT EXISTS tienda_servicios
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tienda_servicios;

-- Borramos primero las tablas dependientes para evitar problemas con claves foráneas.
DROP TABLE IF EXISTS pedido;
DROP TABLE IF EXISTS stock;
DROP TABLE IF EXISTS producto;

-- Catálogo de productos que se muestra en tienda.html.
CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
);

-- Stock disponible de cada producto.
-- id_producto es clave primaria y también clave foránea hacia producto.
CREATE TABLE stock (
    id_producto INT PRIMARY KEY,
    unidades INT NOT NULL,
    CONSTRAINT fk_stock_producto
        FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Pedidos realizados por clientes.
-- Guarda el precio_unitario y total en el momento de la compra.
CREATE TABLE pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    cliente VARCHAR(100) NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedido_producto
        FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- Datos iniciales para probar el catálogo.
INSERT INTO producto (nombre, precio) VALUES
('Teclado', 25.50),
('Ratón', 14.95),
('Monitor', 179.99),
('Auriculares', 39.90);

-- Stock inicial de cada producto, relacionado por id_producto.
INSERT INTO stock (id_producto, unidades) VALUES
(1, 10),
(2, 25),
(3, 5),
(4, 8);
