# Módulo 02 — Asistencia y permisos

> **Estado:** Implementado y verificado (2026-10-26) · **Prioridad:** Operativo ·
> **Carpeta:** `src/Modules/Attendance`
> **Depende de:** Employees, Auth/Core · **Es dependencia de:** Dashboard

## Estado de implementación

Implementado en `src/Modules/Attendance`:

- Registros de asistencia: listado con filtros por empleado, fechas y estado,
  paginación de 10 registros, alta y corrección.
- Reglas de asistencia: un registro por empleado y fecha, sin fechas futuras,
  entrada obligatoria para `present`/`late`, salida opcional no anterior y ausencia
  sin horas con observación obligatoria.
- Permisos: listado con filtros, creación en estado `pending` y revisión única a
  `approved` o `rejected`, guardando revisor, fecha y observación.
- Regla de solapamiento: se bloquean períodos que coincidan con solicitudes
  `pending` o `approved` del mismo empleado; una solicitud `rejected` no bloquea.
- Reporte mensual imprimible, con mes actual por defecto y filtro opcional por
  departamento. Agrega asistencias y permisos por empleado.
- Historial conservado: un empleado inactivo mantiene sus registros, puede tener
  registros existentes corregidos y sus solicitudes pendientes pueden revisarse.
- Contratos públicos: `EmployeeLookup` para empleados y `UserLookup` para el nombre
  del revisor. El módulo no consulta tablas de otros módulos.
- Auditoría: `attendance.created`, `attendance.updated`, `leave.approved` y
  `leave.rejected`, guardando solo el estado.

Verificado el 2026-10-26 con PHP 8.4 y MariaDB 11.4: 304 comprobaciones HTTP, 23
pruebas PHPUnit (45 aserciones) y 96 archivos PHP sin errores de sintaxis. La prueba
funcional reutilizable está en `tests/Functional/AttendanceHttpCheck.php`. Pendiente:
revisión visual responsive y prueba en MySQL 8.

## 1. Objetivo

Registrar las entradas y salidas del personal, identificar tardanzas y ausencias, y
gestionar solicitudes de permisos mediante un flujo básico de revisión y aprobación.

## 2. Problema que resuelve

Llevar la asistencia manualmente dificulta consultar los registros por empleado y fecha,
detectar incidencias y conocer el estado de las solicitudes. El módulo centraliza el
historial y permite obtener resúmenes administrativos por período.

## 3. Usuarios y permisos

| Rol | Permisos |
| --- | -------- |
| Administrador | Consultar y gestionar todos los registros, con acceso administrativo. |
| Personal de RR. HH. | Registrar asistencia, corregir registros autorizados, gestionar permisos y aprobar/rechazar solicitudes. |
| Empleado | Sin cuenta ni autoservicio en el MVP. |

Los registros son introducidos por personal autorizado. No se desarrolla marcación desde
teléfonos ni reloj virtual por empleado. Ambos roles con cuenta acceden a las mismas
pantallas del módulo; la restricción se aplica en servidor con `auth`, `active_user`,
`role:admin,hr` y `csrf`.

## 4. Alcance del MVP

Incluye:

- Registrar hora de entrada y salida.
- Consultar asistencia por empleado, rango de fechas y estado.
- Registrar incidencias de tardanza y ausencia.
- Crear solicitudes de permiso en nombre de los empleados.
- Aprobar o rechazar solicitudes pendientes.
- Registrar motivo, fechas, observaciones y responsable de la revisión.
- Consultar un resumen de asistencia y permisos por mes.
- Imprimir el reporte desde el navegador.

No incluye:

- Integración con dispositivos biométricos.
- Geolocalización.
- Control automático de horas extras.
- Cálculo de descuentos salariales.
- Turnos rotativos complejos.
- Cálculo legal de vacaciones o días disponibles.
- Autoservicio del empleado.
- Nómina.

## 5. Entidades

| Entidad | Información principal | Notas |
| ------- | --------------------- | ----- |
| `attendance_records` | Empleado, fecha, entrada, salida, estado y observación. | Único por empleado y fecha. |
| `leave_requests` | Empleado, tipo, fechas, motivo, estado y revisión. | Flujo pendiente → aprobada/rechazada. |
| `employees` | Empleado asociado. | Se consulta mediante `EmployeeLookup`. |
| `users` | Usuario que registra o revisa. | Se consulta mediante `UserLookup`. |

