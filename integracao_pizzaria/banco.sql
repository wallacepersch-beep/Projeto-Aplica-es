DROP DATABASE IF EXISTS `restaurante`;
CREATE DATABASE `restaurante` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `restaurante`;
SET NAMES utf8mb4;

CREATE TABLE `clientes` (
  `idpessoa` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(70) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `cpf` char(11) NOT NULL,
  PRIMARY KEY (`idpessoa`),
  UNIQUE KEY `uq_clientes_cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mesas` (
  `idmesa` int NOT NULL AUTO_INCREMENT,
  `espaco` int NOT NULL COMMENT 'quantidade de lugares',
  PRIMARY KEY (`idmesa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reservas` (
  `idreserva` int NOT NULL AUTO_INCREMENT,
  `idpessoa` int NOT NULL,
  `idmesa` int NULL DEFAULT NULL,
  `data` date NOT NULL,
  `criada_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idreserva`),
  KEY `idx_reservas_data` (`data`),
  CONSTRAINT `fk_reservas_cliente` FOREIGN KEY (`idpessoa`) REFERENCES `clientes` (`idpessoa`),
  CONSTRAINT `fk_reservas_mesa` FOREIGN KEY (`idmesa`) REFERENCES `mesas` (`idmesa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios_admin` (
  `idusuario` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`idusuario`),
  UNIQUE KEY `uq_admin_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tokens_api` (
  `idtoken` bigint NOT NULL AUTO_INCREMENT,
  `token_hash` char(64) NOT NULL,
  `tipo` enum('cliente','admin') NOT NULL,
  `idpessoa` int NULL,
  `idusuario` int NULL,
  `expira_em` datetime NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idtoken`),
  UNIQUE KEY `uq_tokens_hash` (`token_hash`),
  KEY `idx_tokens_expira` (`expira_em`),
  CONSTRAINT `fk_tokens_cliente` FOREIGN KEY (`idpessoa`) REFERENCES `clientes` (`idpessoa`) ON DELETE CASCADE,
  CONSTRAINT `fk_tokens_admin` FOREIGN KEY (`idusuario`) REFERENCES `usuarios_admin` (`idusuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes` (`nome`, `telefone`, `cpf`) VALUES
 ('Ana Souza', '51991111111', '11144477735'),
 ('Bruno Lima', '51992222222', '22233344496'),
 ('Carla Mendes', '51993333333', '33344455567'),
 ('Diego Alves', '51994444444', '44455566678'),
 ('Elisa Rocha', '51995555555', '55566677789');
INSERT INTO `mesas` (`espaco`) VALUES (2), (4), (4), (6), (8);
INSERT INTO `reservas` (`idpessoa`, `idmesa`, `data`) VALUES
 (1, 1, '2026-10-30'), (2, 3, '2026-10-31'), (3, 2, '2026-11-02'),
 (4, 4, '2026-11-05'), (5, 5, '2026-11-12');
-- Conta de desenvolvimento para o piloto. Troque a senha antes de qualquer publicação.
INSERT INTO `usuarios_admin` (`usuario`, `senha_hash`) VALUES
 ('admin', '$2y$12$j2QBNZMxQ2Ks6Rv4DfxOqumPVnwreWuATu.82jLUu9tEdecGsXDlC');
