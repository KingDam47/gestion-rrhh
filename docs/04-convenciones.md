# Convenciones de desarrollo

## 1. Idioma

- **Interfaz, mensajes y documentación:** español.
- **Identificadores de código** (clases, métodos, variables, tablas y columnas): inglés.
- **Commits:** tipo en inglés (Conventional Commits) y descripción en español.
- **Comentarios:** en español, solo para explicar decisiones no obvias.

## 2. Estilo PHP

- Cumplir **PSR-12**.
- `declare(strict_types=1);` en todos los archivos PHP.
- Sin etiqueta de cierre `?>` en archivos que solo contienen PHP.
- Codificación UTF-8 sin BOM, saltos de línea LF.
- Indentación de 4 espacios en PHP; 2 espacios en HTML, CSS, JS, JSON y YAML.
- Una clase por archivo; el nombre del archivo coincide con la clase.
- Namespaces alineados con la estructura de carpetas:
  `App\Modules\Employees\Controllers\EmployeeController`.
- Usar tipos en parámetros, propiedades y retornos.
- Inyección de dependencias por constructor; evitar `new` dentro de controladores.
- Sin variables globales, `extract()`, `eval()` ni funciones mágicas.
  Excepción controlada: `Core\View` usa `extract($data, EXTR_SKIP)` para renderizar
  plantillas con datos ya preparados. No repetir ese patrón en otro lugar.
- Preferir clases `final` en controladores y servicios cuando no se vayan a extender.
- Los arrays de configuración se devuelven desde `config/`; no se leen variables de
  entorno dispersas por el código.

## 3. Naming

| Elemento | Convención | Ejemplo |
| -------- | ---------- | ------- |
| Carpetas de módulo | PascalCase | `Modules/Employees/` |
| Archivos de clase | PascalCase | `EmployeeController.php` |
| Clases | PascalCase | `EmployeeService` |
| Métodos y variables | camelCase | `findActiveEmployees()` |
| Constantes | UPPER_SNAKE_CASE | `MAX_PAGE_SIZE` |
| Tablas | snake_case plural | `attendance_records` |
| Columnas | snake_case | `created_at`, `employee_id` |
| Vistas | minúsculas y guiones | `leave-requests/index.php` |
| Archivos CSS/JS | kebab-case | `app.css`, `form-validation.js` |
| Variables de entorno | UPPER_SNAKE_CASE | `DB_HOST` |
| Booleanos | prefijo `is/has/can/should` | `isActive`, `canApprove` |
| Tests | `*Test.php` | `LeaveRequestServiceTest.php` |

Reglas adicionales:

- Evitar abreviaturas y nombres genéricos (`data`, `info`, `manager`, `utils2`).
- Los nombres expresan intención, no tipo (`employees`, no `employeeArray`).
- Un método debe hacer una cosa; si necesita más de ~40 líneas, dividirlo.
- Los nombres de estados en base de datos van en inglés y en `snake_case`
  (`pending`, `approved`, `in_review`).
- Las etiquetas en español se resuelven en la vista o en un helper de presentación.

## 4. Organización del código

- `public/` es el único directorio expuesto.
- `src/Core/` contiene infraestructura técnica, nunca reglas de negocio.
- `src/Modules/<Modulo>/` contiene todo lo propio de una capacidad de negocio.
- `src/Shared/` contiene layout, partials y helpers sin negocio.
- `config/` contiene configuración y lectura de entorno.
- `database/` contiene SQL versionado.
- `storage/` contiene logs; nunca archivos públicos.
- `tests/` contiene pruebas PHPUnit.

No crear carpetas globales por tipo (`controllers/`, `services/`, `models/`) fuera de
un módulo. El código se organiza por capacidad de negocio.

## 5. Reglas de PHP

### Entrada y salida

- No usar `$_GET`, `$_POST`, `$_SESSION` ni `$_FILES` dentro de servicios o repositorios.
- El controlador obtiene datos del objeto `Request` y los pasa tipados.
- Validar en el servidor con el validador central; JavaScript es solo una ayuda.
- No confiar en datos ocultos, IDs enviados por el cliente ni en el rol de la sesión
  sin comprobarlo en servidor.
