-- Migración: 202610292100_add_password_changed_at_to_users
-- Propósito: registrar la última modificación de contraseña para invalidar
--            las sesiones abiertas con la contraseña anterior.
-- Autor: Auditoría técnica
-- Fecha: 2026-10-29
-- Rollback: ALTER TABLE users DROP COLUMN password_changed_at;
--
-- Nota: las cuentas existentes quedan con NULL. Eso no invalida sus sesiones
-- actuales; la invalidación empieza en el siguiente cambio o restablecimiento
-- de contraseña. Aplicar en una copia de desarrollo antes de producción.

ALTER TABLE users
    ADD COLUMN password_changed_at DATETIME(6) NULL AFTER password_hash;
