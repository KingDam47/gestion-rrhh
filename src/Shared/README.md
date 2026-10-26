# src/Shared — Presentación compartida

Layout, partials y helpers sin lógica de negocio.

## Estructura

```text
src/Shared/
├── Views/
│   ├── layouts/
│   │   └── app.php          # Layout con navegación y Bootstrap
│   └── partials/
│       ├── flash.php        # Mensajes de éxito/error
│       ├── errors.php       # Errores de validación
│       └── pagination.php   # Paginación reutilizable
└── Support/
    └── helpers.php          # e(), csrf_field(), old(), format_date()
```

## Helpers previstos

| Helper | Uso |
| ------ | --- |
| `e(string $value): string` | Escape HTML seguro |
| `csrf_field(): string` | Campo oculto con token CSRF |
| `old(string $key, mixed $default = '')` | Repoblar formularios |
| `flash(string $type, string $message)` | Guardar mensaje temporal |
| `format_date(?string $date): string` | Formatear fechas para la vista |
| `status_label(string $status): string` | Traducir estados a español |

## Reglas

- Sin consultas SQL ni llamadas a repositorios.
- Sin reglas de negocio.
- Sin entidades de módulos.
- Todo dato dinámico se escapa.
- Los helpers son pequeños, puros cuando sea posible y probados.
- No incluir secretos ni configuración.
- El layout debe ser accesible, responsive y usable con teclado.

## Layout

El layout compartido incluye:

- `<head>` con Bootstrap;
- barra de navegación según el rol;
- mensajes flash;
- contenido de la página;
- footer;
- scripts al final del documento.

Los módulos solo aportan el contenido de su vista.
