-- CREAR DATABASE --
CREATE DATABASE IF NOT EXISTS unisport_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unisport_db;

-- USUARIOS --
CREATE TABLE usuarios (
    id_user          INT(11)          AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100)     NOT NULL,
    email            VARCHAR(255)     NOT NULL UNIQUE,
    password         VARCHAR(255)     NOT NULL,
    rol              ENUM('alumno','externo','entrenador','admin') NOT NULL DEFAULT 'alumno',
    saldo            DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    fecha_registro   TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
);

-- PISTAS --
CREATE TABLE pistas (
    id_pista         INT(11)          AUTO_INCREMENT PRIMARY KEY,
    nombre_pista     VARCHAR(50)      NOT NULL,
    tipo_deporte     VARCHAR(50)      NOT NULL,
    precio_hora      DECIMAL(10,2)    NOT NULL,
    estado           ENUM('disponible','mantenimiento') NOT NULL DEFAULT 'disponible'
);

-- MONITORES --
-- MONITOR PUEDE TENER USER ASOCIADO --
CREATE TABLE monitores (
    id_monitor       INT(11)          AUTO_INCREMENT PRIMARY KEY,
    id_user          INT(11)          DEFAULT NULL,
    nombre           VARCHAR(100)     NOT NULL,
    especialidad     VARCHAR(50)      NOT NULL,
    precio_sesion    DECIMAL(10,2)    NOT NULL,
    disponibilidad   TINYINT(1)       NOT NULL DEFAULT 1,
    FOREIGN KEY (id_user) REFERENCES usuarios(id_user) ON DELETE SET NULL
);

-- MATERIAL --
CREATE TABLE material (
    id_material      INT(11)          AUTO_INCREMENT PRIMARY KEY,
    nombre_material  VARCHAR(50)      NOT NULL,
    stock_total      INT(11)          NOT NULL DEFAULT 0,
    precio_alquiler  DECIMAL(10,2)    NOT NULL
);

-- RESERVAS --
CREATE TABLE reservas (
    id_reserva       INT(11)          AUTO_INCREMENT PRIMARY KEY,
    id_user          INT(11)          NOT NULL,
    id_pista         INT(11)          NOT NULL,
    id_monitor       INT(11)          DEFAULT NULL,
    fecha            DATE             NOT NULL,
    hora_inicio      TIME             NOT NULL,
    hora_fin         TIME             NOT NULL,
    precio_final     DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    estado_pago      ENUM('pendiente','pagado','cancelada') NOT NULL DEFAULT 'pendiente',
    cancelada        cancelada TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_user)    REFERENCES usuarios(id_user),
    FOREIGN KEY (id_pista)   REFERENCES pistas(id_pista),
    FOREIGN KEY (id_monitor) REFERENCES monitores(id_monitor) ON DELETE SET NULL
);

-- RESERVA_MATERIAL --
CREATE TABLE reserva_material (
    id_reserva       INT(11)          NOT NULL,
    id_material      INT(11)          NOT NULL,
    cantidad         INT(11)          NOT NULL DEFAULT 1,
    PRIMARY KEY (id_reserva, id_material),
    FOREIGN KEY (id_reserva)  REFERENCES reservas(id_reserva) ON DELETE CASCADE,
    FOREIGN KEY (id_material) REFERENCES material(id_material)
);


-- DATOS PRUEBA --
INSERT INTO usuarios (nombre, email, password, rol, saldo) VALUES
('Pepe Alumno',      'pepe.alumno@unisport.es',      'password', 'alumno',     50.00),
('Ana Externa',      'ana.externa@gmail.com',         'password', 'externo',    20.00),
('Carlos Gómez',     'carlos.entrenador@unisport.es', 'password', 'entrenador', 0.00),
('Laura Pérez',      'laura.entrenador@unisport.es',  'password', 'entrenador', 0.00),
('Admin UniSport',   'admin@unisport.es',             'password', 'admin',      0.00);

-- PISTAS
INSERT INTO pistas (nombre_pista, tipo_deporte, precio_hora, estado) VALUES
('Pista Tenis 1',    'Tenis',       5.00,  'disponible'),
('Campo Fútbol A',   'Fútbol',      10.00, 'disponible'),
('Pista Pádel 2',    'Pádel',       8.00,  'disponible'),
('Pista Baloncesto', 'Baloncesto',  6.00,  'disponible'),
('Pista Voleibol',   'Voleibol',    4.00,  'disponible'),
('Pista Tenis 2',    'Tenis',       5.00,  'mantenimiento');

-- MoONITORES
INSERT INTO monitores (id_user, nombre, especialidad, precio_sesion, disponibilidad) VALUES
(3, 'Carlos Gómez', 'Tenis',  20.00, 1),
(4, 'Laura Pérez',  'Fútbol', 15.00, 1),
(NULL, 'Mario Ruiz', 'Pádel', 18.00, 1);

-- MATERIAL
INSERT INTO material (nombre_material, stock_total, precio_alquiler) VALUES
('Raqueta Tenis',  10, 3.00),
('Pelota Fútbol',  20, 1.00),
('Raqueta Pádel',  8,  3.50),
('Red Voleibol',   4,  5.00);
