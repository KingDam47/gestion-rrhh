# Roadmap

El proyecto avanza por fases. No se empieza una fase hasta cumplir el criterio de
salida de la anterior. Las duraciones se estiman al planificar cada entrega.

## Fase 0 — Descubrimiento y diseño (completada)

**Objetivo:** entender el problema y definir qué se construye.

Entregables:

- [x] Visión, alcance y roles.
- [x] Tres módulos definidos con alcance y criterios de aceptación.
- [x] Stack aprobado: PHP, MySQL, PDO, Bootstrap 5.
- [x] Arquitectura modular y convenciones.
- [x] Modelo de datos y esquema SQL inicial.
- [x] Estructura de carpetas adaptada a PHP.
- [x] ADR del stack y de la arquitectura.

**Criterio de salida:** requisitos, módulos, stack y modelo de datos aprobados.

## Fase 1 — Fundaciones

**Objetivo:** tener una base PHP ejecutable, segura y desplegable.

### 1.1 Entorno local

- [ ] Instalar PHP 8.2+ y MySQL/MariaDB (XAMPP, Laragon u otro).
- [x] Verificar extensiones `pdo_mysql`, `mbstring`, `openssl` y `session`.
- [x] Crear la base `gestion_rrhh`.
- [x] Importar `database/schema.sql` y `database/seed.sql`.
- [x] Configurar `.env` local.

> La verificación se hizo con PHP 8.4 y MariaDB 11.4 portátiles. Falta instalar
> un entorno permanente con XAMPP o Laragon para el trabajo diario.

### 1.2 Núcleo de la aplicación

- [x] Autoload PSR-4 y `src/bootstrap.php`.
- [x] Front controller `public/index.php`.
- [x] Router con parámetros, middlewares y 404.
- [x] Objetos `Request`, `Response` y `View`.
- [x] Configuración de app y base de datos.
- [x] Conexión PDO con excepciones y charset.
- [x] Layout compartido con Bootstrap 5.
- [x] Manejo centralizado de errores y logs.
- [x] Helper de escape `e()` y de CSRF.
- [x] Validación en servidor.

### 1.3 Autenticación y autorización

- [x] Login y logout con sesiones seguras.
- [x] Contraseñas con `password_hash()`.
- [x] CSRF en formularios.
- [x] Roles `admin` y `hr`.
- [x] Middleware de autenticación y rol.
- [x] Administración de usuarios solo para admin.
- [x] Protección del último administrador activo.
- [x] Cambio de contraseña propia para ambos roles.
- [x] Auditoría consultable solo por admin.

> **Separación de roles verificada el 2026-10-26:** 183 comprobaciones HTTP en
> `tests/Functional/RolesHttpCheck.php` y 40 pruebas PHPUnit (111 aserciones).
> RR. HH. conserva toda la operación (empleados, asistencia, permisos, vacantes,
> candidatos, postulaciones y alta guiada) pero recibe 403 en `/users*` y `/audit`.
> El administrador crea, edita, desactiva, reactiva y restablece cuentas; no puede
> desactivarse a sí mismo ni dejar el sistema sin administrador activo. Un cambio de
> rol o una desactivación invalidan la sesión afectada. La metadata de auditoría se
> limita a `status` y `role`. Probado con PHP 8.4 y MariaDB 11.4; queda pendiente
> MySQL 8.

### 1.4 Datos y despliegue

- [x] Script para crear el primer administrador.
- [x] Datos demo ficticios.
- [x] Despliegue en InfinityFree (`evolvehr.freehosting.dev`).
- [ ] Dominio propio y HTTPS con certificado del dominio definitivo.
- [x] Prueba de humo en producción.

**Criterio de salida:** login funcional, roles aplicados, layout usable, base de datos
operativa y aplicación desplegada con dominio y HTTPS.

## Fase 2 — Módulos de negocio

**Objetivo:** implementar los tres módulos por rebanadas verticales.

### 2.1 Módulo 01 — Gestión de empleados (implementado)

- [x] CRUD de departamentos y cargos.
- [x] Registro de empleados con validación y documento único.
- [x] Listado con búsqueda y filtros.
- [x] Ficha del empleado.
- [x] Edición y desactivación/reactivación.
- [x] Resumen del personal por estado y departamento.
- [x] Pruebas de reglas y permisos.
- [x] Revisión visual en escritorio.
- [ ] Revisión en móvil y validación final con el usuario.

> **Verificado el 2026-10-26:** 165 comprobaciones HTTP, 16 pruebas PHPUnit (21
> aserciones) y 75 archivos PHP sin errores de sintaxis. Incluye CSRF, duplicados,
> coherencia cargo-departamento, reglas de departamento inactivo, reportes, filtros,
> paginación, roles y auditoría. Probado con PHP 8.4 y MariaDB 11.4; queda pendiente
> MySQL 8.

### 2.2 Módulo 02 — Asistencia y permisos (implementado)

- [x] Registro diario de asistencia.
- [x] Validación de registro único por empleado y fecha.
- [x] Tardanzas y ausencias manuales.
- [x] Creación de solicitudes de permiso.
- [x] Aprobación y rechazo con trazabilidad.
- [x] Reporte mensual e impresión.
- [x] Pruebas de transiciones y permisos.
- [x] Revisión visual en escritorio.
- [ ] Revisión en móvil.

