# tests — Pruebas

Pruebas automatizadas con PHPUnit y pruebas funcionales HTTP/manuales guiadas.

## Estructura

```text
tests/
├── Unit/          # Servicios, validadores y reglas puras
└── Functional/    # Recorridos HTTP completos contra un servidor local
```

## Niveles

| Nivel | Qué cubre |
| ----- | --------- |
| Unitario | Reglas de negocio, validaciones, estados y cálculos |
| Funcional | Login, permisos, formularios y reportes por rol |
| Manual | Recorrido completo antes de la entrega |

## Reglas

- Datos ficticios y deterministas; nunca datos reales.
- Una base de prueba separada de la de desarrollo.
- Limpiar el estado entre pruebas.
- No depender del orden de ejecución.
- Cubrir caminos permitidos y denegados.
- Cada bug corregido añade una prueba de regresión.
- No usar `sleep()` ni dependencias de red en PHPUnit.
- Las pruebas funcionales HTTP sí requieren el servidor local y la base demo.
- Las pruebas no se despliegan a producción.

## Comandos

```bash
composer install
composer test
# o
vendor/bin/phpunit
```

En Windows, si no se usa Composer, puede ejecutarse directamente:

```powershell
& "$env:TEMP\opencode\php84\php.exe" vendor\phpunit\phpunit\phpunit
```

### Pruebas funcionales HTTP

`tests/Functional/DashboardHttpCheck.php` verifica el panel; es de **solo lectura**.
`tests/Functional/AttendanceHttpCheck.php`, `tests/Functional/RecruitmentHttpCheck.php`
y `tests/Functional/RolesHttpCheck.php` recorren los módulos 02 y 03 y la separación de
roles contra un servidor local y crean o modifican registros. **La base debe restaurarse
con `database/seed.sql` antes de cada prueba y después de las de escritura.** No se deben
ejecutar las pruebas de escritura una detrás de otra sin restaurar la semilla entre
ambas.

Requisitos:

- Servidor PHP en `http://127.0.0.1:8099`.
- MariaDB/MySQL accesible con la base `gestion_rrhh`.
- Extensiones `allow_url_fopen`, `pdo_mysql`, `dom` y `mbstring`.
- Para Dashboard y Asistencia, fecha del sistema en **octubre de 2026** (período de los
  datos demo).
- Reclutamiento usa la fecha actual para el cierre de vacante y el alta guiada; no
  depende de un mes concreto.
- Roles usa las dos cuentas demo y no depende de un mes concreto.

```powershell
$mdb = "$env:TEMP\opencode\mariadb\mariadb-11.4.13-winx64"
& "$mdb\bin\mariadb.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root gestion_rrhh `
  -e "source C:/Users/KingD/OneDrive/Desktop/gestion-rrhh/database/seed.sql"

& "$env:TEMP\opencode\php84\php.exe" tests\Functional\DashboardHttpCheck.php

# Restaurar otra vez antes de Asistencia.
& "$mdb\bin\mariadb.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root gestion_rrhh `
  -e "source C:/Users/KingD/OneDrive/Desktop/gestion-rrhh/database/seed.sql"

& "$env:TEMP\opencode\php84\php.exe" tests\Functional\AttendanceHttpCheck.php

# Restaurar otra vez antes de Reclutamiento.
& "$mdb\bin\mariadb.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root gestion_rrhh `
  -e "source C:/Users/KingD/OneDrive/Desktop/gestion-rrhh/database/seed.sql"

& "$env:TEMP\opencode\php84\php.exe" tests\Functional\RecruitmentHttpCheck.php

# Restaurar otra vez antes de Roles.
& "$mdb\bin\mariadb.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root gestion_rrhh `
  -e "source C:/Users/KingD/OneDrive/Desktop/gestion-rrhh/database/seed.sql"

& "$env:TEMP\opencode\php84\php.exe" tests\Functional\RolesHttpCheck.php

