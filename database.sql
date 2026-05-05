-- =============================================
--  UNISPORT BOOKING - database.sql
--  Ejecutar en MySQL / MariaDB
-- =============================================

CREATE DATABASE IF NOT EXISTS unisport_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unisport_booking;

-- USUARIOS
CREATE TABLE usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100)        NOT NULL,
    email       VARCHAR(150)        NOT NULL UNIQUE,
    password    VARCHAR(255)        NOT NULL,  -- bcrypt hash
    saldo       DECIMAL(8,2)        NOT NULL DEFAULT 0.00,
    rol         ENUM('alumno','admin') NOT NULL DEFAULT 'alumno',
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
);

-- PISTAS
CREATE TABLE pistas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100)        NOT NULL,
    deporte     VARCHAR(50)         NOT NULL,
    descripcion VARCHAR(255),
    precio      DECIMAL(6,2)        NOT NULL,
    activa      TINYINT(1)          NOT NULL DEFAULT 1
);

-- RESERVAS
CREATE TABLE reservas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT                 NOT NULL,
    pista_id    INT                 NOT NULL,
    fecha       DATE                NOT NULL,
    hora_inicio TIME                NOT NULL,
    hora_fin    TIME                NOT NULL,
    estado      ENUM('activa','cancelada') NOT NULL DEFAULT 'activa',
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (pista_id)   REFERENCES pistas(id)
);

-- DATOS DE PRUEBA
INSERT INTO usuarios (nombre, email, password, saldo) VALUES
('Pepe Alumno', 'pepe.alumno@unisport.es', '$2y$10$e0NRbKBFZkmQ1pCzF9gC7uQkL2lL8dLgBkXrT5oZgFqHpDmN3YGPG', 50.00);
-- contraseña en texto plano: password123

INSERT INTO pistas (nombre, deporte, descripcion, precio) VALUES
('Pista de Tenis 1',   'Tenis',       'Pista dura exterior',         5.00),
('Campo Fútbol A',     'Fútbol',      'Campo 7 césped artificial',   10.00),
('Pista de Pádel 2',   'Pádel',       'Pista cubierta climatizada',   8.00),
('Pista Baloncesto 1', 'Baloncesto',  'Pista polideportiva interior',  6.00),
('Pista Voleibol',     'Voleibol',    'Pista exterior de arena',       4.00),
('Pista de Tenis 2',   'Tenis',       'Pista de tierra batida',        5.00);