- Toda salida dinámica en HTML se escapa con `e()`:
  `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.

### Errores

- Usar excepciones para errores inesperados y errores de validación controlados.
- No mostrar trazas ni mensajes internos al usuario.
- Registrar detalle técnico en `storage/logs`, sin contraseñas, tokens ni PII.
- Los errores 404, 403, 422 y 500 se renderizan con vistas propias.
- No silenciar errores con `@` ni con `try/catch` vacíos.

### Base de datos

- Usar PDO con `ERRMODE_EXCEPTION`.
- Todas las consultas con parámetros vinculados; nunca concatenar valores.
- No se permite SQL en controladores, vistas ni JavaScript.
- Los nombres de columnas/tablas dinámicos solo se aceptan desde una lista blanca.
- Las operaciones que afectan varias tablas van en transacción.
- Los repositorios devuelven modelos o arrays tipados, no filas crudas sin contrato.
- Los `JOIN` se limitan a tablas del mismo módulo. Para datos de otros módulos se usa
  su contrato público.

### Sesiones y seguridad

- Iniciar sesión con `session_regenerate_id(true)`.
- Cookies `HttpOnly`, `SameSite=Lax` y `Secure` en producción.
- Token CSRF en todos los formularios `POST` y validación en el servidor.
- `password_hash()` para almacenar y `password_verify()` para comprobar.
- No almacenar contraseñas, tokens ni datos personales en logs.
- Aplicar permisos en middleware **y** en el servicio cuando la operación afecte a un
  recurso concreto.

## 6. Vistas y frontend

- Las vistas son archivos PHP que reciben variables preparadas por el controlador.
- No ejecutar consultas ni llamar servicios desde una vista.
- Usar el layout compartido y partials para navegación, mensajes flash y errores.
- Bootstrap 5 para componentes y responsive; CSS propio en `public/assets/css/app.css`.
- JavaScript progresivo en `public/assets/js/`; no usar frameworks frontend.
- Validación en cliente para mejorar la experiencia, nunca como única defensa.
- Evitar estilos y scripts inline; si son imprescindibles, justificar y permitir en CSP.
- Toda tabla con datos necesita estado vacío, mensaje de error y paginación.
- Formularios con etiquetas asociadas, mensajes de error junto al campo y foco visible.
- Confirmar acciones destructivas o irreversibles con un diálogo.
- Los reportes deben verse bien al imprimir (`@media print`).

## 7. SQL y migraciones

- Palabras clave SQL en mayúsculas; nombres en snake_case.
- Cada migración es un archivo `YYYYMMDDHHMM_descripcion.sql`.
- Una migración aplicada no se edita; se crea otra.
- Toda migración debe poder ejecutarse en MySQL 8 y MariaDB 11.
- Usar `utf8mb4` y `utf8mb4_unicode_ci`.
- Evitar borrados físicos de registros con historial.
- Documentar el propósito y el rollback en la cabecera del archivo.
- Los datos demo viven en `database/seed.sql`, nunca en una migración de producción.

## 8. Git

### Ramas

- `main` siempre desplegable.
- Ramas cortas: `feat/...`, `fix/...`, `docs/...`, `refactor/...`, `chore/...`.
- Una rama, un propósito.

### Commits

Formato: `tipo(alcance): descripción`

| Tipo | Uso |
| ---- | --- |
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de un error |
| `refactor` | Cambio interno sin alterar comportamiento |
| `test` | Pruebas |
| `docs` | Documentación |
| `chore` | Mantenimiento, dependencias o configuración |
| `perf` | Rendimiento |
| `security` | Corrección de seguridad |

Ejemplos:

```text
feat(employees): agregar registro de empleados
fix(attendance): rechazar salida anterior a la entrada
docs(adr): aceptar stack PHP y MySQL
```

Reglas:

- No commitear `.env`, credenciales, backups ni datos reales.
- No commitear `vendor/`, logs ni caché.
- PRs pequeñas y con título claro.
- Squash merge a `main`.
- Proteger `main` cuando el repositorio esté en GitHub.

## 9. Testing

- Unitarias: reglas de servicios y validadores, sin base de datos.
- Integración: repositorios y transacciones contra una base de prueba.
- Funcionales: flujos por rol, permisos, formularios y reportes.
- Datos de prueba ficticios y deterministas.
- Cada bug corregido incluye una prueba de regresión.
- Los tests no usan la base de producción ni datos reales.
- Comando esperado: `composer test` o `vendor/bin/phpunit`.

## 10. Definition of Done

Una tarea está terminada cuando:

- [ ] Cumple sus criterios de aceptación.
- [ ] Tiene pruebas relevantes y pasan.
- [ ] Pasa `php -l` en todos los archivos modificados.
- [ ] No hay avisos de PSR-12 relevantes.
- [ ] Funciona en escritorio y móvil.
- [ ] Tiene estados de carga, vacío y error.
- [ ] Escapa toda salida y valida toda entrada.
- [ ] Comprueba permisos en servidor.
- [ ] Usa consultas preparadas.
- [ ] No introduce secretos ni datos personales.
- [ ] La documentación o el ADR se actualizaron si aplica.
- [ ] La migración fue probada en una base limpia.
- [ ] La PR fue revisada.
- [ ] El flujo se verificó en el entorno local o de producción.

## 11. Revisión de código

El revisor comprueba:

- [ ] Se respetan las fronteras entre módulos.
- [ ] El controlador es delgado y delega en el servicio.
- [ ] El SQL está en el repositorio y usa parámetros.
- [ ] Las reglas de negocio no están duplicadas.
- [ ] La salida está escapada y el CSRF validado.
- [ ] Los permisos se comprueban en servidor.
- [ ] Los nombres expresan intención.
- [ ] Se cubren casos límite y errores.
- [ ] No se añadieron dependencias innecesarias.
- [ ] La documentación está actualizada.