Relaciones:

- Un empleado tiene muchos registros de asistencia.
- Un empleado tiene muchas solicitudes de permiso.
- Cada registro identifica al usuario que lo creó o modificó.
- Cada solicitud identifica al usuario que la revisó y la fecha de revisión.

## 6. Flujos y pantallas

1. **Registro de asistencia:** seleccionar un empleado activo, fecha, estado y horas.
   La fecha no puede ser futura y solo se admite un registro por empleado y día.
2. **Consulta de asistencia:** filtrar por empleado, rango de fechas y estado; muestra
   totales de presentes, tardanzas y ausencias según el filtro.
3. **Corrección:** el empleado no cambia; se pueden corregir fecha, horas, estado y
   observación, incluso si el empleado fue desactivado después.
4. **Crear solicitud de permiso:** empleado activo, tipo, período y motivo. Nace en
   estado `pending`.
5. **Revisar solicitud:** aprobar o rechazar una solicitud pendiente con observación
   opcional. La decisión es única y no se reabre en el MVP.
6. **Reporte mensual:** totales de asistencia y permisos, resumen por empleado,
   listado de solicitudes del mes e impresión desde el navegador.

Rutas principales:

- `GET /attendance`, `GET /attendance/create`, `POST /attendance`;
- `GET /attendance/{id}/edit`, `POST /attendance/{id}`;
- `GET /attendance/report`;
- `GET /leave-requests`, `GET /leave-requests/create`, `POST /leave-requests`;
- `GET /leave-requests/{id}`, `POST /leave-requests/{id}/review`.

## 7. Reglas de negocio

- Todo registro debe estar asociado a un empleado existente.
- Solo se admiten altas de asistencia y solicitudes nuevas para empleados activos.
- Un empleado inactivo conserva su historial. Sus registros existentes pueden
  corregirse y sus solicitudes pendientes pueden revisarse.
- No debe existir más de un registro diario por empleado; se aplica también con el
  índice único `(employee_id, work_date)`.
- No se aceptan fechas futuras en asistencia.
- `present` y `late` requieren hora de entrada. La salida es opcional, pero no puede
  ser anterior a la entrada.
- `absent` no lleva horas y requiere una observación con el motivo.
- La fecha de una solicitud debe ser válida y la fecha final no puede ser anterior a
  la inicial.
- Una solicitud nueva nace en estado `pending`.
- Solo una solicitud pendiente puede pasar a `approved` o `rejected`.
- Toda aprobación o rechazo registra al usuario responsable y la fecha de revisión.
- Una solicitud resuelta no se modifica silenciosamente; en el MVP no se reabre.
- No se permiten solicitudes con períodos superpuestos a otras solicitudes
  `pending` o `approved` del mismo empleado. Una solicitud `rejected` no bloquea.
- Los tipos de permiso disponibles son `vacation`, `personal`, `sick` y `other`.
- Los registros históricos se conservan; las correcciones quedan identificadas con
  `updated_by`.
- Las tardanzas y ausencias se registran manualmente; no se calculan con horarios
  configurables.
- No se calculan descuentos ni horas extras.
- La auditoría guarda solo el estado resultante, sin nombres, documentos, correos ni
  otros datos personales.

## 8. Dependencias

- **Employees:** proporciona `EmployeeLookup` para validar existencia y estado,
  resolver nombres y departamentos y construir el directorio del reporte.
- **Auth/Core:** sesión, roles, CSRF, usuario que registra/revisa y `UserLookup` para
  mostrar el nombre del revisor.
- **Dashboard:** consulta pendientes y conteos del mes mediante
  `AttendanceService::summary()` y `LeaveRequestService::summary()`.
- **Recruitment:** no participa en este módulo.

### Contratos públicos consumidos

`src/Modules/Employees/Contracts/EmployeeLookup.php`:

- `find(int $employeeId)`: empleado exista o no activo;
- `findMany(array $employeeIds)`: empleados indexados por ID para listados;
- `stats()`: conteos de activos, inactivos y totales para el Dashboard;
- `activeEmployees()`: opciones para altas;
- `directory()`: empleados activos e inactivos para filtros y reportes;
- `departments()`: departamentos completos para filtros y reportes;
- `positions(?int $departmentId)`: cargos completos para filtros y formularios.

