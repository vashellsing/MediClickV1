-- Crear base de datos
CREATE DATABASE IF NOT EXISTS mediclick CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE mediclick;

-- =========================
-- TABLA: roles
-- =========================
CREATE TABLE roles (
  id_rol INT AUTO_INCREMENT PRIMARY KEY,
  rol VARCHAR(50) NOT NULL
);

-- Insertar roles iniciales
INSERT INTO roles (rol) VALUES
('PACIENTE'),
('MEDICO'),
('ADMIN');

-- =========================
-- TABLA: especialidad
-- =========================
CREATE TABLE especialidad (
  id_especialidad INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL
);

-- Ejemplos
INSERT INTO especialidad (nombre) VALUES
('Medicina General'),
('Pediatría'),
('Dermatología'),
('Odontología'),
('Cardiología');

-- =========================
-- TABLA: pacientes
-- =========================
CREATE TABLE pacientes (
  id_paciente INT AUTO_INCREMENT PRIMARY KEY,
  cedula VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  correo VARCHAR(100) NOT NULL UNIQUE,
  id_rol INT NOT NULL DEFAULT 1, -- Rol PACIENTE
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

-- =========================
-- TABLA: medicos
-- =========================
CREATE TABLE medicos (
  id_medico INT AUTO_INCREMENT PRIMARY KEY,
  cedula VARCHAR(20) NOT NULL UNIQUE,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  correo VARCHAR(100) NOT NULL UNIQUE,
  id_especialidad INT NOT NULL,
  id_rol INT NOT NULL DEFAULT 2, -- Rol MEDICO
  FOREIGN KEY (id_especialidad) REFERENCES especialidad(id_especialidad)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

-- =========================
-- TABLA: citas
-- =========================
CREATE TABLE citas (
  id_cita INT AUTO_INCREMENT PRIMARY KEY,
  id_paciente INT NOT NULL,
  id_medico INT NOT NULL,
  fecha_hora_cita DATETIME NOT NULL,
  estado ENUM('PENDIENTE','CONFIRMADA','CANCELADA','REALIZADA') DEFAULT 'PENDIENTE',
  FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente)
    ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (id_medico) REFERENCES medicos(id_medico)
    ON UPDATE CASCADE ON DELETE CASCADE
);

-- =========================
-- TABLA: horario
-- =========================
CREATE TABLE horarios (
  id_horario INT AUTO_INCREMENT PRIMARY KEY,
  id_medico INT NOT NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  estado ENUM('libre','ocupado') DEFAULT 'libre',
  FOREIGN KEY (id_doctor) REFERENCES doctores(id_doctor)
);



-- =========================
-- TABLA: notificacion
-- =========================
CREATE TABLE notificacion (
  id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
  id_paciente INT NOT NULL,
  mensaje TEXT NOT NULL,
  FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente)
    ON UPDATE CASCADE ON DELETE CASCADE
);
