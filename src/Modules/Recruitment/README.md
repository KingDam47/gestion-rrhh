# Módulo 03 — Reclutamiento y selección

> **Estado:** Implementado y verificado (Fase 2) · **Prioridad:** Complementario · **Carpeta:** `src/Modules/Recruitment`
> **Depende de:** Employees, Auth/Core · **Es dependencia de:** Dashboard

## 1. Objetivo

Administrar vacantes, registrar candidatos y dar seguimiento a los procesos de
selección, desde la postulación hasta la selección o el rechazo.

## 2. Problema que resuelve

Evita llevar candidatos y vacantes en archivos separados y permite conocer el estado de
cada proceso de contratación, además de identificar candidatos seleccionados para una
contratación posterior.

## 3. Usuarios y permisos

| Rol | Permisos |
| --- | -------- |
| Administrador | Acceso completo. |
| Personal de RR. HH. | Gestionar vacantes, candidatos y postulaciones; cambiar estados y consultar resultados. |
| Candidato | Sin cuenta en el MVP. |

## 4. Alcance del MVP

Incluye:

- Crear, editar y cerrar vacantes.
- Registrar y consultar candidatos, con correo único.
- Asociar candidatos a vacantes mediante postulaciones.
- Cambiar el estado de una postulación solo con transiciones válidas.
- Registrar observaciones.
- Consultar vacantes abiertas y candidatos seleccionados.
- Alta guiada de un candidato seleccionado como empleado, con confirmación de RR. HH.

No incluye:

- Publicación automática en portales de empleo.
- Recepción de currículos por correo o almacenamiento de documentos.
- Entrevistas por videollamada.
- Pruebas psicométricas.
- Contratación electrónica o firma digital.
- Portal de candidatos.
- Envío automático de correos.

## 5. Entidades

| Entidad | Información principal | Notas |
| ------- | --------------------- | ----- |
| `vacancies` | Título, descripción, departamento, cargo, fecha de apertura, fecha de cierre y estado. | Abierta o cerrada. |
| `candidates` | Nombre, apellidos, correo, teléfono y observaciones. | Sin cuenta ni currículum. |
| `applications` | Candidato, vacante, fecha de postulación, estado y observaciones. | Única por candidato y vacante. |
| `users` | Usuario que registra o actualiza. | Pertenece a Auth. |

Relaciones:

- Una vacante recibe muchas postulaciones.
- Un candidato puede postularse a varias vacantes.
- Cada postulación relaciona un candidato con una vacante.
- Un candidato seleccionado puede convertirse en empleado mediante un alta guiada.

## 6. Flujos y pantallas

1. **Listado y creación de vacantes:** título, departamento, cargo, descripción y apertura.
2. **Cierre de vacante:** cambia el estado, registra la fecha real de cierre y no se reabre.
3. **Registro y listado de candidatos:** datos de contacto y observaciones.
4. **Asociación de candidato con vacante:** crea una postulación.
5. **Seguimiento de postulaciones:** listado por vacante, estado y texto.
6. **Detalle del proceso:** actualización del estado y observaciones.
7. **Consulta de vacantes abiertas y candidatos seleccionados** en el resumen del módulo.
8. **Alta guiada:** desde una postulación seleccionada, abrir el formulario de empleado
   con los datos precargados para que RR. HH. los confirme.

## 7. Reglas de negocio

- Solo se aceptan postulaciones a vacantes abiertas.
- No se permite duplicar la misma postulación para un candidato y una vacante.
- Los estados de postulación son: `received`, `screening`, `interview`, `selected`,
  `rejected`.
- Las transiciones permitidas son:

```text
received  → screening | rejected
screening → interview | rejected
interview → selected  | rejected
selected  → (final)
rejected  → (final)
```

- Una vacante cerrada no acepta nuevas postulaciones y no se reabre en el MVP.
- El correo del candidato es único para evitar duplicados.
- El cargo de la vacante debe pertenecer al departamento seleccionado.
- Un candidato puede editarse después de postular; sus postulaciones se conservan.
- Solo RR. HH. y el administrador pueden consultar y modificar vacantes, candidatos y
  postulaciones.
- Los listados usan 10 registros por página y filtros por texto, estado, departamento
  o vacante según la pantalla.
- Marcar un candidato como seleccionado **no** crea un empleado automáticamente.
  RR. HH. confirma los datos antes del registro.
