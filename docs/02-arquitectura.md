# Arquitectura

## 1. Enfoque: monolito modular con MVC

La aplicación será **un solo despliegue PHP** con **módulos de negocio autocontenidos**.
No se usará un framework completo ni microservicios: el objetivo académico es mantener
el control del flujo, evitar magia innecesaria y demostrar una separación clara de
responsabilidades.

Componentes principales:

- **Front controller:** todas las peticiones entran por `public/index.php`.
- **Router:** asocia método HTTP y ruta con un controlador y sus middlewares.
- **Módulos:** cada capacidad de negocio tiene controladores, servicios, repositorios,
  modelos y vistas.
- **Core:** infraestructura técnica reutilizable (request, response, base de datos,
  sesión, autenticación, CSRF, validación, vistas).
- **Shared:** layout, partials y helpers sin lógica de negocio.

## 2. Capas y flujo de una petición

```text
Navegador
  │
  ▼
public/index.php  (front controller)
  │
  ├── Router + middlewares (auth, role, CSRF)
  │
  ▼
Controller  (HTTP: validar forma, llamar al servicio, elegir vista)
  │
  ▼
Service  (reglas de negocio, permisos, transacciones)
  │
  ▼
Repository  (PDO, SQL preparado, mapeo a modelos)
  │
  ▼
MySQL
```

La respuesta vuelve como una vista PHP con Bootstrap 5. Las vistas no consultan la
base de datos ni contienen reglas de negocio.

## 3. Responsabilidades por capa

| Capa | Responsabilidad | No debe hacer |
| ---- | --------------- | ------------- |
| `public/` | Docroot, front controller, assets y `.htaccess` | Lógica de negocio ni secretos |
| `src/Core/` | Router, request, response, PDO, sesión, auth, CSRF, validación, vistas | Conocer entidades de negocio |
| `src/Modules/<Modulo>/Controllers/` | Recibir HTTP, invocar servicios, preparar datos para la vista | SQL ni reglas de negocio |
| `src/Modules/<Modulo>/Services/` | Reglas, permisos de recurso, transacciones y coordinación | HTML ni acceso directo a `$_POST` |
| `src/Modules/<Modulo>/Repositories/` | SQL con PDO y mapeo de filas | HTML ni reglas de negocio |
| `src/Modules/<Modulo>/Support/` | Normalización, validación de entrada y datos de apoyo | Consultas ni HTML |
| `src/Modules/<Modulo>/Views/` | Presentación con HTML y Bootstrap | SQL, servicios o cálculos de negocio |
| `src/Shared/` | Layout, partials, helpers de presentación | Entidades o reglas de negocio |
| `config/` | Configuración de app y base de datos | Secretos versionados |
| `database/` | Esquema, seed y migraciones | Datos reales |
| `storage/` | Logs fuera del docroot | Archivos públicos |
| `tests/` | Pruebas PHPUnit y funcionales | Datos reales |

## 4. Estructura de un módulo

```text
src/Modules/Employees/
├── Controllers/
│   └── EmployeeController.php
├── Services/
│   └── EmployeeService.php
├── Repositories/
│   └── EmployeeRepository.php
├── Support/
│   └── EmployeeData.php
├── Contracts/              # Solo si otro módulo necesita un contrato público
│   └── EmployeeLookup.php
├── Views/
│   ├── index.php
│   ├── create.php
│   ├── edit.php
│   └── show.php
├── routes.php              # Rutas del módulo
└── README.md               # Ficha funcional y decisiones
```

Reglas:

- El controlador no instancia repositorios; recibe el servicio ya construido.
- El servicio no imprime HTML ni lee superglobales.
- El repositorio no decide reglas de negocio.
- Las vistas reciben datos ya preparados y escapan toda salida.
- `routes.php` declara rutas y middlewares; no contiene lógica.

## 5. Reglas de dependencia

1. `public/index.php` es el único punto de entrada.
2. `Core` no importa nada de `Modules`.
3. `Shared` no importa módulos ni entidades de negocio.
4. Un módulo no accede a las tablas ni repositorios de otro módulo.
5. La comunicación entre módulos se hace mediante un **contrato público** o servicio
   expuesto por el módulo dueño.
6. `Employees` es el módulo maestro; `Attendance` y `Recruitment` pueden consumir su
   contrato de lectura, nunca sus repositorios.
7. Las dependencias se construyen en la composición (bootstrap o factory), no dentro
   de un controlador.
8. No se permiten dependencias circulares.

### Dependencias

```text
Auth ──────────────► Core (sesiones, CSRF)
Dashboard ─────────► Employees, Attendance, Recruitment (solo lectura agregada)
Attendance ────────► Employees (contrato de lectura)
Recruitment ───────► Employees (contrato de lectura y alta guiada)
Employees ─────────► Auth/Core (usuario creador)
```

