# Módulos y fronteras

Un **módulo** es una capacidad de negocio autocontenida. Incluye sus controladores,
servicios, repositorios, soporte, vistas y rutas. Las pruebas viven en `tests/`. No es
una pantalla ni una tabla aislada.

## 1. Módulos definitivos

| # | Módulo | Carpeta | Prioridad | Depende de |
| - | ------ | ------- | --------- | ---------- |
| 01 | Gestión de empleados | `src/Modules/Employees` | Base | Auth/Core |
| 02 | Asistencia y permisos | `src/Modules/Attendance` | Operativo | Employees, Auth/Core |
| 03 | Reclutamiento y selección | `src/Modules/Recruitment` | Complementario | Employees, Auth/Core |

Transversales:

- `Auth`: login, logout, administración de usuarios y consulta de auditoría.
- `Dashboard`: panel de inicio con indicadores agregados, implementado como capa de
  composición.

## 2. Alcance resumido

### Módulo 01 — Gestión de empleados (implementado)

Registra y mantiene la información laboral básica. Administra empleados, departamentos
y cargos. Es la fuente de datos para los otros dos módulos.

Ficha: [Employees](../src/Modules/Employees/README.md).

### Módulo 02 — Asistencia y permisos (implementado)

Registra entradas, salidas, tardanzas y ausencias; gestiona solicitudes de permiso con
aprobación o rechazo y genera un reporte mensual. No calcula nómina ni descuentos.

Ficha: [Attendance](../src/Modules/Attendance/README.md). Verificado el 2026-10-26 con
304 comprobaciones HTTP, 23 pruebas PHPUnit y 96 archivos PHP sin errores de sintaxis.

### Módulo 03 — Reclutamiento y selección (implementado)

Administra vacantes, candidatos y postulaciones. Permite seguir el estado de cada
proceso y marcar candidatos seleccionados para una contratación posterior. No crea
empleados automáticamente: el alta guiada requiere confirmación de RR. HH.

Ficha: [Recruitment](../src/Modules/Recruitment/README.md). Verificado el 2026-10-26 con
535 comprobaciones HTTP, 40 pruebas PHPUnit del proyecto (111 aserciones) y 143 archivos
PHP sin errores de sintaxis.

### Dashboard — Panel de inicio (implementado)

Compone indicadores de empleados, asistencia, permisos, vacantes, candidatos y
postulaciones, y ofrece accesos rápidos a los flujos principales. No contiene SQL ni
repositorios propios: `DashboardSummaryService` consume contratos y servicios públicos de
los tres módulos. Es de solo lectura y respeta el rol del usuario.

Ficha: [Dashboard](../src/Modules/Dashboard/README.md). Verificado el 2026-10-26 con
108 comprobaciones HTTP, 40 pruebas PHPUnit del proyecto (111 aserciones) y 143 archivos
PHP sin errores de sintaxis. Revisado en escritorio y móvil.

## 3. Fronteras

### Propiedad de datos

- `Employees` es dueño de `employees`, `departments` y `positions`.
- `Attendance` es dueño de `attendance_records` y `leave_requests`.
- `Recruitment` es dueño de `vacancies`, `candidates` y `applications`.
- `Auth` es dueño de `users`.
- `Core` no es dueño de tablas de negocio.

Ningún módulo lee o escribe directamente las tablas de otro. Las claves foráneas
existen para integridad referencial, pero las consultas de negocio se limitan a las
tablas propias.

### Comunicación

- `Employees` expone un contrato de lectura (`EmployeeLookup`) con los datos mínimos
  que necesitan los demás módulos.
- `Attendance` y `Recruitment` dependen de ese contrato, no de `EmployeeRepository`.
- `Attendance` consume `EmployeeLookup` de `Employees`.
- `Recruitment` consume `EmployeeLookup` y el caso de uso `EmployeeOnboarding` de
  `Employees`; nunca accede a `EmployeeRepository` ni a las tablas de Employees.
- `Attendance` consume `UserLookup` de `Auth` para mostrar el nombre del revisor sin
  leer la tabla `users`.
- `Recruitment` usa un caso de uso público de `Employees` para la alta guiada de un
  candidato seleccionado. RR. HH. confirma los datos antes de crear el empleado.
- `Dashboard` consume servicios públicos de los tres módulos para obtener conteos
  mediante `DashboardSummaryService`; no accede a repositorios ni tablas.
- `Auth` aporta el usuario autenticado y los roles, administra las cuentas y expone la
  auditoría solo al administrador; no contiene lógica de negocio de empleados,
  asistencia ni reclutamiento.

### Reglas compartidas

- Un empleado inactivo conserva su historial, pero no admite nuevas operaciones de
  asistencia ni nuevas solicitudes de permiso.
- Un departamento o cargo con empleados asociados no se elimina; se desactiva.
- Un cargo debe pertenecer al departamento seleccionado.
- Una vacante cerrada no acepta postulaciones y no se reabre en el MVP.
- Una postulación no se duplica para el mismo candidato y vacante.
- Las transiciones de postulación son unidireccionales; `selected` y `rejected` son
  finales.
- El alta guiada solo se permite desde una postulación `selected` sin empleado asociado
  y se ejecuta en una transacción.
- Solo una solicitud pendiente puede aprobarse o rechazarse.

## 4. Orden de implementación

1. **Fundaciones:** front controller, router, configuración, PDO, layout, auth, CSRF.
   *(completado)*
2. **Empleados:** departamentos, cargos, empleados, búsqueda, filtros, desactivación y
   resumen. *(completado y verificado el 2026-10-26)*
3. **Asistencia y permisos:** registro diario, incidencias, solicitudes, revisión y
   reporte mensual. *(completado y verificado el 2026-10-26)*
4. **Reclutamiento:** vacantes, candidatos, postulaciones, estados y alta guiada.
   *(completado y verificado el 2026-10-26)*
5. **Dashboard y reportes:** integración de indicadores y navegación. *(Dashboard
   completado y verificado el 2026-10-26; reporte imprimible de Reclutamiento pendiente
   de decisión)*

No se implementan dos módulos de negocio en paralelo si eso obliga a duplicar reglas o
a compartir repositorios.

## 5. Definition of Ready

Un módulo puede pasar a implementación cuando:

- [ ] Su ficha está revisada y aprobada.
- [ ] Entidades, estados y relaciones están definidos.
- [ ] Roles y permisos están claros.
- [ ] Flujos y pantallas principales están acordados.
- [ ] Criterios de aceptación verificables.
- [ ] Dependencias con otros módulos resueltas.
- [ ] Migraciones y datos demo previstos.

## 6. Reglas de cambio

- Cambiar el alcance de un módulo requiere actualizar su ficha y este documento.
- Cambiar una tabla compartida requiere revisar las migraciones y los contratos.
- Cambiar un estado requiere actualizar las transiciones, las validaciones y las pruebas.
- Las decisiones estructurales se registran en un ADR.