- El alta guiada se ejecuta en una transacción: crea el empleado, vincula
  `applications.employee_id` y revierte todo si falla.
- No se almacenan currículos ni documentos personales en el MVP.
- El cierre de vacante, el alta de postulación, los cambios de estado y el alta guiada
  se registran en auditoría. La metadata guarda solo `status`; no incluye datos
  personales.

## 8. Dependencias

- **Employees:** departamentos, cargos, contrato de lectura (`EmployeeLookup`) y caso de
  uso de alta (`EmployeeOnboarding`).
- **Auth/Core:** sesión, roles, CSRF y usuario responsable.
- **Dashboard:** consulta el resumen de vacantes, candidatos y postulaciones mediante
  los servicios públicos del módulo.
- **Attendance:** sin dependencia.

## 9. Criterios de aceptación

- [x] Se crean, editan y cierran vacantes.
- [x] Una vacante cerrada no permite nuevas postulaciones ni se reabre.
- [x] Se registran candidatos y se asocian a vacantes.
- [x] Se impiden postulaciones duplicadas y correos de candidato duplicados.
- [x] Se cambia el estado de una postulación solo con transiciones válidas.
- [x] Se identifican candidatos seleccionados.
- [x] Se puede iniciar el alta de un empleado desde un candidato seleccionado.
- [x] El alta no se crea automáticamente sin confirmación de RR. HH.
- [x] Un usuario sin permiso no accede a los datos de candidatos.
- [x] La interfaz es adaptable a móvil y escritorio con Bootstrap 5.

Pendiente fuera de la funcionalidad: revisión visual final con el usuario en móvil y
escritorio.

## 10. Reportes

- Resumen con vacantes abiertas, postulaciones en curso y pendientes de alta.
- Listados filtrados de vacantes por estado/departamento, de postulaciones por vacante,
  estado y texto, y de candidatos por texto.
- El MVP no incluye un reporte imprimible específico de Reclutamiento; las consultas se
  resuelven en pantalla y el historial de estados queda en `audit_logs`.

## 11. Datos e integraciones

- Sin integraciones externas.
- Datos ficticios para la demo.
- Sin currículos, fotos ni documentos de identidad.
- Sin envío de correos.

## 12. Estructura de implementación

```text
src/Modules/Recruitment/
├── Controllers/      # Recruitment, Vacancy, Candidate y Application
├── Services/         # VacancyService, CandidateService, ApplicationService
├── Repositories/     # VacancyRepository, CandidateRepository, ApplicationRepository
├── Support/          # ApplicationStatus, RecruitmentData
├── Views/            # Resumen, listados, formularios, detalle y alta guiada
├── routes.php
└── README.md
```

## 13. Decisiones resueltas

- [x] Una vacante cerrada no se reabre en el MVP; el cierre es final.
- [x] Un candidato puede editarse después de postular y conserva sus postulaciones.
- [x] El historial de estados se limita a `audit_logs`
  (`application.status_changed` con metadata `{"status": ...}`); no hay una pantalla de
  historial detallado.
- [x] El alta guiada usa los datos de la postulación y exige documento, nombres,
  apellidos, correo, fecha de ingreso, departamento y cargo. El teléfono es opcional y
  el empleado nace activo.
- [x] La postulación seleccionada puede quedar pendiente de alta; el vínculo con el
  empleado solo se crea desde el alta guiada.
- [x] La paginación de los tres listados usa 10 registros por página.

## 14. Verificación

El 2026-10-26 se ejecutó `tests/Functional/RecruitmentHttpCheck.php` contra PHP 8.4 y
MariaDB 11.4 con la semilla demo:

- **535/535 comprobaciones HTTP correctas**; la base se restauró al terminar.
- **40 pruebas PHPUnit y 111 aserciones** en el proyecto, incluidas
  `ApplicationStatusTest` y `RecruitmentDataTest`.
- **143 archivos PHP** sin errores de sintaxis, sin contar `vendor/`.

La prueba cubre acceso anónimo y por rol, CRUD de vacantes y candidatos, filtros,
paginación, cierre de vacante, correo único, XSS escapado, CSRF, postulaciones
duplicadas, transiciones válidas y finales, alta guiada con validaciones y transacción,
404 y auditoría sin datos personales.
