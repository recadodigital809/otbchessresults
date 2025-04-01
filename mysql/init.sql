CREATE DATABASE IF NOT EXISTS Ajedrez;
USE Ajedrez;

CREATE TABLE db_Jugadores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    elo INT DEFAULT 0
);

CREATE TABLE db_Torneos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    fecha_inicio DATE NOT NULL,
    tipo ENUM('online', 'presencial') NOT NULL,
    estado ENUM('creado', 'en curso', 'finalizado') NOT NULL DEFAULT 'creado'
);

CREATE TABLE db_Partidas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    torneo_id INT NOT NULL,
    jugador_blancas INT NOT NULL,
    jugador_negras INT NOT NULL,
    resultado ENUM('1-0', '0-1', '½-½') DEFAULT NULL,
    ronda INT NOT NULL,
    FOREIGN KEY (torneo_id) REFERENCES db_Torneos(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_blancas) REFERENCES db_Jugadores(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_negras) REFERENCES db_Jugadores(id) ON DELETE CASCADE
);

CREATE VIEW vw_Partidas AS (
SELECT 
    p.id AS partida_id,
    t.nombre AS torneo,
    t.fecha_inicio,
    j1.nombre AS jugador_blancas,
    j2.nombre AS jugador_negras,
    p.resultado,
    p.ronda
FROM db_Partidas p
JOIN db_Torneos t ON p.torneo_id = t.id
JOIN db_Jugadores j1 ON p.jugador_blancas = j1.id
JOIN db_Jugadores j2 ON p.jugador_negras = j2.id
);

CREATE VIEW vw_PuntosTorneos AS (
SELECT 
    j.id AS jugador_id,
    j.nombre AS jugador,
    t.id AS torneo_id,
    t.nombre AS torneo,
    t.fecha_inicio,
    
    -- Conteo de victorias
    COUNT(CASE WHEN p.resultado = '1-0' AND p.jugador_blancas = j.id THEN 1 END) +
    COUNT(CASE WHEN p.resultado = '0-1' AND p.jugador_negras = j.id THEN 1 END) AS victorias,
    
    -- Conteo de empates
    COUNT(CASE WHEN p.resultado = '½-½' AND (p.jugador_blancas = j.id OR p.jugador_negras = j.id) THEN 1 END) AS empates,
    
    -- Conteo de derrotas
    COUNT(CASE WHEN p.resultado = '0-1' AND p.jugador_blancas = j.id THEN 1 END) +
    COUNT(CASE WHEN p.resultado = '1-0' AND p.jugador_negras = j.id THEN 1 END) AS derrotas,
    
    -- Cálculo de puntos (1 punto por victoria, 0.5 por empate)
    (COUNT(CASE WHEN p.resultado = '1-0' AND p.jugador_blancas = j.id THEN 1 END) +
     COUNT(CASE WHEN p.resultado = '0-1' AND p.jugador_negras = j.id THEN 1 END)) * 1 +
    COUNT(CASE WHEN p.resultado = '½-½' AND (p.jugador_blancas = j.id OR p.jugador_negras = j.id) THEN 1 END) * 0.5 AS puntos
    
FROM db_Jugadores j
JOIN db_Partidas p ON j.id = p.jugador_blancas OR j.id = p.jugador_negras
JOIN db_Torneos t ON p.torneo_id = t.id
GROUP BY j.id, j.nombre, t.id, t.nombre, t.fecha_inicio
);

