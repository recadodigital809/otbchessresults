-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: mysqldb
-- Generation Time: Apr 01, 2025 at 02:11 AM
-- Server version: 8.0.33
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `Ajedrez`
--

DELIMITER $$
--
-- Functions
--
CREATE DEFINER=`root`@`%` FUNCTION `numero_ordinal` (`n` INT) RETURNS VARCHAR(10) CHARSET utf8mb4 DETERMINISTIC BEGIN
    DECLARE sufijo VARCHAR(5);
    
    -- Manejo de sufijos ordinales en español
    IF n = 1 THEN
        SET sufijo = 'ero';
    ELSEIF n = 2 THEN
        SET sufijo = 'do';
    ELSEIF n = 3 THEN
        SET sufijo = 'ero';
    ELSEIF n BETWEEN 4 AND 9 THEN
        SET sufijo = 'to';
    ELSE
        SET sufijo = 'avo';
    END IF;

    -- Retornar el número con el sufijo correspondiente
    RETURN CONCAT(n, sufijo);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `db_elo_history`
--

CREATE TABLE `db_elo_history` (
  `id` int NOT NULL,
  `jugador_id` int NOT NULL,
  `elo_anterior` int NOT NULL,
  `elo_nuevo` int NOT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `db_Jugadores`
--

CREATE TABLE `db_Jugadores` (
  `id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `elo` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `db_Jugadores`
--

INSERT INTO `db_Jugadores` (`id`, `nombre`, `elo`) VALUES
(9, 'Gabriel Urraca', 1800),
(10, 'Bernardo Rijo', 1800),
(11, 'Micael Lopez', 1800),
(12, 'Carlos del Rosario', 1800),
(13, 'Rafael Reyes', 1800),
(14, 'Fidel Valdes', 1800),
(15, 'Franklyn Acosta', 1800),
(16, 'Mario Barinas', 1800),
(17, 'Pablo Gonzalez', 1800),
(18, 'Niman Victoriano', 1800),
(19, 'Starling Rodriguez', 1800),
(20, 'Hirundy Ramirez', 1800),
(21, 'José Amado', 1800),
(22, 'Antonio Caraballo', 1800),
(23, 'Kelvis Ceballos', 1800);

-- --------------------------------------------------------

--
-- Table structure for table `db_Liga`
--

CREATE TABLE `db_Liga` (
  `id` int NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `imagen` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `db_Liga`
--

INSERT INTO `db_Liga` (`id`, `nombre`, `texto`, `imagen`) VALUES
(1, 'Los Muchachones', 'Liga de amantes del ajedrez', NULL),
(3, 'Los viejitos del Quisquella', 'Los maestros', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `db_Liga_Jugadores`
--

CREATE TABLE `db_Liga_Jugadores` (
  `id` int NOT NULL,
  `jugador_id` int NOT NULL,
  `liga_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `db_Liga_Jugadores`
--

INSERT INTO `db_Liga_Jugadores` (`id`, `jugador_id`, `liga_id`) VALUES
(3, 9, 1),
(4, 10, 1),
(5, 11, 1),
(6, 12, 1),
(7, 13, 1),
(8, 14, 1),
(9, 15, 1),
(10, 16, 1),
(11, 17, 1),
(12, 18, 1),
(13, 19, 1),
(14, 20, 1),
(15, 21, 1),
(16, 22, 1),
(17, 23, 1),
(34, 9, 3),
(35, 10, 3),
(36, 11, 3),
(37, 12, 3),
(38, 13, 3),
(39, 14, 3),
(40, 15, 3),
(41, 16, 3),
(42, 17, 3),
(43, 18, 3),
(44, 19, 3),
(45, 20, 3),
(46, 21, 3),
(47, 22, 3),
(48, 23, 3);

-- --------------------------------------------------------

--
-- Table structure for table `db_Partidas`
--

CREATE TABLE `db_Partidas` (
  `id` int NOT NULL,
  `torneo_id` int NOT NULL,
  `jugador_blancas` int DEFAULT NULL,
  `jugador_negras` int DEFAULT NULL,
  `resultado` enum('1-0','0-1','½-½') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ronda` int NOT NULL,
  `tablero` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `db_Partidas`
--
DELIMITER $$
CREATE TRIGGER `actualizar_elo_partida` AFTER UPDATE ON `db_Partidas` FOR EACH ROW BEGIN
    DECLARE ra DECIMAL(10,2);
    DECLARE rb DECIMAL(10,2);
    DECLARE ea DECIMAL(10,4);
    DECLARE eb DECIMAL(10,4);
    DECLARE nuevo_ra DECIMAL(10,2);
    DECLARE nuevo_rb DECIMAL(10,2);
    DECLARE k INT DEFAULT 20; -- Factor de ajuste
    DECLARE sa DECIMAL(3,2); -- Resultado de jugador A
    DECLARE sb DECIMAL(3,2); -- Resultado de jugador B

    -- Verificar que ambos jugadores sean válidos (no BYE)
    IF NEW.jugador_blancas IS NOT NULL AND NEW.jugador_negras IS NOT NULL THEN
        -- Convertir el resultado de la partida a valores numéricos
        IF NEW.resultado = '1-0' THEN
            SET sa = 1;
            SET sb = 0;
        ELSEIF NEW.resultado = '0-1' THEN
            SET sa = 0;
            SET sb = 1;
        ELSEIF NEW.resultado = '½-½' THEN
            SET sa = 0.5;
            SET sb = 0.5;
        END IF;

        -- Obtener el Elo actual de los jugadores
        SELECT elo INTO ra FROM db_Jugadores WHERE id = NEW.jugador_blancas;
        SELECT elo INTO rb FROM db_Jugadores WHERE id = NEW.jugador_negras;

        -- Calcular la expectativa de victoria
        SET ea = 1 / (1 + POWER(10, (rb - ra) / 400));
        SET eb = 1 / (1 + POWER(10, (ra - rb) / 400));

        -- Calcular nuevos valores de Elo
        SET nuevo_ra = ra + k * (sa - ea);
        SET nuevo_rb = rb + k * (sb - eb);

        -- Guardar el cambio en db_elo_history antes de actualizar el Elo
        INSERT INTO db_elo_history (jugador_id, elo_anterior, elo_nuevo)
        VALUES (NEW.jugador_blancas, ra, ROUND(nuevo_ra, 0)),
               (NEW.jugador_negras, rb, ROUND(nuevo_rb, 0));

        -- Actualizar los valores de Elo en la tabla de jugadores
        UPDATE db_Jugadores SET elo = ROUND(nuevo_ra, 0) WHERE id = NEW.jugador_blancas;
        UPDATE db_Jugadores SET elo = ROUND(nuevo_rb, 0) WHERE id = NEW.jugador_negras;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_partidas_before_insert` BEFORE INSERT ON `db_Partidas` FOR EACH ROW BEGIN
    IF NEW.jugador_blancas IS NULL THEN
        SET NEW.resultado = '0-1';
    ELSEIF NEW.jugador_negras IS NULL THEN
        SET NEW.resultado = '1-0';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `db_TorneoJugadores`
--

CREATE TABLE `db_TorneoJugadores` (
  `id` int NOT NULL,
  `torneo_id` int DEFAULT NULL,
  `jugador_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `db_Torneos`
--

CREATE TABLE `db_Torneos` (
  `id` int NOT NULL,
  `created_id` int NOT NULL COMMENT 'ID del creador del torneo',
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `tipo` varchar(55) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'presencial',
  `sistema` varchar(55) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dobleronda` tinyint(1) DEFAULT NULL,
  `estado` varchar(55) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'creado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `db_Usuarios`
--

CREATE TABLE `db_Usuarios` (
  `id` int NOT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `db_Usuarios`
--

INSERT INTO `db_Usuarios` (`id`, `google_id`, `nombre`, `email`, `remember_token`, `token_expiry`, `estatus`, `created_at`, `updated_at`) VALUES
(2, '114273681424298695369', 'Gabriel Urraca', 'gabrielurraca2@gmail.com', NULL, NULL, 1, '2025-03-29 22:18:34', '2025-03-29 22:18:34'),
(3, '111519573693715543584', 'GABRIEL R URRACA A', 'stodgoandroiddevelop@gmail.com', NULL, NULL, 1, '2025-03-30 03:28:36', '2025-03-30 03:28:36'),
(4, '114025526947536779679', 'Recado Digital 809', 'recadodigital809@gmail.com', NULL, NULL, 1, '2025-03-30 15:02:23', '2025-03-30 15:02:23');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_Partidas`
-- (See below for the actual view)
--
CREATE TABLE `vw_Partidas` (
`fecha_inicio` date
,`jugador_blancas` varchar(255)
,`jugador_negras` varchar(255)
,`partida_id` int
,`resultado` enum('1-0','0-1','½-½')
,`ronda` int
,`tablero` int
,`torneo` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_PuntosTorneos`
-- (See below for the actual view)
--
CREATE TABLE `vw_PuntosTorneos` (
`derrotas` bigint
,`elo` int
,`empates` bigint
,`fecha_inicio` date
,`jugador` varchar(255)
,`jugador_id` int
,`lugar` bigint unsigned
,`puntos` decimal(23,1)
,`torneo` varchar(255)
,`torneo_id` int
,`victorias` bigint
);

-- --------------------------------------------------------

--
-- Structure for view `vw_Partidas`
--
DROP TABLE IF EXISTS `vw_Partidas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `vw_Partidas`  AS SELECT `p`.`id` AS `partida_id`, `t`.`nombre` AS `torneo`, `t`.`fecha_inicio` AS `fecha_inicio`, `j1`.`nombre` AS `jugador_blancas`, `j2`.`nombre` AS `jugador_negras`, `p`.`resultado` AS `resultado`, `p`.`ronda` AS `ronda`, `p`.`tablero` AS `tablero` FROM (((`db_Partidas` `p` join `db_Torneos` `t` on((`p`.`torneo_id` = `t`.`id`))) join `db_Jugadores` `j1` on((`p`.`jugador_blancas` = `j1`.`id`))) join `db_Jugadores` `j2` on((`p`.`jugador_negras` = `j2`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_PuntosTorneos`
--
DROP TABLE IF EXISTS `vw_PuntosTorneos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `vw_PuntosTorneos`  AS SELECT `ranking`.`jugador_id` AS `jugador_id`, `ranking`.`jugador` AS `jugador`, `ranking`.`elo` AS `elo`, `ranking`.`torneo_id` AS `torneo_id`, `ranking`.`torneo` AS `torneo`, `ranking`.`fecha_inicio` AS `fecha_inicio`, `ranking`.`victorias` AS `victorias`, `ranking`.`empates` AS `empates`, `ranking`.`derrotas` AS `derrotas`, `ranking`.`puntos` AS `puntos`, rank() OVER (PARTITION BY `ranking`.`torneo_id` ORDER BY `ranking`.`puntos` desc ) AS `lugar` FROM (select `j`.`id` AS `jugador_id`,`j`.`nombre` AS `jugador`,`j`.`elo` AS `elo`,`t`.`id` AS `torneo_id`,`t`.`nombre` AS `torneo`,`t`.`fecha_inicio` AS `fecha_inicio`,count((case when (((`p`.`resultado` = '1-0') and (`p`.`jugador_blancas` = `j`.`id`)) or ((`p`.`resultado` = '0-1') and (`p`.`jugador_negras` = `j`.`id`))) then 1 end)) AS `victorias`,count((case when ((`p`.`resultado` = '½-½') and ((`p`.`jugador_blancas` = `j`.`id`) or (`p`.`jugador_negras` = `j`.`id`))) then 1 end)) AS `empates`,count((case when (((`p`.`resultado` = '0-1') and (`p`.`jugador_blancas` = `j`.`id`)) or ((`p`.`resultado` = '1-0') and (`p`.`jugador_negras` = `j`.`id`))) then 1 end)) AS `derrotas`,((count((case when (((`p`.`resultado` = '1-0') and (`p`.`jugador_blancas` = `j`.`id`)) or ((`p`.`resultado` = '0-1') and (`p`.`jugador_negras` = `j`.`id`))) then 1 end)) * 1) + (count((case when ((`p`.`resultado` = '½-½') and ((`p`.`jugador_blancas` = `j`.`id`) or (`p`.`jugador_negras` = `j`.`id`))) then 1 end)) * 0.5)) AS `puntos` from ((`db_Jugadores` `j` join `db_Partidas` `p` on(((`j`.`id` = `p`.`jugador_blancas`) or (`j`.`id` = `p`.`jugador_negras`)))) join `db_Torneos` `t` on((`p`.`torneo_id` = `t`.`id`))) group by `j`.`id`,`j`.`nombre`,`j`.`elo`,`t`.`id`,`t`.`nombre`,`t`.`fecha_inicio`) AS `ranking` ORDER BY `ranking`.`torneo_id` ASC, `ranking`.`puntos` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `db_elo_history`
--
ALTER TABLE `db_elo_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jugador_id` (`jugador_id`);

--
-- Indexes for table `db_Jugadores`
--
ALTER TABLE `db_Jugadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indexes for table `db_Liga`
--
ALTER TABLE `db_Liga`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indexes for table `db_Liga_Jugadores`
--
ALTER TABLE `db_Liga_Jugadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jugador_id` (`jugador_id`),
  ADD KEY `liga_id` (`liga_id`);

--
-- Indexes for table `db_Partidas`
--
ALTER TABLE `db_Partidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `torneo_id` (`torneo_id`),
  ADD KEY `jugador_blancas` (`jugador_blancas`),
  ADD KEY `jugador_negras` (`jugador_negras`),
  ADD KEY `idx_partidas_torneo` (`torneo_id`);

--
-- Indexes for table `db_TorneoJugadores`
--
ALTER TABLE `db_TorneoJugadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `torneo_id` (`torneo_id`,`jugador_id`),
  ADD KEY `jugador_id` (`jugador_id`);

--
-- Indexes for table `db_Torneos`
--
ALTER TABLE `db_Torneos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `fk_torneos_creador` (`created_id`),
  ADD KEY `idx_torneos_estado` (`estado`);

--
-- Indexes for table `db_Usuarios`
--
ALTER TABLE `db_Usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `db_elo_history`
--
ALTER TABLE `db_elo_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=339;

--
-- AUTO_INCREMENT for table `db_Jugadores`
--
ALTER TABLE `db_Jugadores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `db_Liga`
--
ALTER TABLE `db_Liga`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `db_Liga_Jugadores`
--
ALTER TABLE `db_Liga_Jugadores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `db_Partidas`
--
ALTER TABLE `db_Partidas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1269;

--
-- AUTO_INCREMENT for table `db_TorneoJugadores`
--
ALTER TABLE `db_TorneoJugadores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=248;

--
-- AUTO_INCREMENT for table `db_Torneos`
--
ALTER TABLE `db_Torneos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `db_Usuarios`
--
ALTER TABLE `db_Usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `db_elo_history`
--
ALTER TABLE `db_elo_history`
  ADD CONSTRAINT `db_elo_history_ibfk_1` FOREIGN KEY (`jugador_id`) REFERENCES `db_Jugadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `db_Liga_Jugadores`
--
ALTER TABLE `db_Liga_Jugadores`
  ADD CONSTRAINT `db_Liga_Jugadores_ibfk_1` FOREIGN KEY (`jugador_id`) REFERENCES `db_Jugadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `db_Liga_Jugadores_ibfk_2` FOREIGN KEY (`liga_id`) REFERENCES `db_Liga` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `db_Partidas`
--
ALTER TABLE `db_Partidas`
  ADD CONSTRAINT `db_Partidas_ibfk_1` FOREIGN KEY (`torneo_id`) REFERENCES `db_Torneos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `db_Partidas_ibfk_2` FOREIGN KEY (`jugador_blancas`) REFERENCES `db_Jugadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `db_Partidas_ibfk_3` FOREIGN KEY (`jugador_negras`) REFERENCES `db_Jugadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `db_TorneoJugadores`
--
ALTER TABLE `db_TorneoJugadores`
  ADD CONSTRAINT `db_TorneoJugadores_ibfk_1` FOREIGN KEY (`torneo_id`) REFERENCES `db_Torneos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `db_TorneoJugadores_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `db_Jugadores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `db_Torneos`
--
ALTER TABLE `db_Torneos`
  ADD CONSTRAINT `fk_torneos_creador` FOREIGN KEY (`created_id`) REFERENCES `db_Usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
