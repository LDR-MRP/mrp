CREATE TABLE `prv_det_plataformas` (
  `id_plataforma` bigint NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint NOT NULL COMMENT 'FK prv_cat_proveedores',
  `numero_economico` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_serie_vin` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacidad_vehiculos` tinyint unsigned NOT NULL DEFAULT '1',
  `estatus_operativo` tinyint(1) DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_plataforma`),
  KEY `idx_plataforma_prov` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prv_det_plataforma_chofer_historial` (
    `id` bigint NOT NULL AUTO_INCREMENT,
    `id_plataforma` bigint NOT NULL,
    `id_chofer` bigint NOT NULL,
    `activo` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
