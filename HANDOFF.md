# Traspaso de sesión — Gestión RR. HH.

> **Fecha:** 29/10/2026
> **Sesión de OpenCode:** `ses_e76ab7c1effezSW5NDVJORsXS7`
> **Proyecto:** `C:\Users\KingD\OneDrive\Desktop\gestion-rrhh`
>
> Este archivo es temporal. Sirve para retomar el trabajo después de apagar el
> equipo. Actualizarlo al cerrar cada sesión.

## Cómo reanudar

- **Código de sesión activo:** `ses_e76ab7c1effezSW5NDVJORsXS7`
- **Título en el historial:** «Analisis del proyecto Gestion RRHH»
- **Sesión anterior (cerrada):** `ses_e8b3d5f59ffemGPaYOZaXrvB2w`,
  «Gestion RRHH - dominio y TLS v1.0.2»

Abrir PowerShell en `C:\Users\KingD` y ejecutar:

```powershell
opencode --session ses_e76ab7c1effezSW5NDVJORsXS7
```

Si el comando anterior no funciona, abrir OpenCode y usar el historial de
sesiones, o ejecutar:

```powershell
opencode --continue
```

Al entrar, pegar este mensaje:

```text
Analiza el proyecto de C:\Users\KingD\OneDrive\Desktop\gestion-rrhh.
Lee HANDOFF.md y el README. La aplicación está publicada en
https://evolvehr.cloud (etiqueta v1.0.2) y el backup ya está hecho.
El objetivo de esta sesión es revisar el proyecto a fondo, no desplegar.
```

## Estado actual

- Fase 0 completada: requisitos, arquitectura, modelo de datos, documentación y ADRs.
- Fase 1 (núcleo) completada: front controller, router, PDO, sesiones, login, logout,
  roles `admin`/`hr`, CSRF, layout Bootstrap 5, panel de inicio, 404/403 y logs.
- **Módulo 01 (Empleados) completado y verificado:** empleados, departamentos,
  cargos, búsqueda, filtros, paginación, ficha, edición, desactivación/reactivación,
  reporte imprimible y auditoría.
- **Módulo 02 (Asistencia y permisos) completado y verificado:** registros diarios,
  correcciones, tardanzas, ausencias, solicitudes, revisión, solapamientos, reporte
  mensual imprimible y auditoría.
- **Módulo 03 (Reclutamiento y selección) completado y verificado:** vacantes,
  candidatos, postulaciones, filtros, transiciones de estado, resumen y alta guiada
  transaccional de empleado.
- **Dashboard completado y verificado:** indicadores de personal, asistencia, permisos,
  vacantes, candidatos y postulaciones; accesos rápidos y enlaces operativos para
  `admin` y `hr`.
- **Separación de roles completada y verificada:** RR. HH. conserva toda la operación,
  pero no administra usuarios ni consulta la auditoría. El administrador gestiona
  cuentas, contraseñas y auditoría; el último administrador activo está protegido.
- La navegación muestra Empleados, Asistencia y Reclutamiento para ambos roles. Solo el
  administrador ve Usuarios y Auditoría; ambos ven Mi cuenta en el menú de usuario.
- **Limpieza final completada:** la plantilla histórica se conserva en `exceso/` fuera
  del runtime; se retiraron directorios vacíos, pruebas no usadas, caché, imágenes no
  usadas y APIs muertas. La documentación quedó alineada. `exceso/`, `vendor/`,
  `tests/`, `docs/`, `scripts/`, `.env` y la caché de PHPUnit no se despliegan.
- **Regresión final completada:** lint de 143 archivos PHP sin errores, PHPUnit con
  40 pruebas y 111 aserciones, y pruebas HTTP de Dashboard (108), Asistencia (304),
  Reclutamiento (535) y Roles (183), todas correctas.
