# Módulo transversal — Dashboard

> **Estado:** Implementado y verificado (2026-10-26) · **Carpeta:** `src/Modules/Dashboard`
> **Depende de:** Employees, Attendance, Recruitment, Auth

## 1. Objetivo

Mostrar un panel de inicio con indicadores agregados y accesos rápidos a los módulos
permitidos según el rol. Es una capa de composición, no un módulo de negocio.

## 2. Contenido implementado

### Indicadores

- Personal activo, inactivo y total.
- Asistencia del mes actual: total, registros de hoy, tardanzas y ausencias.
- Permisos: pendientes, aprobados, rechazados y total.
- Vacantes: abiertas, cerradas y total.
- Reclutamiento: postulaciones en curso, seleccionadas, pendientes de alta y candidatos.
- Desglose de postulaciones por estado: recibidas, en evaluación, entrevista,
  seleccionadas y rechazadas.

### Accesos rápidos

- Registrar empleado.
- Registrar asistencia.
- Crear solicitud de permiso.
- Revisar permisos pendientes.
- Crear vacante.
- Ver candidatos seleccionados.

## 3. Reglas

- No contiene SQL propio ni acceso directo a tablas o repositorios.
- `DashboardSummaryService` compone los conteos usando servicios y contratos públicos
  de los módulos.
- Es de solo lectura: no crea, modifica ni audita registros.
- Los indicadores respetan el rol y los permisos. Con los roles actuales (`admin` y
  `hr`), ambos ven los tres módulos y los mismos indicadores.
- Las tarjetas enlazan a listados, reportes o formularios ya existentes; no duplican
  pantallas de negocio.
- La fecha de corte y el mes se calculan en el servidor. El seed demo concentra la
  asistencia en octubre de 2026.
- El panel no muestra documentos, correos ni otros datos personales.

## 4. Criterios de aceptación

- [x] El panel carga después del login.
- [x] Los datos coinciden con los registros de cada módulo.
- [x] Cada rol ve los accesos permitidos.
- [x] Los enlaces llevan a las pantallas correctas.
- [x] La carga es razonable y no bloquea la navegación.
- [x] Funciona en móvil y escritorio.
- [x] El panel no escribe datos ni registra auditoría.

## 5. Estructura

```text
src/Modules/Dashboard/
├── Controllers/      # DashboardController
├── Services/         # DashboardSummaryService
├── Views/            # index.php
└── README.md
```

Las rutas `/` y `/dashboard` se declaran en `src/routes/web.php`. El panel no tiene
rutas propias de escritura ni formularios.

## 6. Dependencias

- `Employees` para los conteos de personal mediante `EmployeeLookup::stats()`.
- `Attendance` para el resumen de asistencia por período y el resumen de permisos.
- `Recruitment` para vacantes, candidatos y postulaciones.
- `Auth` para el usuario autenticado y su rol.

El servicio de resumen consume:

- `EmployeeLookup::stats()`;
- `AttendanceService::summary()`;
- `LeaveRequestService::summary()`;
- `VacancyService::stats()`;
- `CandidateService::stats()`;
- `ApplicationService::stats()`.

## 7. Verificación

El 2026-10-26 se ejecutaron **108 comprobaciones HTTP** con
`tests/Functional/DashboardHttpCheck.php`, todas correctas. Se cubrieron:

- líneas base de la base demo y acceso anónimo;
- login de `admin` y `hr`, saludo e insignia de rol;
- cifras y detalles de empleados, asistencia, permisos, vacantes y reclutamiento;
- accesos rápidos, destinos y todos los enlaces operativos del panel;
- rutas inexistentes y `POST /dashboard` con 404;
- lectura estable sin cambios en la base ni en `audit_logs`.

También pasaron 40 pruebas PHPUnit (111 aserciones) del proyecto y la revisión de
sintaxis de 143 archivos PHP. La interfaz se revisó a 1440×900 y 390×844 sin
desbordamiento horizontal, y el log no registró errores nuevos. Probado con PHP 8.4 y
MariaDB 11.4 en local y en el hosting; queda pendiente MySQL 8.

## 8. Decisiones

- El Dashboard no tiene reporte imprimible propio: reutiliza los listados y reportes de
  los módulos.
- No se agregó una ruta de detalle por indicador. Cada tarjeta abre el listado o reporte
  correspondiente con sus filtros.
- El indicador "Pendientes de alta" cuenta postulaciones `selected` sin
  `employee_id`; el acceso rápido "Ver candidatos seleccionados" abre el listado
  `selected`, que es el punto de partida del alta guiada.
- La asistencia del día se muestra aunque el seed no tenga registros de hoy; es un
  valor real de la base, no un dato simulado en la vista.
