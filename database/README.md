# database — Esquema y datos

## Archivos

| Archivo | Uso |
| ------- | --- |
| `schema.sql` | Esquema inicial completo. Se importa en una base limpia. |
| `seed.sql` | Datos ficticios de demostración. **Destructivo**, solo desarrollo. |
| `migrations/` | Cambios posteriores al esquema inicial. |

## Crear la base local

```sql
CREATE DATABASE gestion_rrhh
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Después, importar en este orden:

1. `database/schema.sql`
2. `database/seed.sql` (solo desarrollo)

Desde phpMyAdmin se puede usar la pestaña **Importar**. Desde consola:

```bash
mysql -u root -p gestion_rrhh < database/schema.sql
mysql -u root -p gestion_rrhh < database/seed.sql
```

## Credenciales demo

El seed crea:

| Rol | Correo | Contraseña |
| --- | ------ | ---------- |
| Administrador | `admin@gestion-rrhh.test` | `password123` |
| RR. HH. | `rrhh@gestion-rrhh.test` | `password123` |

> La contraseña es deliberadamente débil y solo existe para la demo local. En
> producción se debe crear un administrador con una contraseña segura y cambiar
> cualquier credencial de ejemplo.

## Crear un administrador en producción

En local, generar un hash:

```bash
php -r "echo password_hash('TU_CONTRASENA_SEGURA', PASSWORD_DEFAULT), PHP_EOL;"
```

Luego insertar el usuario desde phpMyAdmin con ese hash. No subir `seed.sql` a
producción.

## Migraciones

- Nombre: `YYYYMMDDHHMM_descripcion.sql`.
- Una migración aplicada no se edita.
- Probar en una base limpia antes de aplicarla.
- Documentar el rollback en la cabecera.
- En InfinityFree se aplican manualmente desde phpMyAdmin.
- En una base existente, aplicar
  `migrations/202610292100_add_password_changed_at_to_users.sql` antes de desplegar
  el código que invalida sesiones por cambio de contraseña.

## Reglas

- Sin datos reales.
- Sin documentos de identidad, fotos ni currículos.
- El seed no se ejecuta en producción.
- Los backups se guardan fuera del repositorio.
- El esquema debe funcionar en MySQL 8 y MariaDB 11.