- La base `gestion_rrhh` quedó restaurada con `database/seed.sql` al terminar la
  regresión: 2 usuarios activos, 10 empleados (8 activos), 5 departamentos, 7 cargos,
  15 registros de asistencia, 4 solicitudes de permiso, 3 vacantes (2 abiertas),
  5 candidatos, 6 postulaciones y 5 registros históricos de auditoría.
- **Versión 1.0.0 publicada:** repositorio privado en
  `https://github.com/KingDam47/gestion-rrhh`, rama `main` y etiqueta `v1.0.0`.
- **Versión 1.0.1 etiquetada:** incluye el despliegue en InfinityFree, el forzado de
  HTTPS y la documentación del hosting (`2e1f6d1`).
- No hay instalación permanente de PHP/MySQL; se usan binarios portátiles en
  `%TEMP%`.
- **Despliegue completado (2026-10-26):** la aplicación está publicada en
  `https://evolvehr.cloud` sobre InfinityFree (PHP 8.4.27, MariaDB 11.4.13), con el
  esquema y el seed importados y verificados. El `.env` de producción vive solo en el
  hosting.
- **Dominio propio (2026-10-29):** `evolvehr.cloud`, comprado en IONOS, quedó como
  dominio canónico. Los nameservers apuntan a `ns1/ns2.infinityfree.com`, el dominio
  está aparcado sobre `evolvehr.freehosting.dev` (misma `htdocs`) y el subdominio
  temporal responde con 301. Certificado Let's Encrypt (`CN=evolvehr.cloud`) activo
  desde el 29/10/2026 y válido hasta el 27/01/2027, con `evolvehr.cloud` y
  `*.evolvehr.cloud` en el SAN.

## Siguiente paso

### 0. Despliegue de la Fase 2 (31/10/2026)

- Se creó el backup `backup/pre-fase2-20261031` y la etiqueta
  `v1.0.2-prod-20261031` sobre el último commit estable antes de los cambios.
- Backup previo de producción: archivos completos de `htdocs` y dump SQL de la
  base, guardados fuera del repositorio. La restauración del dump se probó en una
  base temporal local (10 tablas, 3 usuarios, 11 empleados, 16 auditorías).
- Migración `202610292100_add_password_changed_at_to_users.sql` aplicada sobre la
  base de producción existente. No se reimportó el seed y no se borraron datos.
- Se subieron 14 archivos de runtime por FTP (13 cambios + `.gitkeep` del
  limitador). No se subieron `.env`, `database/`, `tests/`, `docs/`, `scripts/`,
  `vendor/` ni credenciales. El `.env` de producción quedó intacto.
- `storage/rate-limits/` se creó en producción y se confirmó que es escribible.
- Verificación en vivo: cabeceras de seguridad sin duplicados, HSTS, redirección
  HTTP→HTTPS y `www`→ápice, login de `admin` y `hr`, `hr` con 403 en `/users` y
  `/audit`, bloqueo del sexto intento de login y cambio de contraseña que invalida
  la otra sesión sin cerrar la sesión que hizo el cambio.
- El usuario temporal usado para la prueba se eliminó. La línea base quedó en
  3 usuarios, 11 empleados y 16 auditorías; las contraseñas demo no se cambiaron.
- El script temporal de migración/backup se eliminó del hosting y se verificó que
  su URL devuelve 404.

### 0.1 Revisión del proyecto (sesión del 29/10/2026)

- El despliegue y el dominio quedaron cerrados: `https://evolvehr.cloud` con TLS
  válido y `v1.0.2` publicada.
- El backup de base y archivos ya lo hizo el usuario desde el panel.
- La sesión activa se dedica a **analizar** el proyecto (arquitectura, módulos,
  deuda técnica), no a desplegar. Punto de partida: `README.md`, `docs/` y este
  archivo.
- Pendientes de producto que siguen abiertos: revisión en móvil de los módulos 01,
  02 y 03, prueba del esquema en MySQL 8 y confirmación de los detalles de formato
  de documento, fecha de salida y reportes.

### 1. Despliegue en InfinityFree (completado el 26/10/2026)