`Employees` no depende de `Attendance` ni de `Recruitment`. `Attendance` y
`Recruitment` no dependen entre sí.

## 6. Composición y autoload

- Namespace raíz: `App\`, mapeado a `src/`.
- Namespace de pruebas: `Tests\`, mapeado a `tests/`.
- `declare(strict_types=1);` en todos los archivos PHP.
- Autoload PSR-4 propio para runtime; Composer solo para herramientas de desarrollo
  (PHPUnit). Así el hosting no depende de `vendor/` en producción.
- Las dependencias de cada controlador se construyen explícitamente al registrar las
  rutas o mediante una fábrica pequeña en `src/bootstrap.php`.
- Se usa un contenedor explícito y pequeño, sin autowiring ni reflexión.

## 7. Stack definitivo

| Área | Tecnología | Uso |
| ---- | ---------- | --- |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript | Formularios, tablas, navegación y responsive |
| Backend | PHP 8.2+ (objetivo 8.4 en InfinityFree) | Lógica, sesiones y renderizado |
| Base de datos | MySQL 8 / MariaDB 11 | Datos relacionales |
| Acceso a datos | PDO | Consultas preparadas y transacciones |
| ORM | Ninguno | SQL explícito y controlado |
| Autenticación | Sesiones PHP + `password_hash()` | Login y contraseñas |
| Autorización | RBAC simple (`admin`, `hr`) | Middleware y comprobaciones de servicio |
| Validación | PHP (servidor) + JavaScript (cliente) | Datos y reglas |
| Testing | PHPUnit + pruebas manuales | Reglas, permisos y flujos |
| Hosting | InfinityFree + dominio propio | PHP/MySQL y HTTPS |

Justificación completa: [ADR-0002](adr/0002-stack-php-mysql.md).

## 8. Base de datos

- Motor InnoDB, charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
- Claves primarias `BIGINT UNSIGNED AUTO_INCREMENT`.
- Claves foráneas con `ON DELETE RESTRICT` o `SET NULL` según el caso.
- Fechas y horas en UTC en la base; la zona horaria de presentación se configura en la app.
- Desactivación lógica (`is_active` o `status = 'inactive'`) en lugar de borrado físico.
- Migraciones SQL versionadas en `database/migrations/`.
- El esquema inicial está en `database/schema.sql` y los datos demo en `database/seed.sql`.
- El SQL solo vive en repositorios y migraciones.

Detalle: [08-modelo-de-datos.md](08-modelo-de-datos.md).

## 9. Seguridad

- Todas las consultas usan PDO con parámetros vinculados.
- CSRF en todos los formularios de mutación.
- Sesiones con cookies `HttpOnly`, `Secure` en producción y `SameSite=Lax`.
- `session_regenerate_id(true)` al iniciar sesión y al cambiar privilegios.
- `password_hash()` y `password_verify()`; rehash si cambia el algoritmo.
- Escape de salida con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Autorización en servidor, no solo ocultando botones.
- Validación en servidor como autoridad final.
- Cabeceras de seguridad y HTTPS.
- Sin datos reales en desarrollo, seed, capturas ni repositorio.

Detalle: [07-seguridad-y-privacidad.md](07-seguridad-y-privacidad.md).

## 10. Testing

| Nivel | Qué cubre | Herramienta |
| ----- | --------- | ----------- |
| Unitario | Reglas de servicio, validadores y cálculos | PHPUnit |
| Integración | Repositorios y transacciones contra MySQL de prueba | PHPUnit + BD de prueba |
| Funcional | Flujos por rol, formularios y permisos | Pruebas manuales guiadas |
| E2E (opcional) | Recorrido completo en navegador | Herramienta externa si el tiempo lo permite |

Se prioriza cubrir reglas de negocio, permisos, validaciones y reportes. Cada bug
corregido debe incluir una prueba de regresión.

## 11. Reglas anti-espagueti

- No escribir SQL en controladores ni vistas.
- No imprimir datos sin escapar.
- No leer `$_POST`/`$_GET` dentro de servicios o repositorios; se pasan valores tipados.
- No usar `global`, `extract()` ni variables superglobales dentro de clases.
- No duplicar reglas entre JavaScript y PHP: el servidor siempre decide.
- No crear un servicio gigante para todo; un servicio por capacidad.
- No crear abstracciones hasta que exista repetición real.
- No hacer consultas dentro de bucles (N+1).
- No borrar registros con historial; usar desactivación.
- No añadir dependencias sin justificar mantenimiento y licencia.

## 12. Decisiones

Toda decisión que afecte a estructura, stack, seguridad o datos se registra en
[`docs/adr/`](adr/README.md). La propuesta anterior basada en Next.js quedó rechazada.
