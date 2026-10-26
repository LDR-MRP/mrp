-- Actualización de INSERTs para sys_notification_distribution
-- Se agrega columna userid (NULL para distribuciones por rol)

INSERT INTO `sys_notification_distribution` (`id`, `event_key`, `rolid`, `userid`, `plantaid`, `is_active`, `updated_at`) VALUES
(1, 'supplier_onboarding_complete', 54, NULL, 50, 0, '2026-05-18 19:20:07'),
(2, 'supplier_onboarding_complete', 57, NULL, 50, 0, '2026-05-18 22:15:16'),
(3, 'supplier_onboarding_complete', 58, NULL, 50, 0, '2026-05-18 22:15:16'),
(4, 'supplier_onboarding_complete', NULL, 115, NULL, 1, '2026-05-18 22:15:16'),
(5, 'supplier_ready_for_approval', 57, NULL, 50, 1, '2026-05-18 14:38:47'),
(6, 'supplier_ready_for_approval', 57, NULL, 51, 1, '2026-05-18 14:38:47'),
(7, 'supplier_ready_for_approval', 58, NULL, 50, 1, '2026-05-18 14:38:47'),
(8, 'supplier_ready_for_approval', 58, NULL, 51, 1, '2026-05-18 14:38:47'),
(9, 'supplier_ready_for_approval', NULL, 115, NULL, 1, '2026-05-18 14:38:47');