- Cuenta creada con el subdominio temporal `evolvehr.freehosting.dev`.
- PHP 8.4.27 (`apache2handler`), MariaDB 11.4.13, `pdo_mysql`, `mbstring` y `openssl`
  disponibles; `storage/logs` con permiso de escritura.
- Base `if0_43278720_rrhr` creada y cargada con `database/schema.sql` y
  `database/seed.sql`. Líneas base: 2 usuarios, 5 departamentos, 7 cargos,
  10 empleados, 15 asistencias, 4 permisos, 3 vacantes, 5 candidatos,
  6 postulaciones y 5 auditorías.
- 139 archivos subidos por FTP a `htdocs` (runtime + `.env` de producción), sin
  `vendor/`, `tests/`, `docs/`, `scripts/`, `exceso/`, `database/` ni READMEs.
- `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`,
  `APP_URL=https://evolvehr.cloud`, `APP_TIMEZONE=UTC`, `SESSION_SECURE=true`.
- `.htaccess` raíz: 301 canónico hacia `https://evolvehr.cloud`, redirección a
  `public/`, bloqueo de `src/`, `config/`, `database/`,
  `storage/`, `tests/`, `docs/`, `scripts/`, `exceso/`, `.env` y `.git`, y forzado de
  HTTPS (`RewriteCond %{HTTPS} !=on`). El hosting termina TLS y reporta `HTTPS=on`.
- La importación se hizo con un instalador PHP temporal protegido por token, que se
  eliminó junto con los `.sql`; el MySQL gratuito no admite conexiones externas.
- Prueba de humo en vivo: login de `admin` y `hr`, Dashboard, Empleados
  (listado, ficha y reporte), Departamentos, Cargos, Asistencia (listado y reporte),
  Permisos, Reclutamiento (vacantes, candidatos, postulaciones), Usuarios, Auditoría y
  Mi cuenta; `hr` recibe 403 en `/users` y `/audit`; CSRF inválido rechazado; 404
  personalizada; assets con 200; `.env`, `config/`, `src/`, `storage/` y `database/`
  bloqueados; UTF-8 correcto en el HTML.
- Escritura verificada en producción: cambio de contraseña de `hr` con ida y vuelta,
  regeneración de sesión y registro en auditoría. Después se reimportó el seed para
  dejar la demo en su línea base.

#### Cómo volver a desplegar

- Las credenciales FTP y MySQL solo están en el panel de InfinityFree; no se guardan en
  el equipo ni en el repositorio. Hay que volver a pedirlas o copiarlas del panel.
- Paquete a subir: `.htaccess` raíz + `public/`, `src/`, `config/` y `storage/`
  (sin READMEs). Son 138 archivos, unos 640 KB.
- Destino FTP: `ftpupload.net`, usuario `if0_43278720`, carpeta `htdocs`. El `.env` de
  producción ya vive en el hosting: no sobrescribirlo con el `.env` local.
- Un cambio solo de archivos no requiere tocar la base. Si cambia el esquema, se aplica
  desde phpMyAdmin o con un instalador PHP temporal por token que se borra al terminar
  (el MySQL gratuito no acepta conexiones externas).
- Comprobación rápida tras subir: `https://evolvehr.cloud/login`, login de
  `admin` y `hr`, `/users` con 403 para `hr` y un reporte.
- El primer acceso desde un navegador nuevo muestra el reto JavaScript (`aes.js`) del
  hosting; no es un error de la aplicación.
- InfinityFree intercepta antes que Apache las rutas sensibles: `src/`, `config/`,
  `storage/`, `database/`, `tests/`, `docs/`, `scripts/` y `exceso/` responden **302**
  hacia `https://errors.infinityfree.net/errors/403/`, y los archivos que empiezan por
  punto (`.env`, `.htaccess`, `.git/`) cortan la conexión. Nunca se sirve su contenido;
  el `[F,L]` del `.htaccess` queda como segunda barrera si se migra de hosting.
