CREATE DATABASE IF NOT EXISTS susuerte;
USE susuerte;

CREATE TABLE usuarios(
id_usuario INT AUTO_INCREMENT NOT NULL,
nombre VARCHAR(50) NOT NULL,
saldo DECIMAL(10,2),
creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY(id_usuario)
);

CREATE TABLE tiquetes(
id_tiquete INT AUTO_INCREMENT,
usuario_id INT NOT NULL,
monto DECIMAL(10,2),
estado ENUM('ganador','perdedor','pendiente') NOT NULL DEFAULT 'pendiente',
creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY(id_tiquete),
FOREIGN KEY (usuario_id) References usuarios(id_usuario)
);

-- Usuarios
INSERT INTO usuarios (nombre, saldo) VALUES
('Juan Pérez', 1500.50),
('María Gómez', 2300.00),
('Carlos Rodríguez', 750.25),
('Ana López', 3200.75),
('Pedro Martínez', 500.00),
('Valentina Marin', 0.00);

-- Tiquetes
INSERT INTO tiquetes (usuario_id, monto, estado) VALUES
(1, 100.00, 'ganador'),
(1, 50.00, 'perdedor'),
(2, 200.00, 'pendiente'),
(2, 150.00, 'ganador'),
(3, 80.00, 'perdedor'),
(3, 120.00, 'pendiente'),
(4, 500.00, 'ganador'),
(4, 300.00, 'ganador'),
(5, 60.00, 'perdedor'),
(5, 90.00, 'pendiente');

/* Consultas requeridas */

/* 2.2 Top 3 usuarios con mayor monto total apostado en tiquetes GANADORES */

SELECT u.nombre, SUM(t.monto) AS total
FROM usuarios u
INNER JOIN tiquetes t ON t.usuario_id = u.id_usuario
WHERE t.estado = 'ganador'
GROUP BY u.id_usuario, u.nombre
ORDER BY total DESC
LIMIT 3;

/* 2.3 Lista de usuarios sin ningún tiquete registrado  */

SELECT DISTINCT u.id_usuario, u.nombre 
FROM usuarios u
LEFT JOIN tiquetes t ON t.usuario_id = u.id_usuario
WHERE t.usuario_id IS NULL;