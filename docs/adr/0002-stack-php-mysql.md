# ADR-0002: Stack PHP, MySQL y monolito modular

- **Estado:** Aceptado
- **Fecha:** 2026-10-25
- **Decisores:** Equipo del proyecto y responsable académico

## Contexto

Se debe construir un MVP académico de gestión de RR. HH. con tres módulos:

1. Gestión de empleados.
2. Asistencia y permisos.
3. Reclutamiento y selección.

Restricciones:

- Proyecto universitario con alcance y tiempo limitados.
- Hosting gratuito **InfinityFree**, que ofrece PHP y MySQL, pero no Node.js.
- Dominio propio y HTTPS.
- Sin presupuesto para servicios de pago.
- Datos personales ficticios; no se usarán datos reales.
- Código organizado, mantenible y sin dependencia de un framework complejo.

## Opciones consideradas

### Opción A — PHP puro con núcleo propio (MVC modular)

- PHP 8.2+ con `strict_types`.
- Front controller, router y autoload PSR-4 propios.
- PDO para MySQL, sin ORM.
- Bootstrap 5 y JavaScript progresivo.
- Sesiones PHP, `password_hash()`, CSRF y RBAC.
- PHPUnit para pruebas.

Ventajas: control total, sin dependencias de runtime, despliegue sencillo en hosting
compartido, estructura didáctica y sin magia.

Desventajas: hay que construir router, validación, sesiones y vistas; mayor
responsabilidad sobre seguridad y calidad.

### Opción B — Laravel

- Framework completo con ORM Eloquent, migraciones, auth y ecosistema.

Ventajas: productividad, seguridad integrada y convenciones maduras.

Desventajas: mayor curva de aprendizaje, peso, requisitos de Composer/CLI y posible
dificultad en un hosting gratuito sin SSH. Para el alcance académico puede ocultar
demasiado la lógica que se quiere demostrar.

### Opción C — CodeIgniter

- Framework PHP ligero, adecuado para hosting compartido.

Ventajas: menos peso que Laravel, estructura MVC conocida.

Desventajas: añade una dependencia y convenciones que no son necesarias para tres
módulos; la configuración y el despliegue siguen siendo más complejos de explicar.

### Opción D — Node.js / Next.js

Descartada: InfinityFree no admite Node.js como backend. La propuesta inicial
[ADR-0001](0001-stack-propuesto.md) quedó rechazada.

## Decisión

Se adopta la **Opción A: PHP puro con un núcleo MVC mínimo y modular**, con este stack:

| Área | Decisión |
| ---- | -------- |
| Backend | PHP 8.2+ (producción: 8.4 si está disponible) |
| Base de datos | MySQL 8 / MariaDB 11, InnoDB, `utf8mb4` |
| Acceso a datos | PDO con consultas preparadas |
| ORM | Ninguno |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript |
| Arquitectura | Monolito modular MVC con front controller |
| Autenticación | Sesiones PHP + `password_hash()` |
| Autorización | RBAC `admin` / `hr` en middleware y servicios |
| Validación | Servidor (autoridad) + cliente (experiencia) |
| Testing | PHPUnit + pruebas funcionales manuales |
| Autoload | PSR-4 propio en runtime; Composer solo para desarrollo |
| Hosting | InfinityFree + dominio propio + HTTPS |
| Repositorio | Git y GitHub |

Estructura:

```text
public/       → front controller y assets
src/Core/     → router, request, PDO, sesión, auth, CSRF, vistas
src/Modules/  → Auth, Dashboard, Employees, Attendance, Recruitment
src/Shared/   → layout, partials y helpers
config/       → configuración y entorno
database/     → esquema, seed y migraciones
tests/        → PHPUnit
```

La comunicación entre módulos se realiza mediante contratos públicos. `Employees` es
el módulo maestro; `Attendance` y `Recruitment` no acceden a sus repositorios.

## Consecuencias

### Positivas

- Despliegue directo en InfinityFree sin Node ni servicios externos.
- Control total del flujo y de la seguridad.
- Estructura clara para demostrar separación de responsabilidades.
- Sin dependencias de runtime que puedan fallar en el hosting.
- SQL explícito y fácil de revisar.
- Coste cero de infraestructura, salvo el dominio.

### Negativas

- Hay que implementar y mantener el núcleo (router, request, validación, CSRF).
- Mayor riesgo de errores de seguridad si se omiten controles.
- Sin ORM, más SQL manual y mapeo de filas.
- Sin CLI/SSH en producción: migraciones y backups manuales.
- Sin entorno de staging real; se prueba en local antes de subir.
- La calidad depende de disciplina en revisiones y pruebas.

### Riesgos y mitigaciones

| Riesgo | Mitigación |
| ------ | ---------- |
| Vulnerabilidades por núcleo propio | Usar patrones conocidos, revisar OWASP y probar permisos/CSRF/XSS |
| Complejidad del router | Mantenerlo pequeño y cubierto por pruebas |
| SQL repetido | Centralizar en repositorios y revisar PRs |
| Hosting sin SSH | Autoload propio, despliegue FTP y backups manuales |
| Pérdida de datos | Exportaciones antes de cada cambio y restauración probada |
| Versión de PHP distinta | Evitar características exclusivas de 8.4; probar en 8.2+ |
| Dependencia de Composer | No usar Composer en runtime; solo para PHPUnit local |

## Implementación

- `public/index.php` es el único front controller.
- `src/bootstrap.php` carga configuración, autoload, sesión, zona horaria y errores.
- Las rutas se declaran por módulo en `routes.php`.
- Los controladores se construyen con sus dependencias de forma explícita.
- Las vistas usan `e()` para escapar y `csrf_field()` para formularios.
- Las migraciones se ejecutan manualmente en local y producción.
- `database/seed.sql` es solo para desarrollo/demo.

## Pendiente

- [ ] Instalar PHP/MySQL local y verificar versiones.
- [ ] Confirmar en InfinityFree la versión de PHP, MySQL y soporte de `.htaccess`.
- [ ] Implementar el núcleo y autenticación.
- [ ] Verificar que no se requiera `vendor/` en producción.
- [ ] Revisar términos y límites del hosting antes de la entrega.

## Enlaces

- [Arquitectura](../02-arquitectura.md)
- [Modelo de datos](../08-modelo-de-datos.md)
- [Despliegue](../06-deploy-y-dominio.md)
- [Seguridad](../07-seguridad-y-privacidad.md)