- `curl` de Windows no sirve para probar este hosting: aunque el certificado sea válido,
  la renegociación TLS del proxy de InfinityFree termina en `000` («server closed
  abruptly»). Para verificación estricta se usa un cliente .NET sobre `SslStream` con
  validación activada (`strict-check.ps1` en `%TEMP%`), o un navegador real.

### 2. Revisar los módulos 01, 02 y 03 con el usuario

- Mostrar el recorrido completo: login, empleados, departamentos, cargos,
  asistencia, permisos, reporte mensual, reclutamiento y alta guiada.
- Revisar la interfaz en escritorio (confirmada) y en móvil (pendiente para los módulos
  01, 02 y 03; el Dashboard ya se revisó).
- Confirmar decisiones pendientes:
  - formato y longitud del documento;
  - si se requiere fecha de salida/cese;
  - formato visual final del reporte mensual;
  - si Reclutamiento necesita un reporte imprimible específico.

### 3. Cerrar detalles no productivos

- Revisar en móvil los módulos 01, 02 y 03.
- Confirmar las decisiones pendientes: formato del documento, fecha de salida, formato
  final del reporte mensual y necesidad de un reporte imprimible de Reclutamiento.
- Revisar accesibilidad (teclado, contraste, etiquetas) y estilos de impresión.

### 4. Endurecimiento y entrega

- Revisar seguridad, estados vacíos y mensajes de error.
- Probar en MySQL 8 (local o en el hosting) y cerrar la cuenta temporal.
- Mantener el repositorio y etiquetar nuevas versiones cuando corresponda.
- Completar manual de usuario, guion de demo y backup final.
- Comprar y conectar el dominio definitivo con HTTPS.

## Entorno local

Los binarios son portátiles y viven en `%TEMP%`. Si Windows limpia la carpeta
temporal, habrá que descargarlos otra vez o instalar XAMPP/Laragon.

### MariaDB

```powershell
$mdb = "$env:TEMP\opencode\mariadb\mariadb-11.4.13-winx64"
& "$mdb\bin\mariadbd.exe" "--datadir=$mdb\data" "--port=3307" "--console"
```

Comprobar:

```powershell
& "$mdb\bin\mariadb-admin.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root ping
```

- Host: `127.0.0.1`
- Puerto: `3307`
- Usuario: `root`
- Contraseña: vacía
- Base: `gestion_rrhh`

Reimportar el seed (destructivo):

```powershell
$mdb = "$env:TEMP\opencode\mariadb\mariadb-11.4.13-winx64"
& "$mdb\bin\mariadb.exe" --no-defaults -h 127.0.0.1 -P 3307 -u root gestion_rrhh `
  -e "source C:/Users/KingD/OneDrive/Desktop/gestion-rrhh/database/seed.sql"
```

### Servidor PHP

Desde la carpeta del proyecto:

```powershell
& "$env:TEMP\opencode\php84\php.exe" -S 127.0.0.1:8099 -t public public/index.php
```

Abrir `http://127.0.0.1:8099`.

### Pruebas

```powershell
& "$env:TEMP\opencode\php84\php.exe" vendor\phpunit\phpunit\phpunit
```

Pruebas funcionales HTTP (restaurar el seed antes de cada una; las de escritura también
lo requieren al terminar):

```powershell
# Solo lectura; requiere el seed restaurado.
& "$env:TEMP\opencode\php84\php.exe" tests\Functional\DashboardHttpCheck.php

# Escritura: restaurar el seed antes y después.
& "$env:TEMP\opencode\php84\php.exe" tests\Functional\AttendanceHttpCheck.php
& "$env:TEMP\opencode\php84\php.exe" tests\Functional\RecruitmentHttpCheck.php
& "$env:TEMP\opencode\php84\php.exe" tests\Functional\RolesHttpCheck.php
```

Para revisar sintaxis de todos los archivos PHP:

