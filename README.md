# Gestión RR. HH.

Aplicación web para centralizar la información básica del personal y facilitar tres
actividades de Gestión Humana: **gestión de empleados**, **asistencia y permisos** y
**reclutamiento y selección**.

> **Proyecto universitario.** Es un MVP académico con datos ficticios. No debe
> utilizarse con datos reales de empleados.
>
> **Estado:** Fundaciones, módulos 01 (Empleados), 02 (Asistencia y permisos),
> 03 (Reclutamiento y selección), Dashboard y separación de roles `admin`/`hr`
> implementados y verificados en local con PHP 8.4 y MariaDB 11.4. La interfaz se revisó
> en escritorio y el Dashboard también en móvil. El entorno permanente de desarrollo
> (XAMPP/Laragon) y el despliegue en InfinityFree están pendientes. La verificación se
> hizo con binarios portátiles.

## Módulos

| # | Módulo | Carpeta | Prioridad | Objetivo |
| - | ------ | ------- | --------- | -------- |
| 01 | Gestión de empleados | [`src/Modules/Employees`](src/Modules/Employees/README.md) | Base | Registrar, consultar y mantener la información laboral básica del personal. |
| 02 | Asistencia y permisos | [`src/Modules/Attendance`](src/Modules/Attendance/README.md) | Operativo | Registrar asistencia, tardanzas, ausencias y solicitudes de permisos. |
| 03 | Reclutamiento y selección | [`src/Modules/Recruitment`](src/Modules/Recruitment/README.md) | Complementario | Administrar vacantes, candidatos y procesos de selección. |

Módulos transversales: [`Auth`](src/Modules/Auth/README.md) (autenticación y usuarios)
y [`Dashboard`](src/Modules/Dashboard/README.md) (panel de inicio).

## Stack

| Área | Tecnología |
| ---- | ---------- |
| Frontend | HTML5, CSS3, Bootstrap 5 y JavaScript |
| Backend | PHP 8.x |
| Base de datos | MySQL 8 / MariaDB 11 |
| Acceso a datos | PDO con consultas preparadas |
| ORM | Ninguno (SQL explícito en repositorios) |
| Autenticación | Sesiones PHP + `password_hash()` |
| Autorización | Roles `admin` y `hr` definidos en PHP |
| Validación | PHP en servidor y JavaScript en el navegador |
| Testing | PHPUnit y pruebas funcionales HTTP |
| Hosting | InfinityFree con dominio propio y HTTPS |
| Control de versiones | Git y GitHub |

La justificación y las consecuencias del stack están en el
[ADR-0002](docs/adr/0002-stack-php-mysql.md).

## Alcance del MVP

Incluye:

- Inicio de sesión y roles básicos.
- Panel de navegación.
- Operaciones CRUD y desactivación lógica.
- Validaciones en servidor y cliente.
- Base de datos relacional MySQL.
- Reportes sencillos dentro de la aplicación, imprimibles desde el navegador.
- Diseño adaptable a móviles con Bootstrap 5.

No incluye:

- Nómina, impuestos o prestaciones.
- Reloj biométrico o marcación desde dispositivos.
- Aplicación móvil independiente.
- Portal de autoservicio para empleados o candidatos.
- Envío automático de correos.
- Firma digital o almacenamiento de documentos personales.
- Integraciones con sistemas externos.
- Exportación a Excel o PDF (opcional si el tiempo lo permite).

El detalle está en [docs/01-vision-y-alcance.md](docs/01-vision-y-alcance.md).

## Estructura del proyecto

```text
gestion-rrhh/
├── .github/                  # Plantilla de pull request
├── config/                   # Configuración de aplicación y base de datos
├── database/                 # Esquema, seed y migraciones SQL
├── docs/                     # Visión, arquitectura, módulos, seguridad y ADRs
├── public/                   # ÚNICO directorio web (docroot)
│   ├── assets/               # CSS y JavaScript
│   ├── index.php             # Front controller
│   └── .htaccess             # Front controller y seguridad
├── scripts/                  # Scripts CLI: creación de administrador
├── src/
│   ├── Core/                 # Router, Request, Database, Auth, CSRF, View...
│   ├── Modules/              # Módulos de negocio autocontenidos
│   │   ├── Auth/
│   │   ├── Dashboard/
│   │   ├── Employees/
│   │   ├── Attendance/
│   │   └── Recruitment/
│   └── Shared/               # Layouts, partials y helpers
├── storage/                  # Logs, fuera del docroot
├── tests/                    # PHPUnit y pruebas funcionales HTTP
├── exceso/                   # Material retirado del runtime (no se despliega)
├── .editorconfig
├── .env.example
├── .gitignore
├── .htaccess                 # Redirección a public/ para InfinityFree
└── README.md
```

