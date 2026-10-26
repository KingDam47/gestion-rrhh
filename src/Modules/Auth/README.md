# Módulo transversal — Auth

> **Estado:** implementado y verificado con los roles `admin` y `hr` separados ·
> **Carpeta:** `src/Modules/Auth`
> **Depende de:** Core · **Es dependencia de:** todos los módulos

## 1. Objetivo

Gestionar el inicio y cierre de sesión, las cuentas de acceso, los roles `admin` y `hr`
y la consulta de auditoría. No contiene lógica de empleados, asistencia ni reclutamiento.

## 2. Alcance

Incluye:

- Login con correo y contraseña.
- Logout y destrucción de sesión.
- Sesiones seguras y expiración por inactividad.
- CSRF en formularios.
- Roles `admin` y `hr`.
- Administración de usuarios exclusiva del administrador:
  - crear,
  - consultar, buscar y filtrar,
  - editar nombre, correo y rol,
  - desactivar y reactivar,
  - restablecer contraseña.
- Cambio de contraseña del propio usuario para ambos roles.
- Consulta de auditoría exclusiva del administrador.
- Invalidación de la sesión afectada cuando cambia el rol o se desactiva la cuenta.

No incluye:

- Autorregistro.
- Recuperación de contraseña por correo (no hay envío de correos en el MVP).
- Inicio de sesión con Google/Microsoft.
- MFA.
- Portal de empleados o candidatos.
- Registro automático de cada login, logout o intento fallido. La tabla demo conserva
  un registro de ejemplo, pero el MVP solo audita las acciones administrativas que se
  indican en la sección 4.

## 3. Entidad `users`

| Campo | Regla |
| ----- | ----- |
| `name` | Obligatorio, máximo 120 caracteres. |
| `email` | Obligatorio, único, normalizado a minúsculas, máximo 190 caracteres. |
| `password_hash` | Generado con `password_hash()`. Nunca texto plano. |
| `role` | `admin` o `hr`. |
| `is_active` | Los usuarios inactivos no pueden iniciar sesión. |
| `last_login_at` | Se actualiza al iniciar sesión. |

## 4. Reglas de negocio

- El correo es único y se compara en minúsculas.
- La contraseña tiene al menos 10 caracteres y debe confirmarse.
- Al cambiar la propia contraseña se exige la contraseña actual y que la nueva sea
  distinta.
- Solo el administrador puede crear, editar, desactivar, reactivar y restablecer
  contraseñas de otras cuentas.
- El rol `hr` no puede administrar cuentas ni consultar la auditoría.
- Un administrador no puede desactivar su propia cuenta.
- No se puede desactivar ni degradar al último administrador activo. La comprobación se
  ejecuta dentro de una transacción con bloqueo de los administradores activos.
- Un usuario desactivado no puede iniciar sesión; si tenía sesión, se invalida en la
  siguiente petición.
- Un cambio de rol también invalida la sesión de la cuenta afectada en la siguiente
  petición.
- Los usuarios no se eliminan si tienen historial; se desactivan.
- Se auditan el alta, la edición, la activación/desactivación, el restablecimiento de
  contraseña y el cambio de contraseña propia.
- La metadata de auditoría se limita a `status` y `role`; nunca guarda nombres, correos,
  contraseñas ni hashes.
- La sesión se regenera al iniciar sesión. Tras varios intentos fallidos se aplica un
  retraso progresivo.

## 5. Pantallas

- Login.
- Listado de usuarios con búsqueda, filtros, resumen y paginación (solo admin).
- Crear usuario (solo admin).
- Editar usuario y rol (solo admin).
- Desactivar/reactivar usuario (solo admin).
- Restablecer contraseña (solo admin).
- Cambiar contraseña propia (`/account/password`, ambos roles).
- Auditoría con filtros y paginación (solo admin).

## 6. Seguridad

- Cookies `HttpOnly`, `SameSite=Lax` y `Secure` en producción.
- `session_regenerate_id(true)` al autenticar.
- Token CSRF en login, logout y todos los formularios de usuario y cuenta.
- Mensaje genérico en login fallido (no revelar si el correo existe).
- `password_verify()` y `password_hash()` con `PASSWORD_DEFAULT`.
- Rate limiting básico por sesión.
- Rutas `/users*` y `/audit` protegidas con `role:admin`; `/account/password` disponible
  para ambos roles.
- Los permisos se validan en el servidor, no solo ocultando enlaces.

## 7. Criterios de aceptación

- [x] Un usuario activo inicia sesión con credenciales válidas.
- [x] Un usuario inactivo o con contraseña incorrecta recibe un error genérico.
- [x] El logout destruye la sesión.
- [x] Las rutas privadas redirigen al login si no hay sesión.
- [x] El rol `hr` no accede a la gestión de usuarios ni a la auditoría.
- [x] El rol `hr` conserva el acceso operativo a los tres módulos.
- [x] El admin crea, edita, desactiva y reactiva usuarios.
- [x] El admin restablece contraseñas de otras cuentas.
- [x] Ambos roles cambian su propia contraseña.
- [x] No se puede dejar el sistema sin administrador activo.
- [x] Los formularios validan CSRF.
- [x] Los cambios sensibles quedan auditados con metadata limitada.

## 8. Estructura de implementación

```text
src/Modules/Auth/
├── Contracts/        # UserLookup para otros módulos
├── Controllers/      # AuthController, UserController, ProfileController, AuditController
├── Services/         # AuthService, UserLookupService, UserService, AuditLogService
├── Repositories/     # UserRepository, AuditLogRepository
├── Middleware/       # ActiveUserMiddleware
├── Support/          # UserData (normalización de entradas)
├── Views/            # login, users/, account/, audit/
├── routes.php
└── README.md
```

## 9. Contrato público implementado

`src/Modules/Auth/Contracts/UserLookup.php` está implementado por
`UserLookupService` y permite a otros módulos resolver nombres de usuarios sin
consultar la tabla `users`:

- `names(array $userIds)`: nombres indexados por ID.

Attendance lo usa para mostrar el nombre de quien revisó una solicitud de permiso.

## 10. Dependencias

- `Core\Session`, `Core\Auth`, `Core\Csrf`, `Core\Validator`, `Core\Database`.
- `audit_logs` para trazabilidad.
- No depende de los módulos de negocio.

## 11. Verificación

- `tests/Functional/RolesHttpCheck.php`: 183 comprobaciones HTTP (2026-10-26).
- `tests/Unit/UserDataTest.php` y `tests/Unit/AuditLabelTest.php`: normalización y
  etiquetas de auditoría.
- PHPUnit del proyecto: 40 pruebas y 111 aserciones correctas.
- Probado con PHP 8.4 y MariaDB 11.4; queda pendiente MySQL 8 e InfinityFree.