```powershell
$php = "$env:TEMP\opencode\php84\php.exe"
$base = "C:\Users\KingD\OneDrive\Desktop\gestion-rrhh"
$files = Get-ChildItem -LiteralPath $base -Recurse -Filter *.php -File |
  Where-Object { $_.FullName -notmatch '\\vendor\\' }
$failed = 0
foreach ($file in $files) {
  & $php -l $file.FullName > $null 2>&1
  if ($LASTEXITCODE -ne 0) { $failed++; Write-Output "FAIL $($file.FullName)" }
}
Write-Output "PHP revisados: $($files.Count). Fallos: $failed"
```

### Credenciales demo

- Administrador: `admin@gestion-rrhh.test` / `password123`
- RR. HH.: `rrhh@gestion-rrhh.test` / `password123`

## Comprobaciones que ya pasaron

- **Regresión final tras la limpieza (2026-10-26):** PHP lint de 143 archivos sin
  errores, PHPUnit con 40 pruebas y 111 aserciones, y pruebas HTTP completas:
  Dashboard 108/108, Asistencia 304/304, Reclutamiento 535/535 y Roles 183/183.
  El seed se restauró antes y después de cada prueba de escritura.
- **Roles (2026-10-26):** 183 comprobaciones HTTP correctas sobre PHP 8.4 y
  MariaDB 11.4:
  - acceso anónimo a rutas administrativas y de cuenta;
  - RR. HH.: 403 en `/users*` y `/audit`, menú sin esos enlaces y operación completa;
  - administrador: listado, filtros, alta, edición, contraseña, estado y auditoría;
  - protección del último administrador y de la propia cuenta;
  - cambio de contraseña propia y cierre de sesión por cambio de rol/estado;
  - CSRF, 404 y metadata limitada a `status`/`role`.
- **Dashboard (2026-10-26):** 108 comprobaciones HTTP correctas sobre PHP 8.4 y
  MariaDB 11.4:
  - líneas base de la base demo y acceso anónimo;
  - login de `admin` y `hr`, saludo e insignia de rol;
  - cifras y detalles de empleados, asistencia, permisos, vacantes y reclutamiento;
  - accesos rápidos, destinos y todos los enlaces operativos del panel;
  - rutas inexistentes y `POST /dashboard` con 404;
  - lectura estable sin cambios en la base ni en `audit_logs`.
- **Módulo 03 (2026-10-26):** 535 comprobaciones HTTP correctas sobre PHP 8.4 y
  MariaDB 11.4:
  - acceso anónimo, login de `admin` y `hr`, navegación y resumen;
  - CRUD de vacantes, filtros, paginación y cierre final;
  - candidatos, correo único, edición posterior a la postulación y XSS escapado;
  - postulaciones, duplicados, vacante abierta, transiciones válidas y finales;
  - alta guiada con validaciones, transacción, vínculo con el empleado y alta única;
  - CSRF, 404, roles y auditoría con metadata limitada a `status`.
- **Módulo 02 (2026-10-26):** 304 comprobaciones HTTP correctas:
  - login de `admin` y `hr`, acceso anónimo, 404 y CSRF;
  - altas, correcciones, filtros, paginación y reporte;
  - registro único, fechas futuras, horas incoherentes y ausencias;
  - empleados inactivos: alta bloqueada, historial editable y revisión pendiente;
  - creación de permisos, solapamientos, revisión única y observaciones;
  - reporte por mes y departamento, XSS escapado y auditoría sin datos personales.
- **Módulo 01 (histórico):** 165 comprobaciones HTTP; 16 pruebas PHPUnit y 21
  aserciones en ese momento; auditoría con solo el estado.
- **PHPUnit:** 40 pruebas y 111 aserciones correctas (2026-10-26), incluidas
  `ApplicationStatusTest`, `RecruitmentDataTest`, `UserDataTest` y `AuditLabelTest`.