La arquitectura y las reglas de dependencia están en
[docs/02-arquitectura.md](docs/02-arquitectura.md).

## Documentación

| Documento | Contenido |
| --------- | --------- |
| [01-visión y alcance](docs/01-vision-y-alcance.md) | Problema, usuarios, alcance, roles y métricas |
| [02-arquitectura](docs/02-arquitectura.md) | Capas, front controller, reglas y stack |
| [03-módulos](docs/03-modulos.md) | Fronteras, dependencias y orden de implementación |
| [04-convenciones](docs/04-convenciones.md) | PSR-12, naming, SQL, Git y Definition of Done |
| [05-roadmap](docs/05-roadmap.md) | Fases y entregables del proyecto |
| [06-deploy y dominio](docs/06-deploy-y-dominio.md) | XAMPP/Laragon, InfinityFree, dominio y backups |
| [07-seguridad y privacidad](docs/07-seguridad-y-privacidad.md) | Sesiones, CSRF, RBAC y datos ficticios |
| [08-modelo de datos](docs/08-modelo-de-datos.md) | Diagrama ER, tablas, estados y restricciones |
| [ADRs](docs/adr/README.md) | Decisiones de arquitectura |

## Principios de trabajo

1. **Modularidad:** cada módulo es dueño de sus controladores, servicios, repositorios y vistas.
2. **Separación de capas:** el controlador recibe HTTP, el servicio decide y el repositorio ejecuta SQL.
3. **SQL seguro:** PDO con consultas preparadas, sin concatenar entradas del usuario.
4. **Salida escapada:** todo dato dinámico se imprime con `htmlspecialchars`.
5. **Permisos en servidor:** ocultar un botón no es una medida de seguridad.
6. **Datos ficticios:** nunca usar información real en el repositorio, la base local o las demos.
7. **Cambios pequeños:** ramas cortas, commits claros y PRs revisables.
8. **Documentar decisiones:** los cambios importantes se registran en un ADR.

## Próximos pasos

- [x] Definir los tres módulos y su alcance.
- [x] Aprobar el stack PHP/MySQL.
- [x] Diseñar el esquema relacional y los estados.
- [x] Reorganizar la estructura para PHP.
- [x] Importar el esquema y el seed en una base local y validar el login.
- [x] Implementar el núcleo: front controller, router, configuración, PDO y vistas.
- [x] Implementar autenticación, sesiones, CSRF y roles.
- [x] Separar los permisos de `admin` y RR. HH.: usuarios y auditoría solo para admin.
- [x] Construir el módulo 01 (Empleados): empleados, departamentos y cargos.
- [x] Construir el módulo 02 (Asistencia y permisos): registros, permisos y reporte.
- [x] Construir el módulo 03 (Reclutamiento y selección): vacantes, candidatos,
  postulaciones y alta guiada.
- [x] Revisar la interfaz en escritorio y el Dashboard en móvil.
- [x] Limpiar el proyecto, retirar código y artefactos no usados y actualizar la
  documentación.
- [x] Ejecutar la regresión final: lint, PHPUnit y pruebas HTTP de los cuatro recorridos.
- [ ] Desplegar en InfinityFree con un subdominio temporal y probar el esquema en MySQL 8.
- [ ] Revisar en móvil los módulos 01, 02 y 03.
- [ ] Confirmar con el usuario los detalles pendientes de formato de documento, fecha de
  salida, reporte mensual y reporte de Reclutamiento.
