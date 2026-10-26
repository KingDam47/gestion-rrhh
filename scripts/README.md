# scripts — Automatizaciones

Scripts de apoyo para desarrollo y operación. No forman parte del runtime web.

## Scripts disponibles

| Script | Propósito |
| ------ | --------- |
| `create-admin.php` | Crear un administrador con contraseña segura. |

Los scripts de backup y empaquetado quedan como tareas futuras del roadmap; no se
incluyen hasta implementarlos.

## Reglas

- Documentar uso, parámetros y requisitos al inicio del script.
- No incluir secretos ni credenciales en el código.
- Pedir confirmación antes de operar contra producción.
- Usar datos ficticios.
- No ejecutar scripts de escritura desde el navegador.
- Los scripts CLI requieren PHP instalado localmente.
- En InfinityFree no hay SSH; las operaciones de producción se hacen por FTP y
  phpMyAdmin.

## Ejemplo de cabecera

```php
<?php

declare(strict_types=1);

/**
 * Uso: php scripts/create-admin.php --email=admin@example.test
 * Requiere: conexión a la base configurada en .env.
 * Efecto: crea un usuario admin activo si el correo no existe.
 */
```

## Crear un administrador en producción

1. Generar el hash localmente con `password_hash()`.
2. Insertar el usuario desde phpMyAdmin con ese hash.
3. No subir el script ni `.env` a un directorio público.
