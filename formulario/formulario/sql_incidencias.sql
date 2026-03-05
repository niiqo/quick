-- Script SQL para crear la tabla de incidencias
-- Ejecutar este script en tu base de datos MySQL

CREATE TABLE IF NOT EXISTS `incidencias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_orden` INT(11) NOT NULL,
  `fecha` DATETIME NOT NULL,
  `incidencia` TEXT NOT NULL,
  `usuario` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_orden` (`id_orden`),
  CONSTRAINT `fk_incidencias_orden` FOREIGN KEY (`id_orden`) REFERENCES `info_orden` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para búsquedas rápidas por orden
CREATE INDEX idx_incidencias_orden ON incidencias(id_orden);
