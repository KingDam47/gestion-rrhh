# Módulo 01 — Gestión de empleados

> **Estado:** Implementado y verificado (2026-10-26) · **Prioridad:** Base ·
> **Carpeta:** `src/Modules/Employees`
> **Depende de:** Auth/Core · **Es dependencia de:** Attendance, Recruitment, Dashboard

## Estado de implementación

Implementado en `src/Modules/Employees`:

- Empleados: listado con búsqueda y filtros, paginación de 8 registros, alta, ficha,
  edición, desactivación y reactivación lógica.
- Departamentos y cargos: listado, alta, edición y activación/desactivación.
- Validaciones: documento y correo únicos, normalización de datos, coherencia
  cargo-departamento y bloqueo de altas en departamentos inactivos.
- Reporte imprimible de personal y resumen por departamento.
- Contrato público `EmployeeLookup` para Attendance y Recruitment.
- Auditoría de cambios de estado en `audit_logs` sin datos personales.
- Navegación y panel de inicio conectados al módulo.

Verificado el 2026-10-26 con PHP 8.4 y MariaDB 11.4. Pendiente: revisión visual
responsive, prueba en MySQL 8 y despliegue en InfinityFree.

## 1. Objetivo

Registrar, consultar y mantener organizada la información laboral básica de los
empleados de la organización. Es el módulo central del sistema y la fuente de datos
para asistencia y reclutamiento.

## 2. Problema que resuelve

Los datos del personal pueden estar dispersos en documentos, hojas de cálculo o
archivos independientes. Esto dificulta localizar información, mantenerla actualizada
y consultar la relación de empleados por departamento. El módulo centraliza esos
registros y sirve de base a los demás procesos.

## 3. Usuarios y permisos

| Rol | Permisos |
| --- | -------- |
| Administrador | Acceso completo. Puede crear usuarios y asignar roles desde Auth. |
| Personal de RR. HH. | Registrar, consultar, editar, desactivar y reactivar empleados; administrar departamentos y cargos. No puede crear usuarios ni asignar roles administrativos. |
| Empleado | Sin cuenta ni acceso directo en el MVP. |

## 4. Alcance del MVP

Incluye:

- Registrar empleados con datos personales y laborales básicos.
- Consultar el listado y la ficha individual.
- Editar los datos autorizados.
- Buscar por nombre, documento, departamento o estado.
- Administrar departamentos y cargos.
- Activar o desactivar empleados.
- Filtrar por estado laboral y consultar un resumen del personal.
- Reporte sencillo del personal por estado y departamento, imprimible.

No incluye:

- Nómina o salarios.
- Contratos digitales o firma electrónica.
- Evaluaciones de desempeño.
- Historial documental avanzado.
- Portal de autoservicio para empleados.
- Fotografías, copias de documentos de identidad o archivos personales.
- Integraciones externas.

## 5. Entidades

| Entidad | Información principal | Notas |
| ------- | --------------------- | ----- |
| `employees` | Documento, nombres, apellidos, correo, teléfono, fecha de ingreso, departamento, cargo y estado. | El documento es único. |
| `departments` | Nombre, descripción y estado. | No se borra si tiene empleados o cargos. |
| `positions` | Nombre, descripción, departamento y estado. | Pertenece a un departamento. |
| `users` | Cuenta, contraseña hash, rol y estado. | Pertenece al módulo Auth. No se guarda contraseña en `employees`. |

Relaciones:

- Un departamento tiene muchos empleados.
- Un departamento tiene muchos cargos.
- Un cargo pertenece a un departamento y puede estar asociado a varios empleados.
- Un empleado pertenece a un departamento y puede tener un cargo.
- Un empleado puede estar asociado a los registros de asistencia y permisos.
- Un empleado puede ser creado o editado por un usuario, registrado en `created_by` y
  `updated_by`.

## 6. Flujos y pantallas

1. **Listado de empleados:** tabla con búsqueda, filtros por departamento y estado,
   paginación y acciones.
2. **Registro de empleado:** formulario con validaciones y selección de departamento
   y cargo.
3. **Ficha del empleado:** datos personales y laborales, estado e historial básico.
4. **Edición:** actualización de campos permitidos.
5. **Desactivación/reactivación:** cambio de estado sin eliminar registros.
6. **Departamentos y cargos:** listado, creación, edición y desactivación.

## 7. Reglas de negocio

- El documento de identidad es obligatorio y único.
- Los campos obligatorios se validan en el servidor.
- El correo, si se informa, debe ser válido y único. Se guarda en minúsculas.
- El teléfono es opcional y debe tener un formato razonable.
- La fecha de ingreso es obligatoria y debe ser una fecha válida.
- El cargo debe pertenecer al departamento seleccionado. Es opcional.
- Un cargo con empleados asignados no puede cambiar de departamento.
- Un empleado inactivo conserva su historial, pero no admite nuevas operaciones
  normales de asistencia ni nuevas solicitudes de permiso.
