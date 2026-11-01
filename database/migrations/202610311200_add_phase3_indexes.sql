-- Migración: 202610311200_add_phase3_indexes
-- Propósito: índices aditivos para el orden de postulaciones y la comprobación
--            de solapamiento de permisos por empleado.
-- Autor: Auditoría técnica (Fase 3)
-- Fecha: 2026-10-31
-- Rollback:
--   ALTER TABLE applications DROP INDEX idx_applications_applied_at;
--   ALTER TABLE leave_requests DROP INDEX idx_leave_requests_overlap;
--
-- Es idempotente: comprueba information_schema antes de crear cada índice, así
-- que puede ejecutarse en una base que ya los tenga (por ejemplo, una base
-- creada con el schema.sql actualizado).

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'applications'
      AND index_name = 'idx_applications_applied_at'
);
SET @sql := IF(
    @exists = 0,
    'ALTER TABLE applications ADD KEY idx_applications_applied_at (applied_at, id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'leave_requests'
      AND index_name = 'idx_leave_requests_overlap'
);
SET @sql := IF(
    @exists = 0,
    'ALTER TABLE leave_requests ADD KEY idx_leave_requests_overlap (employee_id, status, start_date, end_date)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