# Restaurar otra vez al terminar.
```

Se pueden cambiar los valores con las variables de entorno `APP_URL`, `DB_DSN`,
`DB_USER` y `DB_PASSWORD`. Los guiones abortan si la base no está en el estado inicial
del seed para evitar resultados falsos.

La prueba del Dashboard ejecuta **108 comprobaciones** y cubre:

- líneas base de la base demo y acceso anónimo;
- login de `admin` y `hr`, saludo e insignia de rol;
- indicadores de empleados, asistencia, permisos, vacantes, candidatos y postulaciones;
- accesos rápidos, destinos y enlaces operativos del panel;
- 404 y lectura sin cambios en la base ni en `audit_logs`.

La prueba de Asistencia devuelve código 0 si todas las comprobaciones pasan. Al
2026-10-26 ejecuta **304 comprobaciones** y cubre:

- acceso anónimo, login de `admin` y `hr`, y cierre de operaciones;
- altas y correcciones de asistencia, registro único y fechas futuras;
- horas incoherentes, ausencias sin horas y observación obligatoria;
- empleados inactivos: alta bloqueada, historial editable y revisión pendiente;
- solicitudes de permiso, solapamientos y revisión única;
- filtros, paginación y reportes por mes/departamento;
- CSRF, 404, XSS escapado y auditoría sin datos personales.

La prueba de Reclutamiento ejecuta **535 comprobaciones** y cubre:

- acceso anónimo, login de `admin` y `hr`, navegación y resumen del módulo;
- CRUD de vacantes, filtros, paginación, cierre final y formulario de candidatos;
- correo de candidato único, validaciones y XSS escapado en observaciones;
- postulaciones: duplicados, vacante abierta, filtros y transiciones válidas/finales;
- alta guiada: validaciones, transacción, vínculo con el empleado y alta única;
- CSRF, 404, roles y auditoría con metadata limitada a `status`.

La prueba de roles ejecuta **183 comprobaciones** y cubre:

- acceso anónimo a todas las rutas administrativas y de cuenta;
- RR. HH.: 403 en `/users*` y `/audit`, sin enlaces en el menú, pero con acceso completo
  a los tres módulos operativos y a `/account/password`;
- administrador: listado, búsqueda, filtros, alta, edición, restablecimiento de contraseña,
  desactivación y reactivación de cuentas;
- protección del último administrador activo y de la propia cuenta;
- cambio de contraseña propia, contraseña actual incorrecta y confirmación distinta;
- cierre de sesión tras cambio de rol o desactivación, y aviso en el login;
- CSRF, 404, paginación de usuarios y auditoría con metadata limitada a `status`/`role`.

La prueba de seguridad de la fase 2 ejecuta **29 comprobaciones** y cubre:

- `Cache-Control: no-store, private`, `Permissions-Policy`, CSP con
  `frame-ancestors 'none'`/`object-src 'none'` y HSTS bajo HTTPS;
- bloqueo del login tras 5 fallos del mismo correo y independencia de otros correos
  desde la misma IP;
- invalidación de la segunda sesión tras cambiar la contraseña, conservación de la
  sesión que hizo el cambio y restauración de la contraseña demo.

> La prueba de seguridad se niega a ejecutarse si `APP_URL` o `DB_DSN` no apuntan a
> `127.0.0.1`/`localhost` con `dbname=gestion_rrhh`, para evitar cambios accidentales
> en producción.

## Flujos funcionales mínimos

- Login, logout y acceso denegado.
- Navegación según rol.
- Empleado: alta, búsqueda, edición, desactivación y reactivación.
- Asistencia: registro único, validación de horas, solicitud y revisión.
- Reclutamiento: vacante, postulación duplicada, transición de estado, cierre y alta
  guiada.
- Reportes: coherencia con la base de datos.

## Estado actual

- **PHPUnit:** 47 pruebas, 128 aserciones correctas (2026-10-29).
- **Funcional Dashboard:** 108/108 comprobaciones correctas (2026-10-26).
- **Funcional Asistencia:** 304/304 comprobaciones correctas (2026-10-26).
- **Funcional Reclutamiento:** 535/535 comprobaciones correctas (2026-10-26).
- **Funcional Roles:** 183/183 comprobaciones correctas (2026-10-29).
- **Funcional Seguridad fase 2:** 29/29 comprobaciones correctas (2026-10-29).
- **Sintaxis:** 146 archivos PHP sin errores, sin contar `vendor/`.
- **Datos:** seed restaurado al terminar las pruebas (2 usuarios, 10 empleados,
  3 vacantes, 5 candidatos, 6 postulaciones, 5 auditorías).
