# Visión y alcance

## 1. Contexto

La empresa gestiona información básica del personal y tres actividades habituales de
Gestión Humana con apoyo de hojas de cálculo, documentos y correos. Los datos quedan
dispersos y es difícil consultarlos, mantenerlos actualizados y controlar quién puede
verlos o modificarlos.

El proyecto es un **MVP académico** que demuestra una solución web organizada para:

1. Gestionar empleados, departamentos y cargos.
2. Registrar asistencia, tardanzas, ausencias y permisos.
3. Administrar vacantes, candidatos y postulaciones.

## 2. Problema

- La información del personal está repartida en varios archivos.
- Consultar empleados por departamento o estado requiere trabajo manual.
- La asistencia y los permisos se registran sin un historial centralizado.
- Los procesos de selección no tienen un seguimiento claro por vacante.
- No existe un control de acceso uniforme sobre los datos de RR. HH.

## 3. Usuarios y roles

En el MVP solo existen dos tipos de cuenta:

| Rol | Acceso | Permisos |
| --- | ------ | -------- |
| Administrador | Sí | Acceso completo a la operación, gestión de usuarios y roles, y consulta de auditoría. |
| Personal de RR. HH. | Sí | Gestión de empleados, asistencia, permisos, candidatos y vacantes. No puede administrar cuentas ni consultar la auditoría. |
| Empleado | No | Sin cuenta en el MVP. Sus datos se gestionan por RR. HH. |
| Candidato | No | Sin cuenta. Sus datos los registra RR. HH. |

Matriz resumida:

| Acción | Administrador | RR. HH. |
| ------ | :-----------: | :-----: |
| Acceder al sistema | Sí | Sí |
| Gestionar empleados | Sí | Sí |
| Gestionar asistencia y permisos | Sí | Sí |
| Gestionar candidatos y vacantes | Sí | Sí |
| Crear usuarios y asignar roles | Sí | No |
| Restablecer contraseñas de otras cuentas | Sí | No |
| Cambiar la propia contraseña | Sí | Sí |
| Consultar auditoría | Sí | No |

## 4. Alcance del MVP

Incluye:

- Inicio de sesión con sesiones seguras.
- Roles y permisos básicos.
- Panel de navegación.
- Operaciones de creación, consulta, actualización y desactivación lógica.
- Validaciones en servidor y en navegador.
- Base de datos relacional MySQL.
- Reportes sencillos dentro de la aplicación, imprimibles desde el navegador.
- Diseño adaptable a móviles con Bootstrap 5.
- Datos ficticios para pruebas y demostración.

No incluye:

- Nómina, cálculo de impuestos o prestaciones.
- Reloj biométrico, geolocalización o marcación desde dispositivos.
- Aplicación móvil independiente.
- Portal de autoservicio para empleados.
- Envío automático de correos.
- Firma digital, contratos electrónicos o almacenamiento de documentos personales.
- Integraciones con sistemas externos.
- Evaluaciones de desempeño.
- Cálculo legal de vacaciones o días disponibles.
- Exportación a Excel o PDF (opcional si el tiempo lo permite).

## 5. Módulos y prioridad

| Prioridad | Módulo | Papel |
| --------- | ------ | ----- |
| Base | Gestión de empleados | Fuente de datos del personal; habilita a los demás módulos. |
| Operativo | Asistencia y permisos | Proceso diario y reporte mensual. |
| Complementario | Reclutamiento y selección | Proceso de incorporación de nuevo personal. |

El orden de implementación recomendado es Empleados → Asistencia y permisos →
Reclutamiento y selección. Las fichas completas están en
[`src/Modules`](../src/Modules) y las reglas de frontera en
[03-modulos.md](03-modulos.md).

## 6. Criterios de éxito

El MVP se considera funcional cuando:

- Un administrador y un usuario de RR. HH. pueden iniciar sesión.
- Cada rol ve solo las opciones y operaciones permitidas.
- Se puede registrar, consultar, editar, desactivar y reactivar un empleado.
- Se rechazan documentos duplicados y datos inválidos.
- Se puede registrar la asistencia diaria y consultar un reporte mensual coherente.
- Se pueden crear solicitudes de permiso y aprobarlas o rechazarlas.
- Se pueden crear vacantes, candidatos y postulaciones con estados controlados.
- Las operaciones son usables en escritorio y móvil.
- La aplicación está desplegada con HTTPS y dominio propio.
- La documentación y el manual de usuario permiten reproducir la demostración.

## 7. Restricciones

- Proyecto universitario con tiempo y alcance limitados.
- Hosting gratuito InfinityFree: PHP y MySQL, sin Node.js, sin SSH y sin cambio de
  document root.
- Dominio propio contratado aparte.
- Equipo pequeño; se prioriza claridad sobre infraestructura compleja.
- Datos estrictamente ficticios; no se admiten documentos de identidad, fotos,
  currículos ni información real.
- Sin integraciones externas ni envío de correos en el MVP.

## 8. Riesgos

| Riesgo | Impacto | Mitigación |
| ------ | ------- | ---------- |
| Alcance demasiado amplio | Alto | Priorizar los tres módulos y excluir todo lo demás |
| Tiempo insuficiente | Alto | Implementar por rebanadas verticales y dejar reportería avanzada para el final |
| Límites de InfinityFree | Medio | Probar el despliegue temprano y mantener backups |
| Datos reales por error | Alto | Usar solo datos ficticios y revisar antes de cada demo |
| Fallos de permisos | Alto | Probar accesos permitidos y denegados en servidor |
| Deuda por SQL duplicado | Medio | Centralizar SQL en repositorios y revisar PRs |
| Dependencia de una sola persona | Medio | Documentación, convenciones y revisión cruzada |

## 9. Preguntas abiertas

- [ ] Confirmar los tipos de permiso que se mostrarán en el selector: vacaciones,
      permiso personal, incapacidad/enfermedad y otro.
- [ ] Confirmar el formato y longitud del documento de identidad según el país.
- [ ] Definir si el nombre del sistema y el logotipo serán los definitivos.
- [ ] Confirmar si se requiere exportación a Excel o PDF; en principio es opcional.
- [ ] Definir la zona horaria de la empresa para registrar horas y fechas.
