# Manual de usuario — Gestión RR. HH.

Este manual explica cómo usar la aplicación web **Gestión RR. HH.** con las cuentas de
demostración. Todos los datos del sistema son ficticios.

- **URL de demostración:** <https://evolvehr.cloud>
- **Roles:** Administrador (`admin`) y Personal de RR. HH. (`hr`).
- **Navegador recomendado:** Chrome, Edge o Firefox actualizados.

> La primera visita al sitio publicado puede mostrar un reto de seguridad del hosting
> antes de cargar la aplicación. Es normal; espera unos segundos y continúa.

## 1. Iniciar sesión

1. Abre la URL de la aplicación.
2. Escribe tu correo y contraseña.
3. Pulsa **Entrar**.

Credenciales de demostración:

| Rol | Correo | Contraseña |
| --- | ------ | ---------- |
| Administrador | `admin@gestion-rrhh.test` | `password123` |
| RR. HH. | `rrhh@gestion-rrhh.test` | `password123` |

Consejos:

- Usa el icono del ojo para mostrar u ocultar la contraseña.
- Si fallas varias veces, el sistema bloquea temporalmente los intentos. Espera unos
  minutos antes de volver a probar.
- Si tu cuenta fue desactivada o tus permisos cambiaron, la sesión se cierra y el login
  muestra el motivo.

## 2. Conocer la pantalla

- **Barra superior:** accesos a Empleados, Asistencia y Reclutamiento.
- **Menú de usuario:** nombre, rol, **Mi cuenta** y **Cerrar sesión**.
- **Administrador:** además ve **Usuarios** y **Auditoría**.
- **Panel:** resume el personal, la asistencia del día, los permisos pendientes y el
  estado del reclutamiento.
- **Enlace «Saltar al contenido»:** aparece al navegar con teclado y lleva al contenido
  principal sin recorrer todo el menú.

## 3. Permisos por rol

| Acción | Administrador | RR. HH. |
| ------ | :-----------: | :-----: |
| Ver el panel | Sí | Sí |
| Gestionar empleados, departamentos y cargos | Sí | Sí |
| Registrar y corregir asistencia | Sí | Sí |
| Crear y revisar permisos | Sí | Sí |
| Gestionar vacantes, candidatos y postulaciones | Sí | Sí |
| Dar de alta a un candidato seleccionado | Sí | Sí |
| Crear, editar, desactivar o reactivar usuarios | Sí | No |
| Restablecer contraseñas de otras cuentas | Sí | No |
| Consultar la auditoría | Sí | No |

Si RR. HH. intenta abrir `/users` o `/audit` escribiendo la dirección, recibe un error
de acceso denegado. Ocultar un enlace no sustituye la comprobación del servidor.

## 4. Módulo de empleados

### 4.1 Consultar y filtrar

1. Entra en **Empleados**.
2. Usa **Buscar** por nombre, documento o correo.
3. Filtra por **Departamento** o **Estado**.
4. Pulsa **Filtrar**; usa **Limpiar** para quitar los filtros.

La tabla muestra empleado, departamento, cargo, fecha de ingreso y estado. Los botones de
la columna de acciones permiten ver la ficha o editar.

### 4.2 Registrar un empleado

1. Pulsa **Nuevo empleado**.
2. Completa documento, fecha de ingreso, nombres y apellidos.
3. Añade correo y teléfono si corresponde.
4. Selecciona departamento y, opcionalmente, cargo.
5. Pulsa **Registrar empleado**.

Reglas importantes:

- El documento es obligatorio y no puede repetirse.
- El correo es opcional, pero si se informa debe ser único.
- El cargo debe pertenecer al departamento elegido.
- El empleado se crea como **activo**.
- No se puede registrar si no hay departamentos activos.

### 4.3 Editar, desactivar y reactivar

- **Editar:** cambia datos laborales y de contacto desde la ficha.
- **Desactivar:** el empleado conserva su historial, pero no admite nuevas asistencias.
- **Reactivar:** vuelve a habilitarlo si su departamento está activo.

