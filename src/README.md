# Código fuente

## Organización

```text
src/
├── Core/          # Infraestructura técnica reutilizable
├── Modules/       # Módulos de negocio autocontenidos
└── Shared/        # Layout, partials y helpers de presentación
```

## Flujo de dependencias

```text
public/index.php
  → Core (router, request, session, auth, CSRF)
    → Modules/<Modulo>/Controllers
      → Services
        → Repositories
          → PDO/MySQL

Modules → Shared (solo presentación)
Core → sin dependencias de Modules
Shared → sin dependencias de Modules ni negocio
```

## Dónde va cada cosa

| Necesidad | Ubicación |
| --------- | --------- |
| Ruta o front controller | `public/index.php`, `routes.php` del módulo |
| Controlador HTTP | `Modules/<Modulo>/Controllers` |
| Regla de negocio | `Modules/<Modulo>/Services` |
| Consulta SQL | `Modules/<Modulo>/Repositories` |
| Normalización o datos de apoyo | `Modules/<Modulo>/Support` |
| Vista del módulo | `Modules/<Modulo>/Views` |
| Router, sesión, auth, CSRF, PDO | `src/Core` |
| Layout y partials | `src/Shared/Views` |
| Helper de escape o formato | `src/Shared/Support` |
| Configuración | `config/` |
| Esquema y seed | `database/` |
| Assets públicos | `public/assets/` |
| Pruebas | `tests/` |

## Reglas

1. `public/` es el único directorio expuesto.
2. No importar archivos internos de otro módulo.
3. No acceder a la base de datos desde controladores o vistas.
4. No duplicar reglas entre JavaScript y PHP.
5. No crear carpetas globales por tipo fuera de un módulo.
6. No añadir un módulo sin ficha, rutas y pruebas.
7. No usar datos reales.

## Namespaces

- `App\Core\...` → `src/Core/...`
- `App\Modules\<Modulo>\...` → `src/Modules/<Modulo>/...`
- `App\Shared\...` → `src/Shared/...`
- `Tests\...` → `tests/...`

Todo archivo PHP declara `strict_types=1` y usa indentación de 4 espacios.

## Estado

El MVP está implementado: núcleo, autenticación, separación de roles, Empleados,
Asistencia, Reclutamiento, Dashboard y pruebas unitarias/funcionales. Está desplegado en
`https://evolvehr.freehosting.dev`. Quedan pendientes la prueba en MySQL 8 y la revisión
móvil de los módulos 01, 02 y 03. La verificación se hizo con PHP 8.4 y MariaDB 11.4
portátiles y también en el hosting (PHP 8.4.27 y MariaDB 11.4.13).