- [x] Integrar los indicadores de los tres módulos en el Dashboard.
- [ ] Instalar PHP 8.2+ y MySQL/MariaDB de forma permanente en local (XAMPP, Laragon u otro).
- [ ] Comprar y conectar el dominio definitivo con HTTPS.
- [x] Inicializar Git y publicar la versión 1.0.0 en
  [KingDam47/gestion-rrhh](https://github.com/KingDam47/gestion-rrhh) (privado).

## Verificación del módulo 01

El 2026-10-26 se ejecutaron 165 comprobaciones HTTP contra el servidor local. Se
cubrieron altas, duplicados, coherencia cargo-departamento, CSRF, edición,
desactivación/reactivación, reglas de departamentos inactivos, reportes, filtros,
paginación, rol RR. HH. y auditoría. También pasaron 16 pruebas de PHPUnit (21
aserciones) en ese momento y la revisión de sintaxis de 75 archivos PHP. La base se
restauró con `database/seed.sql` al terminar. Queda pendiente la revisión en móvil y la
prueba en MySQL 8/InfinityFree.

## Verificación del módulo 02

El 2026-10-26 se ejecutaron 304 comprobaciones HTTP contra el servidor local. Se
cubrieron altas y correcciones de asistencia, registro único, fechas futuras, horas
incoherentes, ausencias, empleados inactivos, creación y revisión de permisos,
solapamientos, CSRF, roles, reportes por mes/departamento y auditoría sin datos
personales. También pasaron 23 pruebas PHPUnit (45 aserciones) en ese momento y la
revisión de sintaxis de 96 archivos PHP. La prueba funcional reutilizable está en
[`tests/Functional/AttendanceHttpCheck.php`](tests/Functional/AttendanceHttpCheck.php).
La base se restauró con `database/seed.sql` al terminar. Queda pendiente la revisión
en móvil y la prueba en MySQL 8/InfinityFree.

## Verificación del módulo 03

El 2026-10-26 se ejecutaron 535 comprobaciones HTTP contra el servidor local. Se
cubrieron acceso anónimo y por rol, CRUD de vacantes y candidatos, filtros, paginación,
cierre final de vacantes, correo de candidato único, XSS escapado, CSRF, postulaciones
duplicadas, transiciones válidas y finales, alta guiada con validaciones y transacción,
404 y auditoría sin datos personales. También pasaron 40 pruebas PHPUnit (111
aserciones) del proyecto y la revisión de sintaxis de 143 archivos PHP. La prueba funcional
reutilizable está en
[`tests/Functional/RecruitmentHttpCheck.php`](tests/Functional/RecruitmentHttpCheck.php).
La base se restauró con `database/seed.sql` al terminar. Queda pendiente la revisión
en móvil y la prueba en MySQL 8/InfinityFree.

## Verificación del Dashboard

El 2026-10-26 se ejecutaron 108 comprobaciones HTTP contra el servidor local con
[`tests/Functional/DashboardHttpCheck.php`](tests/Functional/DashboardHttpCheck.php).
Se cubrieron las líneas base de la base demo, acceso anónimo y por rol, saludo e insignia
de rol, cifras y detalles de las cuatro tarjetas y del bloque de reclutamiento, accesos
rápidos, enlaces operativos, 404 y lectura sin cambios en la base ni en la auditoría.
También pasaron 40 pruebas PHPUnit (111 aserciones) del proyecto y la revisión de sintaxis
de 143 archivos PHP. La interfaz se revisó a 1440×900 y 390×844 sin desbordamiento
horizontal, y el log no registró errores nuevos. Como regresión final también pasaron
304/304 comprobaciones de Asistencia y 535/535 de Reclutamiento. Queda pendiente la
prueba en MySQL 8/InfinityFree.

## Verificación de separación de roles

El 2026-10-26 se ejecutaron 183 comprobaciones HTTP contra el servidor local con
[`tests/Functional/RolesHttpCheck.php`](tests/Functional/RolesHttpCheck.php). Se
verificó que:

- RR. HH. conserva el acceso operativo a empleados, asistencia, permisos, vacantes,
  candidatos y postulaciones, pero recibe 403 en `/users*` y `/audit`;
- el menú de RR. HH. no muestra Usuarios ni Auditoría, y sí muestra Mi cuenta;
- el administrador crea, edita, desactiva, reactiva y restablece contraseñas;
- el último administrador activo no puede desactivarse ni degradarse;
- un administrador no puede desactivar su propia cuenta;
- un cambio de rol o una desactivación invalidan la sesión afectada;
- el cambio de contraseña propia funciona para ambos roles y exige la contraseña actual;
- CSRF, 404, paginación, filtros y metadata de auditoría limitada a `status` y `role`.

También pasaron 40 pruebas PHPUnit (111 aserciones) y la revisión de sintaxis de 143
archivos PHP. Como regresión se repitieron Dashboard (108/108), Asistencia (304/304) y
Reclutamiento (535/535). La base se restauró con `database/seed.sql` al terminar:
2 usuarios, 5 auditorías y 10 empleados. Queda pendiente la prueba en MySQL
8/InfinityFree.

> El dominio se compra aparte. InfinityFree no permite Node.js ni cambiar el
> document root, por lo que el proyecto se despliega con PHP y una regla de
> reescritura hacia `public/`.