No hay borrado físico de empleados, departamentos ni cargos.

### 4.4 Departamentos y cargos

- **Departamentos:** agrupan al personal. Un departamento inactivo no permite nuevas
  altas ni reactivaciones de empleados o cargos.
- **Cargos:** pertenecen a un departamento. Un cargo con empleados no puede cambiar de
  departamento.

### 4.5 Reporte de empleados

Pulsa **Reporte** en el listado para ver el resumen imprimible. Usa el botón **Imprimir**
o `Ctrl+P`. La navegación y los botones no se imprimen.

## 5. Módulo de asistencia y permisos

### 5.1 Registrar asistencia

1. Entra en **Asistencia**.
2. Pulsa **Registrar asistencia**.
3. Elige empleado, fecha y estado.
4. Para **Presente** o **Tardanza**, informa la hora de entrada; la salida es opcional.
5. Para **Ausencia**, añade una observación.
6. Guarda el registro.

Reglas:

- Solo se registra asistencia de empleados activos.
- No se permiten fechas futuras.
- Solo puede existir un registro por empleado y fecha.
- La hora de salida no puede ser anterior a la de entrada.
- Al corregir un registro no se cambia el empleado.

### 5.2 Crear una solicitud de permiso

1. Entra en **Permisos**.
2. Pulsa **Nueva solicitud**.
3. Elige empleado, tipo de permiso y fechas.
4. Escribe el motivo.
5. Guarda la solicitud.

La solicitud nace **pendiente**. No se permiten solicitudes que se superpongan con otra
pendiente o aprobada del mismo empleado.

### 5.3 Revisar una solicitud

1. Abre la solicitud desde el listado.
2. Selecciona **Aprobar** o **Rechazar**.
3. Añade una observación si lo deseas.
4. Pulsa **Registrar revisión** y confirma.

La revisión es única: una solicitud resuelta no se puede volver a revisar. Si no eliges
una decisión, el formulario muestra el error junto al campo sin perder lo escrito.

### 5.4 Reporte mensual

1. Entra en **Reporte mensual**.
2. Elige mes y, si quieres, departamento.
3. Pulsa **Aplicar**.
4. Pulsa **Imprimir** para obtener la versión en papel.

El reporte incluye totales de asistencia, permisos del mes y el detalle por empleado.

## 6. Módulo de reclutamiento

### 6.1 Vacantes

- **Nueva vacante:** título, descripción, departamento, cargo y fechas.
- **Editar:** mientras la vacante esté abierta.
- **Cerrar vacante:** acción final; no se reabre ni acepta nuevas postulaciones.
- Una vacante cerrada conserva su historial y sus postulaciones.

### 6.2 Candidatos

- Registra nombres, apellidos, correo y teléfono.
- El correo es único.
- Un candidato puede editarse después de postular y conserva sus postulaciones.

### 6.3 Postulaciones y estados

1. Entra en **Postulaciones** y pulsa **Nueva postulación**.
2. Elige candidato y vacante abierta.
3. Añade observaciones si corresponde.
4. Guarda.

Estados y transiciones:

```text
Recibida → En evaluación → Entrevista → Seleccionada
    └──────────┴──────────────┴──────────→ Rechazada
```

- **Seleccionada** y **Rechazada** son finales.
- No se permiten saltos de estado ni repetir el estado actual.
- Si otra persona cambió la postulación mientras la tenías abierta, el sistema avisa y
  pide revisar el estado actual antes de continuar.

### 6.4 Dar de alta a un candidato

1. Abre una postulación en estado **Seleccionada**.
2. Pulsa **Dar de alta como empleado**.
3. Revisa y completa los datos personales y laborales.
4. Confirma el alta.

El alta crea el empleado y vincula la postulación en una sola operación. Si algo falla,
no se crea un empleado a medias. Solo se permite un alta por postulación.

## 7. Usuarios y contraseñas (solo administrador)

### 7.1 Crear un usuario

