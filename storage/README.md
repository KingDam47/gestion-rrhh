# storage — Archivos internos

Directorio para logs. **No debe ser accesible por HTTP.**

## Estructura

```text
storage/
├── logs/         # Errores y eventos técnicos
└── rate-limits/  # Contadores del limitador de login
```

## Reglas

- Nunca guardar archivos públicos aquí.
- No registrar contraseñas, tokens, CSRF ni datos personales.
- `rate-limits/` solo contiene hashes de correo/IP y contadores; no guarda los
  valores originales.
- Rotar o limpiar logs periódicamente. El limitador borra sus archivos caducados
  de forma oportunista.
- No versionar el contenido; sí el `.gitkeep` de `logs/` y `rate-limits/`.
- El `.htaccess` raíz y el de `storage/` bloquean el acceso web.
- En producción, revisar permisos de escritura solo para el usuario de PHP.
- No incluir `storage/` en backups públicos ni en paquetes compartidos.

## Depuración

En local se puede registrar el detalle técnico. En producción:

- `APP_DEBUG=false`;
- mensaje genérico al usuario;
- detalle en el log;
- sin trazas en la respuesta HTTP.
