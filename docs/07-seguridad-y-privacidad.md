# Seguridad y privacidad

La aplicación maneja datos personales y laborales. Aunque el proyecto es académico y
usa datos ficticios, debe aplicar controles equivalentes a un sistema real. La
seguridad es un requisito del producto, no una fase final.

## 1. Principios

- **Privacidad desde el diseño:** recoger solo los datos necesarios.
- **Mínimo privilegio:** cada rol accede solo a lo imprescindible.
- **Denegar por defecto:** sin permiso explícito, se deniega.
- **Defensa en profundidad:** validación, autorización, sesiones, CSRF, escape y logs.
- **Trazabilidad:** las acciones sensibles quedan registradas.
- **Datos ficticios:** nunca usar información real en desarrollo, demos ni repositorio.
- **Servidor como autoridad:** la validación y los permisos no se delegan al navegador.

## 2. Roles

| Rol | Descripción |
| --- | ----------- |
| `admin` | Acceso completo a la operación; único que gestiona usuarios, roles y consulta la auditoría. |
| `hr` | Gestión operativa de empleados, asistencia, permisos y reclutamiento. No administra cuentas ni consulta auditoría. |
| Empleado / candidato | Sin cuenta en el MVP. |

Matriz de permisos:

| Acción | Admin | RR. HH. |
| ------ | :---: | :-----: |
| Iniciar sesión | Sí | Sí |
| Ver dashboard | Sí | Sí |
| Gestionar empleados | Sí | Sí |
| Gestionar departamentos y cargos | Sí | Sí |
| Registrar y consultar asistencia | Sí | Sí |
| Aprobar o rechazar permisos | Sí | Sí |
| Gestionar vacantes, candidatos y postulaciones | Sí | Sí |
| Crear, desactivar o modificar usuarios | Sí | No |
| Asignar roles | Sí | No |
| Restablecer contraseñas de otras cuentas | Sí | No |
| Cambiar la propia contraseña | Sí | Sí |
| Consultar auditoría | Sí | No |

Reglas:

- Los permisos se validan en el servidor, no solo ocultando enlaces.
- El rol `hr` no puede elevar sus privilegios ni crear administradores.
- Un administrador no puede desactivar su propia cuenta.
- El último administrador activo no puede desactivarse ni cambiar a `hr`. La
  comprobación se ejecuta dentro de una transacción con bloqueo de los administradores
  activos.
- Un cambio de rol o una desactivación invalidan la sesión de la cuenta afectada en la
  siguiente petición.
- Los cambios de rol, estado y contraseña se auditan; la metadata se limita a `status` y
  `role`.
- Las cuentas se desactivan; no se eliminan si tienen historial.

## 3. Autenticación

- Login con correo y contraseña.
- Contraseñas con `password_hash($password, PASSWORD_DEFAULT)`.
- Verificación con `password_verify()`.
- Rehash automático si `password_needs_rehash()` lo indica.
- Longitud mínima de contraseña: 10 caracteres.
- No mostrar si el correo existe o no en un login fallido.
- Rate limiting compartido entre peticiones: 5 fallos por correo y 20 por IP en una
  ventana de 15 minutos. El contador se guarda en `storage/rate-limits/` y falla
  abierto si el almacenamiento no está disponible.
- `session_regenerate_id(true)` al iniciar sesión. Un cambio de rol cierra la sesión y
  obliga a iniciar de nuevo.
- Un cambio o restablecimiento de contraseña invalida las demás sesiones de la cuenta;
  la sesión que realizó el cambio se conserva.
- Cookies de sesión:
  - `HttpOnly = true`
  - `Secure = true` en producción
  - `SameSite = Lax`
  - `session.use_strict_mode = 1`
- Expiración por inactividad configurable.
- Logout destruye la sesión y la cookie.
- Sin cuentas compartidas ni contraseñas por defecto en producción.

## 4. Autorización

- Middleware `AuthMiddleware` para rutas privadas.
- Middleware `RoleMiddleware` para rutas exclusivas de `admin`.
- Las rutas `/users*` y `/audit` son exclusivas de `admin`; `/account/password` está
  disponible para ambos roles.
- Comprobaciones de recurso en el servicio cuando aplica (por ejemplo, que el empleado
  exista y esté activo).
- Denegar por defecto; devolver 403 sin revelar detalles internos.
- No confiar en IDs ocultos ni en parámetros manipulables.
- El menú puede ocultar opciones, pero el servidor siempre vuelve a comprobar.
- Probar cada restricción con un caso permitido y otro denegado.

## 5. CSRF

- Generar un token por sesión.
- Incluir el token en todos los formularios `POST`.
- Validar el token en el servidor antes de procesar la acción.
- Rechazar peticiones sin token o con token inválido.
- Regenerar el token después de un login.
- No aceptar mutaciones por `GET`.
- Usar `hash_equals()` para comparar tokens.

## 6. XSS y salida

