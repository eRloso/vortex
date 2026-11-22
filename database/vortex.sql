-- =====================================================================
--  VORTEX · Sistema de ventas
--  Script de base de datos para MariaDB / MySQL
--  Importar en phpMyAdmin o: mysql -u root -p < database/vortex.sql
-- =====================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS vortex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vortex;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS detalle_ventas, ventas, productos, categorias, clientes, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Tablas
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    email       VARCHAR(120) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    rol         ENUM('admin', 'vendedor') NOT NULL DEFAULT 'vendedor',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE categorias (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80) NOT NULL UNIQUE,
    color       CHAR(7) NOT NULL DEFAULT '#7C5CFF',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE productos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo        VARCHAR(30) NOT NULL UNIQUE,
    nombre        VARCHAR(120) NOT NULL,
    descripcion   TEXT NULL,
    categoria_id  INT UNSIGNED NULL,
    precio        DECIMAL(12, 2) NOT NULL DEFAULT 0,
    stock         INT NOT NULL DEFAULT 0,
    stock_minimo  INT NOT NULL DEFAULT 5,
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_productos_nombre (nombre),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE SET NULL,
    CONSTRAINT chk_productos_stock CHECK (stock >= 0)
) ENGINE = InnoDB;

CREATE TABLE clientes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    documento   VARCHAR(20) NOT NULL UNIQUE,
    nombre      VARCHAR(120) NOT NULL,
    email       VARCHAR(120) NULL,
    telefono    VARCHAR(20) NULL,
    direccion   VARCHAR(180) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE ventas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(20) NULL UNIQUE,
    cliente_id   INT UNSIGNED NULL,
    usuario_id   INT UNSIGNED NOT NULL,
    subtotal     DECIMAL(12, 2) NOT NULL DEFAULT 0,
    impuesto     DECIMAL(12, 2) NOT NULL DEFAULT 0,
    total        DECIMAL(12, 2) NOT NULL DEFAULT 0,
    metodo_pago  VARCHAR(20) NOT NULL DEFAULT 'efectivo',
    tasa_usd     DECIMAL(14, 8) NULL COMMENT 'Tasa COP->USD obtenida de la API al vender',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ventas_fecha (created_at),
    CONSTRAINT fk_ventas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL,
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE = InnoDB;

