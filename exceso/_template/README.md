# Plantilla de módulo PHP

Carpeta de referencia para crear módulos nuevos. **No se importa directamente.**

## Uso

1. Copiar esta carpeta a `src/Modules/<NombreEnPascalCase>/`.
2. Renombrar el namespace y las clases.
3. Completar `README.md` con la ficha funcional.
4. Implementar de dentro hacia fuera: repositorio → servicio → controlador → vistas.
5. Registrar las rutas en `routes.php` y en la composición de la aplicación.
6. Añadir pruebas.

## Estructura

```text
<Modulo>/
├── Controllers/       # Reciben HTTP y eligen la vista
├── Services/          # Reglas de negocio, permisos y transacciones
├── Repositories/      # SQL con PDO y mapeo
├── Models/            # Entidades y value objects simples
├── Views/             # Plantillas PHP con Bootstrap
├── routes.php         # Rutas y middlewares del módulo
└── README.md          # Ficha funcional
```

## Responsabilidades

| Carpeta | Contiene | No contiene |
| ------- | -------- | ----------- |
| `Controllers` | Acciones HTTP, validación de forma, llamada al servicio, render | SQL, reglas de negocio |
| `Services` | Reglas, autorización de recurso, transacciones, coordinación | HTML, superglobales |
| `Repositories` | Consultas preparadas, mapeo de filas | HTML, reglas de negocio |
| `Models` | Datos tipados y comportamiento simple | Consultas, HTML |
| `Views` | HTML, Bootstrap, escape de salida | SQL, lógica de negocio |

## Ejemplo de namespace

```php
<?php

declare(strict_types=1);

namespace App\Modules\Example\Services;

final class ExampleService
{
    public function __construct(
        private readonly ExampleRepository $repository
    ) {
    }
}
```

## Ejemplo de ruta (conceptual)

```php
<?php

declare(strict_types=1);

use App\Modules\Example\Controllers\ExampleController;

return [
    ['GET', '/examples', [ExampleController::class, 'index'], ['auth']],
    ['GET', '/examples/create', [ExampleController::class, 'create'], ['auth']],
    ['POST', '/examples', [ExampleController::class, 'store'], ['auth', 'csrf']],
];
```

El formato definitivo se implementará en `src/Core/Router.php`. Mantener las rutas
declarativas y sin lógica.

## Checklist de creación

- [ ] Ficha funcional completa.
- [ ] Nombre de módulo en PascalCase.
- [ ] Namespace correcto.
- [ ] Entidades y reglas definidas.
- [ ] Repositorio con consultas preparadas.
- [ ] Servicio con validaciones y permisos.
- [ ] Controlador delgado.
- [ ] Vistas con layout, errores y estados vacíos.
- [ ] Rutas y middlewares registrados.
- [ ] Pruebas unitarias e integración.
- [ ] Documentación y ADR actualizados.
- [ ] Sin acceso a repositorios de otros módulos.
- [ ] Sin datos reales.

## Reglas

- El módulo es dueño de sus tablas y consultas.
- No se importan clases internas de otro módulo.
- La comunicación usa contratos públicos.
- Los errores de negocio se modelan y se muestran sin detalles internos.
- Las vistas escapan toda salida con `e()`.
- Las acciones de mutación usan CSRF.
- Las operaciones con varias tablas usan transacción.