- **Sintaxis:** 143 archivos PHP sin errores, sin contar `vendor/`.
- Seed restaurado al terminar las pruebas: 2 usuarios activos, 10 empleados (8 activos),
  5 departamentos, 7 cargos, 15 asistencias, 4 permisos, 3 vacantes, 5 candidatos,
  6 postulaciones y 5 auditorías.
- `storage/logs/` quedó limpio (solo `.gitkeep`) y `.phpunit.cache/` se eliminó después
  de las pruebas. No quedan logs, cachés ni artefactos generados en el proyecto.

## Decisiones y notas

- `admin` conserva toda la operación y además administra cuentas y consulta la
  auditoría. `hr` conserva toda la operación, pero recibe 403 en `/users*` y `/audit` y
  no ve esos enlaces en el menú.
- Los usuarios solo se crean desde el módulo Auth; no hay autorregistro. Las cuentas se
  desactivan, nunca se eliminan.
- Un administrador no puede desactivar su propia cuenta. El último administrador activo
  no puede desactivarse ni degradarse; la comprobación se hace en una transacción con
  bloqueo de los administradores activos.
- Un cambio de rol o una desactivación invalidan la sesión afectada en la siguiente
  petición, con aviso en el login.
- Un cambio o restablecimiento de contraseña invalida las demás sesiones de la cuenta;
  la sesión que hizo el cambio se conserva. La marca se guarda en
  `users.password_changed_at`.
- El login se limita por correo (5 fallos) y por IP (20 fallos) en una ventana de 15
  minutos. El contador vive en `storage/rate-limits/` y falla abierto si no se puede
  escribir.
- Las respuestas dinámicas envían `Cache-Control: no-store, private`,
  `Permissions-Policy` y CSP con `frame-ancestors 'none'` y `object-src 'none'`.
  HSTS se envía solo bajo HTTPS.
- `ErrorHandler` activa `log_errors` y registra errores fatales en
  `storage/logs/php-error.log` y en el log de la aplicación.
- `EmployeeRepository::valueExists()` solo acepta `document` y `email`; cualquier otra
  columna lanza una excepción.
- Ambos roles cambian su propia contraseña en `/account/password`; se exige la
  contraseña actual y que la nueva sea distinta.
- La auditoría es solo para `admin`. Su metadata se limita a `status` y `role`, nunca
  nombres, correos, contraseñas ni hashes. El MVP no registra automáticamente cada
  login/logout ni los intentos fallidos.
- No hay borrado físico de empleados, departamentos ni cargos: solo activación y
  desactivación.
- El documento se normaliza sin espacios y en mayúsculas.
- El correo se guarda en minúsculas y es único cuando se informa.
- El cargo es opcional y debe pertenecer al departamento del empleado.
- Un cargo con empleados no puede cambiar de departamento.
- No se puede reactivar un empleado o cargo si su departamento está inactivo.
- La paginación de empleados usa 8 registros por página.
- Las acciones de estado se registran en `audit_logs` sin datos personales.
- `EmployeeLookup` es el contrato que consumen Attendance, Recruitment y Dashboard.
  Expone `find`, `findMany`, `stats`, `activeEmployees`, `directory`, `departments` y
  `positions`; no se eliminaron operaciones que la aplicación siga usando.
- `EmployeeOnboarding` es el caso de uso público de Employees que usa Recruitment para
  el alta guiada; no se accede a `EmployeeRepository` ni a tablas de Employees.
- El formulario de empleado crea siempre en estado `active`; el estado se cambia
  desde la ficha.
- Asistencia usa 10 registros por página.
- Reclutamiento usa 10 registros por página en vacantes, candidatos y postulaciones.
- Una vacante cerrada es final: no se reabre ni acepta nuevas postulaciones. Un
  candidato sí puede editarse después de postular y conserva sus postulaciones.
- Las transiciones de postulación son `received → screening|rejected`,
  `screening → interview|rejected`, `interview → selected|rejected`; `selected` y
  `rejected` son finales y no se reabren.
- La auditoría de Reclutamiento registra `vacancy.closed`, `application.created`,
  `application.status_changed` y `application.hired`; la metadata guarda solo `status`.
