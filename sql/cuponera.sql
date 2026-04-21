-- =============================================================
--  La Cuponera SV  -  Base de datos
-- =============================================================

CREATE DATABASE IF NOT EXISTS cuponera_sv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cuponera_sv;

-- ---------------------------------------------------------------
-- Administradores
-- ---------------------------------------------------------------
CREATE TABLE administradores (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(150) NOT NULL,
    usuario       VARCHAR(80)  NOT NULL UNIQUE,
    correo        VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Administrador por defecto  (password: Admin123!)
INSERT INTO administradores (nombre, usuario, correo, password)
VALUES ('Administrador Principal', 'admin', 'admin@cuponera.sv',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ---------------------------------------------------------------
-- Empresas
-- ---------------------------------------------------------------
CREATE TABLE empresas (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nombre               VARCHAR(150) NOT NULL,
    nit                  VARCHAR(20)  NOT NULL UNIQUE,
    direccion            TEXT         NOT NULL,
    telefono             VARCHAR(20)  NOT NULL,
    correo               VARCHAR(150) NOT NULL UNIQUE,
    usuario              VARCHAR(80)  NOT NULL UNIQUE,
    password             VARCHAR(255) NOT NULL,
    estado               ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
    porcentaje_comision  DECIMAL(5,2) DEFAULT NULL,
    aprobado_por         INT          DEFAULT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aprobado_por) REFERENCES administradores(id)
);

-- ---------------------------------------------------------------
-- Clientes
-- ---------------------------------------------------------------
CREATE TABLE clientes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    apellidos       VARCHAR(150) NOT NULL,
    dui             VARCHAR(20)  NOT NULL UNIQUE,
    fecha_nacimiento DATE         NOT NULL,
    usuario         VARCHAR(80)  NOT NULL UNIQUE,
    correo          VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------
-- Ofertas
-- ---------------------------------------------------------------
CREATE TABLE ofertas (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id           INT          NOT NULL,
    titulo               VARCHAR(200) NOT NULL,
    precio_regular       DECIMAL(10,2) NOT NULL,
    precio_oferta        DECIMAL(10,2) NOT NULL,
    fecha_inicio         DATE         NOT NULL,
    fecha_fin            DATE         NOT NULL,
    fecha_limite_canje   DATE         NOT NULL,
    cantidad_cupones     INT          DEFAULT NULL,
    descripcion          TEXT         NOT NULL,
    estado               ENUM('disponible','no_disponible') DEFAULT 'disponible',
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

-- ---------------------------------------------------------------
-- Compras
-- ---------------------------------------------------------------
CREATE TABLE compras (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id       INT           NOT NULL,
    oferta_id        INT           NOT NULL,
    numero_tarjeta   VARCHAR(20)   NOT NULL,
    fecha_venc_tarjeta VARCHAR(7)  NOT NULL,
    cvv              VARCHAR(4)    NOT NULL,
    monto_pagado     DECIMAL(10,2) NOT NULL,
    fecha_compra     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (oferta_id)  REFERENCES ofertas(id)
);

-- ---------------------------------------------------------------
-- Cupones
-- ---------------------------------------------------------------
CREATE TABLE cupones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    compra_id   INT         NOT NULL,
    codigo_unico VARCHAR(64) NOT NULL UNIQUE,
    estado      ENUM('activo','canjeado') DEFAULT 'activo',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compra_id) REFERENCES compras(id)
);

-- ---------------------------------------------------------------
-- Recuperación de contraseña
-- ---------------------------------------------------------------
CREATE TABLE password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    correo     VARCHAR(150) NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    tipo_usuario ENUM('admin','empresa','cliente') NOT NULL,
    expires_at TIMESTAMP    NOT NULL,
    used       TINYINT(1)   DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
