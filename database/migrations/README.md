# Migraciones

Cambios posteriores al esquema inicial.

## Convención

```text
YYYYMMDDHHMM_descripcion-en-kebab-case.sql
```

Ejemplo:

```text
202611011200_add_termination_date_to_employees.sql
```

## Reglas

- Una migración aplicada no se edita; se crea otra.
- Incluir una cabecera con propósito, autor, fecha y rollback.
- Probar en una base limpia y en una copia de desarrollo.
- Usar transacciones cuando MySQL lo permita para el tipo de operación.
- No incluir datos reales.
- No borrar tablas o columnas sin backup y revisión.
- Aplicar manualmente en InfinityFree desde phpMyAdmin.

## Cabecera sugerida

```sql
-- Migración: 202611011200_add_termination_date_to_employees
-- Propósito: registrar la fecha de cese del empleado.
-- Rollback: ALTER TABLE employees DROP COLUMN termination_date;
```
