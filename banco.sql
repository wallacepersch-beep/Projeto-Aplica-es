
DROP DATABASE IF EXISTS `restaurante`;
CREATE DATABASE `restaurante`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `restaurante`;

SET NAMES utf8mb4;

-- --------------------------------------------------------
CREATE TABLE `clientes` (
  `idpessoa` int NOT NULL AUTO_INCREMENT,
  `nome`     varchar(70) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  PRIMARY KEY (`idpessoa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `mesas` (
  `idmesa` int NOT NULL AUTO_INCREMENT,
  `espaco` int NOT NULL COMMENT 'quantidade de lugares',
  PRIMARY KEY (`idmesa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `reservas` (
  `idreserva` int NOT NULL AUTO_INCREMENT,
  `idpessoa`  int NOT NULL,
  `idmesa`    int NULL DEFAULT NULL,
  `data`      date NOT NULL,
  `criada_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idreserva`),
  KEY `idx_reservas_data` (`data`),
  CONSTRAINT `fk_reservas_cliente` FOREIGN KEY (`idpessoa`) REFERENCES `clientes` (`idpessoa`),
  CONSTRAINT `fk_reservas_mesa`    FOREIGN KEY (`idmesa`)   REFERENCES `mesas` (`idmesa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
INSERT INTO `clientes` (`nome`, `telefone`) VALUES
  ('Ana Souza',    '51 991111111'),
  ('Bruno Lima',   '51 992222222'),
  ('Carla Mendes', '51 993333333'),
  ('Diego Alves',  '51 994444444'),
  ('Elisa Rocha',  '51 995555555');

INSERT INTO `mesas` (`espaco`) VALUES (2), (4), (4), (6), (8);

INSERT INTO `reservas` (`idpessoa`, `idmesa`, `data`) VALUES
  (1, 1, '2026-10-24'),
  (2, 3, '2026-10-25'),
  (3, 2, '2026-10-30'),
  (4, 4, '2026-11-05'),
  (5, 5, '2026-11-12');