- No se elimina físicamente un empleado con asistencia, permisos o información
  relacionada.
- Un departamento o cargo con empleados asociados no se elimina; se desactiva.
- No se puede reactivar un empleado o cargo si su departamento está inactivo.
- Solo el administrador crea cuentas, las desactiva y modifica roles.
- RR. HH. puede administrar registros laborales, pero no asignarse permisos de
  administrador.
- La desactivación y la reactivación se registran en auditoría.
- El documento se normaliza (sin espacios y en mayúsculas) antes de comprobar
  duplicados.

## 8. Dependencias

- **Auth/Core:** usuario autenticado, roles, CSRF y sesión.
- **Attendance:** consume el contrato de lectura de empleados para validar que un
  empleado existe y está activo.
- **Recruitment:** consume el contrato de lectura y el alta guiada de un candidato
  seleccionado.
- **Dashboard:** consulta conteos a través de servicios públicos.

### Contrato público implementado

`src/Modules/Employees/Contracts/EmployeeLookup.php` está implementado por
`EmployeeLookupService` y expone operaciones de solo lectura:

- `find(int $employeeId)`: devuelve el empleado exista o no activo;
- `findMany(array $employeeIds)`: empleados indexados por ID para listados;
- `stats()`: conteos de empleados activos, inactivos y totales para el Dashboard;
- `activeEmployees()`: lista mínima de empleados activos;
- `directory()`: directorio de activos e inactivos para filtros y reportes;
- `departments()`: departamentos completos para filtros y reportes;
- `positions(?int $departmentId)`: cargos completos para filtros y formularios.

Los otros módulos **no** acceden a `EmployeeRepository` ni a las tablas `employees`,
`departments` o `positions`. Attendance, Recruitment y Dashboard consumen el contrato.

## 9. Criterios de aceptación

- [x] Se puede registrar un empleado y verlo en el listado.
- [x] Se rechaza un documento duplicado.
- [x] Se rechaza un correo duplicado cuando se informa.
- [x] Se puede buscar y filtrar por nombre, documento, departamento y estado.
- [x] Los cambios se reflejan en la ficha.
- [x] Se puede desactivar y reactivar un empleado.
- [x] La desactivación no elimina su historial asociado.
- [x] Un usuario sin permiso no puede acceder a operaciones restringidas por URL.
- [x] Un cargo de otro departamento es rechazado.
- [x] Un departamento o cargo con empleados no se puede eliminar; solo desactivar.
- [x] El reporte de personal coincide con los datos almacenados.
- [ ] La interfaz es usable en móvil y escritorio. *(Bootstrap 5 implementado;
  revisión visual final pendiente).*

## 10. Reportes

- Total de empleados activos e inactivos.
- Empleados por departamento.
- Listado filtrado por estado y departamento.
- Impresión desde el navegador con estilos `@media print`.

## 11. Datos e integraciones

- Sin integraciones externas.
- Datos ficticios para desarrollo y demostración.
- No se almacenan fotos, documentos de identidad ni archivos personales.

## 12. Estructura de implementación

```text
src/Modules/Employees/
├── Contracts/        # EmployeeLookup para otros módulos
├── Controllers/      # EmployeeController, DepartmentController, PositionController
├── Repositories/     # SQL con PDO
├── Services/         # Reglas y coordinación
├── Support/          # Normalización de datos de entrada
├── Views/            # Listados, formularios, fichas y reporte
├── routes.php
└── README.md
```

## 13. Preguntas abiertas

- [ ] Confirmar el formato y longitud del documento según el país.
- [x] El cargo es opcional.
- [ ] Confirmar si se requiere un campo de fecha de salida/cese.
- [x] El correo personal es opcional.

## 14. Verificación

- **2026-10-26:** 165 comprobaciones HTTP sobre PHP 8.4 y MariaDB 11.4.
  - Altas, edición, ficha, listado, filtros, paginación y reporte.
  - Documento duplicado, correo duplicado y cargo de otro departamento.
  - CSRF, acceso sin sesión, 404 y rol `hr`.
  - Desactivación/reactivación de empleados, departamentos y cargos.
  - Reglas de departamento inactivo y cargo con empleados.
  - Auditoría sin datos personales.
- **PHPUnit:** 16 pruebas, 21 aserciones correctas.
- **Sintaxis:** 75 archivos PHP sin errores.
- **Datos:** `database/seed.sql` restaurado al terminar las pruebas.
- **Pendiente:** revisión visual responsive, MySQL 8 e InfinityFree.