1. Entra en **Usuarios** y pulsa **Nuevo usuario**.
2. Completa nombre, correo, rol y contraseña.
3. Pulsa **Crear usuario**.

La contraseña debe tener al menos 10 caracteres, una letra y un número.

### 7.2 Editar, desactivar y reactivar

- **Editar:** cambia nombre, correo o rol.
- **Desactivar:** corta la sesión activa y bloquea nuevos inicios.
- **Reactivar:** permite volver a entrar.

Protecciones:

- No puedes desactivar tu propia cuenta.
- El último administrador activo no puede desactivarse ni cambiar a RR. HH.
- Un cambio de rol cierra la sesión afectada para aplicar los permisos nuevos.

### 7.3 Restablecer una contraseña

1. Abre el usuario y pulsa **Restablecer contraseña**.
2. Define y confirma la nueva contraseña.
3. Guarda.

Las demás sesiones de esa cuenta se invalidan. La contraseña anterior deja de funcionar.

## 8. Mi cuenta

1. Abre el menú de usuario y elige **Mi cuenta**.
2. Escribe la contraseña actual.
3. Escribe y confirma la nueva contraseña.
4. Pulsa **Actualizar contraseña**.

La nueva contraseña debe ser distinta de la actual, tener al menos 10 caracteres, una
letra y un número. Tu sesión actual se mantiene; las demás sesiones de tu cuenta se
cierran.

## 9. Auditoría (solo administrador)

**Auditoría** muestra las acciones sensibles: altas y cambios de usuarios, cambios de
contraseña, estados de empleados, asistencias, permisos, vacantes, postulaciones, altas
guiadas e inicios de sesión.

- Filtra por texto, entidad, acción o rango de fechas.
- La tabla no muestra correos, contraseñas ni datos personales.
- Los eventos de login guardan solo el motivo y la IP; nunca la contraseña.

## 10. Accesibilidad y teclado

- Usa `Tab` y `Shift+Tab` para moverte entre controles.
- El primer `Tab` muestra **Saltar al contenido**.
- Los elementos enfocados tienen un contorno visible.
- Los mensajes de error se anuncian y se asocian al campo correspondiente.
- Las tablas tienen etiqueta para lectores de pantalla.
- Los iconos decorativos se ocultan a los lectores; los botones con solo icono tienen
  nombre accesible.
- En móvil, las tablas permiten desplazamiento horizontal sin romper la página.

## 11. Impresión

- Los reportes de Empleados y Asistencia tienen botón **Imprimir**.
- El menú, los botones y los avisos de pantalla no se imprimen.
- Las tablas repiten el encabezado en cada página y evitan cortar filas.
- Si el navegador lo permite, activa «Gráficos de fondo» para conservar los colores de
  las insignias.

## 12. Problemas frecuentes

| Situación | Qué hacer |
| --------- | --------- |
| «El formulario expiró o no es válido» | Recarga la página y vuelve a enviar. No uses el botón Atrás tras enviar. |
| La sesión se cerró sola | La cuenta fue desactivada, cambió de rol o cambió la contraseña en otro dispositivo. Inicia sesión de nuevo. |
| «No hay departamentos activos» | Un administrador o RR. HH. debe crear o reactivar un departamento. |
| No aparece un permiso o una postulación | Revisa los filtros activos y pulsa **Limpiar**. |
| El sexto intento de login no funciona | Espera unos minutos; el límite es temporal. |
| No veo Usuarios ni Auditoría | Tu cuenta es de RR. HH.; esas secciones son solo para administrador. |
| La página publicada no carga a la primera | El hosting muestra un reto de seguridad; espera y recarga. |

## 13. Cerrar sesión

Usa **Cerrar sesión** en el menú de usuario. Si usas un equipo compartido, cierra también
el navegador. No dejes sesiones abiertas en equipos públicos.

## 14. Datos y privacidad

- La demo usa datos ficticios.
- No introduzcas información real de personas.
- No compartas la URL ni las credenciales fuera del círculo de la demostración.
- La auditoría y los logs no guardan contraseñas ni datos personales innecesarios.