CREATE TABLE detalle_ventas (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venta_id         INT UNSIGNED NOT NULL,
    producto_id      INT UNSIGNED NOT NULL,
    cantidad         INT NOT NULL,
    precio_unitario  DECIMAL(12, 2) NOT NULL,
    importe          DECIMAL(12, 2) NOT NULL,
    CONSTRAINT fk_detalle_venta FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos (id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- Datos de ejemplo
-- ---------------------------------------------------------------------

-- Usuario: admin@vortex.com / admin123
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador Vortex', 'admin@vortex.com', '$2y$10$vh0FwgcmeuqI5.KtmqqFau/svhQS4gG9qIFZNxO.RTHR1IbWNoedS', 'admin');

INSERT INTO categorias (id, nombre, color) VALUES
(1, 'Computadores',   '#7C5CFF'),
(2, 'Periféricos',    '#22D3EE'),
(3, 'Audio',          '#EC4899'),
(4, 'Accesorios',     '#F59E0B'),
(5, 'Almacenamiento', '#22C55E');

INSERT INTO productos (id, codigo, nombre, descripcion, categoria_id, precio, stock, stock_minimo) VALUES
(1,  'VTX-LAP-001', 'Portátil Lenovo IdeaPad 3',        'Ryzen 5, 8 GB RAM, 512 GB SSD, 15.6"', 1, 2499000, 8,  3),
(2,  'VTX-LAP-002', 'Portátil ASUS Vivobook 15',        'Core i5, 16 GB RAM, 512 GB SSD',        1, 2890000, 5,  3),
(3,  'VTX-PER-001', 'Mouse inalámbrico Logitech M185',  NULL,                                    2,   69900, 40, 10),
(4,  'VTX-PER-002', 'Teclado mecánico Redragon Kumara', 'Switches rojos, retroiluminado',        2,  189000, 15, 5),
(5,  'VTX-PER-003', 'Monitor Samsung 24" FHD',          'Panel IPS 75 Hz',                       2,  649000, 4,  5),
(6,  'VTX-AUD-001', 'Audífonos JBL Tune 520BT',         'Bluetooth 5.3, 57 h de batería',        3,  229000, 12, 4),
(7,  'VTX-AUD-002', 'Parlante JBL Go 3',                'Resistente al agua IP67',               3,  179000, 3,  4),
(8,  'VTX-ACC-001', 'Base refrigerante para portátil',  NULL,                                    4,   89000, 20, 5),
(9,  'VTX-ACC-002', 'Cable HDMI 2.0 de 2 m',            NULL,                                    4,   25000, 60, 15),
(10, 'VTX-ALM-001', 'SSD Kingston A400 480 GB',         'SATA III 2.5"',                         5,  159000, 25, 6),
(11, 'VTX-ALM-002', 'Memoria USB SanDisk 64 GB',        'USB 3.0',                               5,   39900, 2,  10),
(12, 'VTX-ACC-003', 'Hub USB-C 6 en 1',                 'HDMI 4K, USB 3.0, lector SD',           4,  129000, 10, 4);

INSERT INTO clientes (id, documento, nombre, email, telefono, direccion) VALUES
(1, '222222222222', 'Consumidor final', NULL, NULL, NULL),
(2, '1110456789',   'Laura Gómez',      'laura.gomez@correo.com',   '3104567890', 'Cra 5 # 12-30, Honda'),
(3, '1110987654',   'Andrés Restrepo',  'andres.restrepo@correo.com', '3209876543', 'Calle 10 # 3-45, Mariquita'),
(4, '1105123456',   'Camila Torres',    'camila.torres@correo.com', '3001234567', NULL);

-- Ventas de los últimos días para que el dashboard tenga datos
INSERT INTO ventas (id, codigo, cliente_id, usuario_id, metodo_pago, tasa_usd, created_at) VALUES
(1, 'VTX-000001', 2, 1, 'tarjeta',       0.00025, NOW() - INTERVAL 6 DAY),
(2, 'VTX-000002', 1, 1, 'efectivo',      0.00025, NOW() - INTERVAL 5 DAY),
(3, 'VTX-000003', 3, 1, 'nequi',         0.00025, NOW() - INTERVAL 4 DAY),
(4, 'VTX-000004', 1, 1, 'efectivo',      0.00025, NOW() - INTERVAL 3 DAY),
(5, 'VTX-000005', 4, 1, 'transferencia', 0.00025, NOW() - INTERVAL 2 DAY),
(6, 'VTX-000006', 2, 1, 'efectivo',      0.00025, NOW() - INTERVAL 1 DAY),
(7, 'VTX-000007', 1, 1, 'tarjeta',       0.00025, NOW() - INTERVAL 1 DAY),
(8, 'VTX-000008', 3, 1, 'nequi',         0.00025, NOW() - INTERVAL 2 HOUR);

INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, importe)
SELECT v.venta_id, p.id, v.cantidad, p.precio, p.precio * v.cantidad
FROM (
    SELECT 1 AS venta_id, 1 AS producto_id, 1 AS cantidad UNION ALL
    SELECT 1, 3, 1  UNION ALL
    SELECT 2, 9, 3  UNION ALL
    SELECT 2, 11, 2 UNION ALL
    SELECT 3, 6, 1  UNION ALL
    SELECT 3, 4, 1  UNION ALL
    SELECT 4, 3, 2  UNION ALL
    SELECT 5, 2, 1  UNION ALL
    SELECT 5, 12, 1 UNION ALL
    SELECT 6, 10, 2 UNION ALL
    SELECT 6, 9, 1  UNION ALL
    SELECT 7, 5, 1  UNION ALL
    SELECT 8, 7, 1  UNION ALL
    SELECT 8, 3, 1
) AS v
JOIN productos p ON p.id = v.producto_id;

UPDATE ventas v
JOIN (SELECT venta_id, SUM(importe) AS subtotal FROM detalle_ventas GROUP BY venta_id) d ON d.venta_id = v.id
SET v.subtotal = d.subtotal,
    v.impuesto = ROUND(d.subtotal * 0.19, 2),
    v.total    = d.subtotal + ROUND(d.subtotal * 0.19, 2);