- El alta guiada solo se permite desde una postulación `selected` sin empleado asociado.
  Se ejecuta en una transacción, crea un empleado activo mediante `EmployeeOnboarding`
  y vincula `applications.employee_id`; si falla, revierte todo. No se dispara
  automáticamente al marcar `selected`.
- El historial de estados no tiene pantalla propia: queda en `audit_logs`.
- Solo se registran altas de asistencia para empleados activos. Un empleado inactivo
  conserva su historial y sus registros existentes pueden corregirse.
- Un registro por empleado y fecha; sin fechas futuras. `present`/`late` requieren
  entrada y salida opcional no anterior; `absent` no lleva horas y exige observación.
- Al editar una asistencia no se cambia el empleado. `AttendanceService::update` envía
  solo `work_date`, `check_in`, `check_out`, `status`, `notes` y `updated_by`; enviar
  `employee_id` a ese `UPDATE` provoca `HY093`.
- Los permisos nacen en `pending`; solo los pendientes se aprueban o rechazan y no se
  reabren. Se bloquean solapamientos con solicitudes `pending` o `approved`.
- El reporte mensual cuenta los permisos por `start_date` dentro del mes y usa el
  directorio completo de empleados; el filtro por departamento es opcional.
- La auditoría de asistencia y permisos guarda solo `status` como metadata.
- La coherencia de `leave_requests` se valida en el servicio, no con `CHECK`. No
  reintroducir `CHECK chk_leave_requests_review` en MariaDB: falla con `ERROR 1901`.
- El Dashboard es una capa de composición de solo lectura. `DashboardSummaryService`
  usa `EmployeeLookup::stats()`, `AttendanceService::summary()`,
  `LeaveRequestService::summary()`, `VacancyService::stats()`,
  `CandidateService::stats()` y `ApplicationService::stats()`; no tiene SQL ni
  repositorios propios.
- Ambos roles (`admin` y `hr`) ven los mismos indicadores del Dashboard y los accesos a
  los tres módulos operativos. Los accesos de administración (Usuarios y Auditoría) son
  exclusivos de `admin`.
- La asistencia del mes y del día se calculan con la fecha del servidor. El seed demo
  concentra los registros en octubre de 2026; por eso la tarjeta puede mostrar 0 hoy.
- "Pendientes de alta" cuenta postulaciones `selected` sin `employee_id`. El acceso
  rápido "Ver candidatos seleccionados" abre el listado `selected`, punto de partida
  del alta guiada.
- Por decisión del usuario (2026-10-26), la demo publicada conserva las contraseñas
  `password123` del seed. Son datos ficticios y la URL no se comparte fuera del círculo
  de la demo; si eso cambia, rotar las dos cuentas desde Mi cuenta o con el
  restablecimiento del administrador.

## Pendiente fuera del módulo

- El hosting gratuito no ofrece MySQL 8: se validó con MariaDB 11.4.13 en local y en
  InfinityFree. Si se migra a MySQL 8, repetir la regresión.
- InfinityFree aplica un reto JavaScript (`aes.js`) en la primera visita y no acepta
  conexiones MySQL externas; la importación debe hacerse desde phpMyAdmin o con un
  instalador PHP temporal, que hay que borrar al terminar.
- Las contraseñas demo siguen siendo `password123` en el sitio publicado. Cambiarlas
  antes de compartir la URL fuera del círculo de la demo.
- Instalar XAMPP o Laragon de forma permanente.
- Revisar en móvil los módulos 01, 02 y 03.
- Revisar accesibilidad (teclado, contraste, etiquetas) y estilos de impresión.
- Confirmar formato de documento, fecha de salida, reporte mensual y reporte de
  Reclutamiento.
- Decidir si el repositorio debe pasar de privado a público.
- Comprar y conectar el dominio definitivo con HTTPS.
- Completar manual de usuario, guion de demo y backup final.
