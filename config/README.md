# config — Configuración

Configuración central de la aplicación y de la base de datos.

## Archivos previstos

```text
config/
├── app.php        # Nombre, entorno, URL, zona horaria y sesión
└── database.php   # Host, puerto, base, usuario y charset
```

Cada archivo devuelve un array:

```php
<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Gestión RR. HH.',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
];
```

La lectura de `.env` se implementa en `src/Core/Env.php` o en `src/bootstrap.php`.
No se debe leer `$_ENV` directamente desde los módulos.

## Reglas

- `.env` no se versiona; `.env.example` sí.
- Los secretos solo viven en `.env` y en el proveedor de hosting.
- Validar las variables obligatorias al arrancar y fallar con un mensaje claro.
- No incluir credenciales por defecto en el código.
- No acceder a la configuración desde una vista.
- `APP_DEBUG` debe ser `false` en producción.
- `SESSION_SECURE` debe ser `true` en producción.

## Variables

El listado completo está en `.env.example` y en
[docs/06-deploy-y-dominio.md](../docs/06-deploy-y-dominio.md).