`src/Modules/Auth/Contracts/UserLookup.php`:

- `names(array $userIds)`: nombres indexados por ID.

Attendance **no** accede a `EmployeeRepository`, `UserRepository` ni a las tablas
`employees`, `departments`, `positions` o `users`.

## 9. Criterios de aceptación

- [x] Se registra entrada y salida de un empleado.
- [x] Se rechaza un segundo registro diario para el mismo empleado.
- [x] Se rechaza una salida anterior a la entrada.
- [x] Se consulta la asistencia por fechas, empleado y estado.
- [x] Se registra una tardanza o ausencia manual.
- [x] La ausencia exige observación y no admite horas.
- [x] Se bloquean fechas futuras y altas para empleados inactivos.
- [x] Se crea una solicitud en estado pendiente.
- [x] Se aprueba o rechaza una solicitud pendiente.
- [x] Se impiden transiciones de estado inválidas y la reapertura.
- [x] Se bloquean solapamientos con solicitudes pendientes o aprobadas.
- [x] El historial muestra el resultado y quién revisó.
- [x] El reporte mensual coincide con los registros almacenados.
- [x] Un usuario sin sesión no accede a las operaciones restringidas.
- [x] La edición de asistencia conserva el empleado original.
- [ ] La interfaz es usable en móvil y escritorio. *(Bootstrap 5 implementado;
  revisión visual final pendiente).*

## 10. Reportes

Reporte mensual con:

- total de registros de asistencia, presentes, tardanzas y ausencias;
- solicitudes del mes por estado (`pending`, `approved`, `rejected`);
- resumen por empleado con departamento y documento;
- listado de solicitudes cuyo `start_date` cae dentro del mes;
- filtros por mes y, opcionalmente, departamento;
- impresión desde el navegador con estilos `@media print`.

El mes actual es el valor por defecto. Los filtros de departamento usan el directorio
completo de empleados, incluidos los inactivos con historial. Los reportes son resúmenes
administrativos, no liquidaciones de nómina ni determinaciones legales.

## 11. Datos e integraciones

- Sin integraciones externas.
- Fechas y horas administradas desde la aplicación.
- Datos ficticios para la demo.
- No se almacenan justificantes médicos ni documentos personales.

## 12. Estructura de implementación

```text
src/Modules/Attendance/
├── Controllers/      # AttendanceController, LeaveRequestController, ReportController
├── Repositories/     # AttendanceRepository, LeaveRequestRepository
├── Services/         # AttendanceService, LeaveRequestService, MonthlyReportService
├── Support/          # AttendanceData: normalización de fechas, horas y meses
├── Views/            # Registros, permisos, revisión y reporte
├── routes.php
└── README.md
```

## 13. Preguntas abiertas

- [x] Los tipos de permiso son vacaciones, permiso personal, incapacidad y otro.
- [x] Se bloquean solicitudes superpuestas con pendientes o aprobadas.
- [x] Una solicitud aprobada no se corrige ni se anula en el MVP.
- [x] El reporte mensual incluye totales, resumen por empleado y solicitudes.
- [ ] Confirmar con el usuario el formato visual final del reporte y la revisión
  responsive antes de la entrega.

## 14. Verificación

- **2026-10-26:** 304 comprobaciones HTTP sobre PHP 8.4 y MariaDB 11.4.
  - Altas, correcciones, filtros, paginación y reporte.
  - Registro único, fechas futuras, horas incoherentes y ausencias.
  - Empleados inactivos: alta bloqueada, historial editable y revisión pendiente.
  - Solicitudes: creación, solapamientos, revisión única y observaciones.
  - CSRF, acceso anónimo, 404 y rol `hr`.
  - Auditoría con solo el estado, sin datos personales.
  - La prueba detectó y corrigió un `HY093` al actualizar: el servicio enviaba
    `employee_id` a un `UPDATE` que no lo utiliza.
- **PHPUnit:** 23 pruebas, 45 aserciones correctas.
- **Sintaxis:** 96 archivos PHP sin errores.
- **Datos:** `database/seed.sql` restaurado al terminar las pruebas.
- **Prueba reutilizable:** `tests/Functional/AttendanceHttpCheck.php`.
- **Pendiente:** revisión visual responsive y MySQL 8.