> **Verificado el 2026-10-26:** 304 comprobaciones HTTP, 23 pruebas PHPUnit (45
> aserciones) y 96 archivos PHP sin errores de sintaxis. Incluye CSRF, registro único,
> fechas futuras, horas incoherentes, empleados inactivos, solapamientos, revisión
> única, reporte por mes/departamento, roles y auditoría sin datos personales. La prueba
> funcional reutilizable está en `tests/Functional/AttendanceHttpCheck.php`. Probado con
> PHP 8.4 y MariaDB 11.4; queda pendiente MySQL 8.

### 2.3 Módulo 03 — Reclutamiento y selección (implementado)

- [x] CRUD y cierre de vacantes.
- [x] Registro de candidatos.
- [x] Postulaciones con validación de duplicados y vacante abierta.
- [x] Cambio de estado con transiciones válidas.
- [x] Listado de vacantes abiertas y candidatos seleccionados.
- [x] Alta guiada de un candidato seleccionado como empleado.
- [x] Pruebas de reglas y permisos.
- [x] Revisión visual en escritorio.
- [ ] Revisión en móvil.

> **Verificado el 2026-10-26:** 535 comprobaciones HTTP, 40 pruebas PHPUnit del proyecto
> (111 aserciones) y 143 archivos PHP sin errores de sintaxis. Incluye CSRF, correo único,
> XSS, postulaciones duplicadas, transiciones finales, alta guiada transaccional,
> filtros, paginación, roles y auditoría con metadata limitada a `status`. La prueba
> funcional reutilizable está en `tests/Functional/RecruitmentHttpCheck.php`. Probado
> con PHP 8.4 y MariaDB 11.4; queda pendiente MySQL 8.

### 2.4 Integración

- [x] Dashboard con indicadores de los tres módulos.
- [x] Navegación según rol y accesos a los tres módulos.
- [x] Reportes imprimibles en Empleados y Asistencia; Reclutamiento usa listados y
  resumen en pantalla.
- [x] Revisión responsive del Dashboard en móvil y escritorio.
- [ ] Revisión de accesibilidad (teclado, contraste, etiquetas) y responsive de los
  módulos 01, 02 y 03.

> **Dashboard verificado el 2026-10-26:** 108 comprobaciones HTTP, 40 pruebas PHPUnit del
> proyecto (111 aserciones) y 143 archivos PHP sin errores de sintaxis. Incluye líneas
> base de la base demo, acceso anónimo y por rol, indicadores de los tres módulos,
> accesos rápidos, enlaces operativos, 404 y lectura sin cambios en la base ni en la
> auditoría. La interfaz se revisó a 1440×900 y 390×844 sin desbordamiento horizontal.
> La prueba funcional reutilizable está en `tests/Functional/DashboardHttpCheck.php`.
> Probado con PHP 8.4 y MariaDB 11.4; queda pendiente MySQL 8.

**Criterio de salida:** cada módulo cumple sus criterios de aceptación, tiene pruebas y
fue validado con el usuario.

## Fase 3 — Endurecimiento

**Objetivo:** preparar la aplicación para la demostración final y el uso controlado.

- [ ] Revisión de seguridad (sesiones, CSRF, XSS, SQL, permisos).
- [ ] Pruebas de accesos denegados.
- [ ] Validación de todas las reglas de negocio en servidor.
- [ ] Backups de base de datos y archivos.
- [ ] Prueba de restauración.
- [ ] Revisión de consultas e índices.
- [ ] Accesibilidad y navegación por teclado.
- [ ] Estilos de impresión de reportes.
- [ ] Mensajes de error y estados vacíos.
- [ ] Documentación técnica actualizada.
- [ ] Manual de usuario.
- [ ] Datos demo coherentes.
- [x] Prueba completa en InfinityFree con HTTPS.

**Criterio de salida:** checklist de seguridad completo, backups probados y sin fallos
críticos conocidos.

## Fase 4 — Entrega académica

**Objetivo:** demostrar el MVP y entregar la documentación.

- [ ] Guion de demostración por rol.
- [ ] Credenciales demo.
- [ ] Base de datos demo restaurada.
- [ ] Manual de usuario.
- [ ] Documento técnico o memoria.
- [ ] Presentación.
- [ ] Ensayo de la demo.
- [ ] Backup final y enlace de producción.
- [ ] Retrospectiva y backlog post-MVP.

**Criterio de salida:** demostración reproducible, documentación entregada y aplicación
accesible por el dominio.

## Después del MVP

Ideas no comprometidas:

- Exportación a Excel/PDF.
- Notificaciones por correo.
- Portal de autoservicio para empleados.
- Historial documental.
- Evaluaciones de desempeño.
- Integración con nómina o reloj biométrico.
- Reportes avanzados.

## Ruta crítica

```text
Entorno local
  → Núcleo + Auth
    → Empleados
      → Asistencia y permisos
        → Reclutamiento
          → Dashboard + reportes
            → Seguridad + deploy
              → Entrega
```

El módulo de Empleados bloquea a los otros dos. El despliegue en InfinityFree debe
probarse antes de la fase final, no al último día.

## Bloqueos actuales

- No hay PHP ni MySQL instalados de forma permanente; se usan binarios portátiles.
  Instalar XAMPP o Laragon para el trabajo diario.
- El esquema se probó en MariaDB 11.4 en local y en InfinityFree; todavía no en MySQL 8.
- Sitio publicado en `https://evolvehr.freehosting.dev` con el subdominio temporal de
  InfinityFree; falta comprar y conectar el dominio definitivo.
- El document root de InfinityFree es fijo (`htdocs`); el `.htaccess` raíz redirige a
  `public/` y fuerza HTTPS.
- El repositorio Git está inicializado, etiquetado como `v1.0.0` y publicado en GitHub
  como repositorio privado.