- Escapar toda salida dinámica con:
  `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.
- Usar el helper `e()` en las vistas.
- No insertar HTML generado por el usuario.
- Evitar `innerHTML` en JavaScript; usar `textContent` cuando sea posible.
- Las observaciones y motivos son texto plano, no HTML.
- Configurar una política CSP compatible con Bootstrap; preferiblemente self-host de
  los assets para reducir orígenes permitidos.

## 7. SQL e integridad

- Todas las consultas usan PDO con parámetros vinculados.
- `PDO::ATTR_EMULATE_PREPARES = false`.
- `PDO::ATTR_ERRMODE = PDO::ERRMODE_EXCEPTION`.
- No concatenar entradas del usuario en SQL.
- Los nombres de columnas para ordenar se eligen desde una lista blanca.
- Las operaciones múltiples usan transacciones.
- Restricciones en base de datos: únicas, claves foráneas y `CHECK` de fechas/estados.
- Los errores de base de datos no se muestran al usuario.

## 8. Validación

- Validar en servidor todos los datos obligatorios, formatos, longitudes y reglas.
- Validar también en el navegador para mejorar la experiencia.
- Rechazar fechas inválidas, salida anterior a entrada y períodos invertidos.
- Comprobar duplicados en base de datos y mostrar un mensaje claro.
- No confiar en `required`, `type=date` ni otros controles HTML.
- Normalizar correos a minúsculas antes de comparar.
- Limitar la longitud de todos los campos.
- No permitir HTML en campos de texto libre.

## 9. Datos personales

- El MVP usa datos ficticios; no se almacenan documentos de identidad, fotos,
  currículos ni información de salud real.
- Los campos de documento, teléfono y correo de la demo son inventados.
- No incluir PII en logs, URLs, mensajes de error ni capturas.
- No exportar datos a servicios externos.
- No enviar correos automáticos.
- Si en el futuro se usan datos reales, aplicar política de retención, acceso,
  rectificación y eliminación según la normativa local.
- Revisar la normativa aplicable con asesoría legal antes de un uso real.

## 10. Auditoría

Registrar en `audit_logs`:

- creación, edición, activación/desactivación y restablecimiento de contraseña de
  usuarios;
- cambio de contraseña propia;
- desactivación y reactivación de empleados, departamentos y cargos;
- altas y correcciones de asistencia;
- aprobación o rechazo de permisos;
- cierre de vacantes;
- alta de postulaciones, cambios de estado y alta guiada.

El MVP no registra automáticamente cada inicio de sesión ni los intentos fallidos; la
tabla demo conserva un registro de ejemplo. La metadata de las acciones nuevas se limita
a `status` y `role`.

Campos:

- usuario responsable;
- acción;
- tipo e identificador de entidad;
- metadatos mínimos;
- fecha/hora UTC;
- IP (opcional y tratada como dato personal).

No registrar:

- contraseñas ni hashes;
- tokens de sesión o CSRF;
- contenido completo de campos sensibles;
- datos de salud o salario.

La auditoría es de solo escritura para la aplicación y su consulta está restringida.

## 11. Cabeceras y transporte

Configurar en producción:

- HTTPS obligatorio y redirección desde HTTP.
- `Strict-Transport-Security` cuando el dominio esté estable.
- `X-Content-Type-Options: nosniff`.
- `X-Frame-Options: DENY` y CSP `frame-ancestors 'none'`.
- `Referrer-Policy: strict-origin-when-cross-origin`.
- `Permissions-Policy` restrictiva para cámara, micrófono, geolocalización, pagos y USB.
- `Content-Security-Policy` adaptada a los assets reales, con `object-src 'none'`.
- `Cache-Control: no-store, private` en respuestas dinámicas con datos de la aplicación.

InfinityFree ofrece SSL gratuito; verificar que el certificado esté activo antes de la
entrega.

## 12. Backups y continuidad

- Exportar la base antes de cada despliegue importante.
- Guardar backups fuera del hosting y con acceso restringido.
- Probar la restauración al menos una vez antes de la entrega.
- No incluir `.env` con credenciales en paquetes compartidos.
- Documentar fecha, contenido y responsable del backup.
- RPO/RTO para el MVP: backup manual antes de cada cambio; restauración en menos de un
  día laborable.

## 13. Riesgos del hosting compartido

- No hay aislamiento total entre cuentas; no usar datos reales.
- Sin SSH ni Composer CLI; el código se sube por FTP.
- Sin cron; las tareas programadas no forman parte del MVP.
- Límites de recursos y posible suspensión por inactividad.
- El document root no se puede cambiar; se usa `.htaccess`.
- Los archivos fuera de `public/` deben bloquearse explícitamente, incluido `exceso/`.
- Revisar los términos y límites del plan antes de la entrega.

## 14. Checklist antes de la demo

- [ ] Login, logout y expiración de sesión.
- [ ] Cookies `HttpOnly`, `SameSite` y `Secure` en producción.
- [ ] CSRF en todos los formularios.
- [ ] Salida escapada en todas las vistas.
- [ ] Consultas preparadas en todos los repositorios.
- [ ] Permisos de admin y RR. HH. probados.
- [ ] Accesos directos por URL denegados cuando corresponde.
- [ ] Sin credenciales por defecto en producción.
- [ ] `.env` fuera de `public/` y no versionado.
- [ ] HTTPS y cabeceras configuradas.
- [ ] Backups exportados y restauración probada.
- [ ] Logs sin contraseñas ni PII.
- [ ] Datos de demostración ficticios.
- [ ] Documentación y manual actualizados.

## 15. Referencias

- PHP Security: <https://www.php.net/manual/es/security.php>
- PHP Sessions: <https://www.php.net/manual/es/book.session.php>
- OWASP Top 10: <https://owasp.org/www-project-top-ten/>
- OWASP Cheat Sheet Series: <https://cheatsheetseries.owasp.org/>
- OWASP ASVS: <https://owasp.org/www-project-application-security-verification-standard/>
