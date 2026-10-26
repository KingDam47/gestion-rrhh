# src/Core — Infraestructura técnica

Componentes reutilizables que no conocen las reglas de negocio. Implementados en la
Fase 1.

## Clases

| Clase | Responsabilidad |
| ----- | --------------- |
| `Application` | Ciclo de la petición y renderizado central de errores |
| `Router` | Registrar rutas, resolver parámetros y ejecutar middlewares |
| `Request` | Encapsular método, ruta, query, body y datos del servidor |
| `Response` | Respuesta HTML, redirecciones y cabeceras de seguridad |
| `View` | Renderizar plantillas PHP con layout y datos |
| `Database` | Conexión PDO perezosa con excepciones y charset |
| `Session` | Iniciar sesión, leer/escribir, regenerar, expirar y destruir |
| `Auth` | Usuario autenticado, login/logout y comprobación de rol |
| `Csrf` | Generar y validar tokens |
| `Validator` | Reglas de validación y mensajes en español |
| `Flash` | Mensajes temporales de éxito, error, aviso e información |
| `OldInput` | Repoblar formularios tras un error de validación |
| `Env` | Lectura de `.env` sin dependencias |
| `Config` | Configuración con claves de punto |
| `Container` | Contenedor explícito de servicios y controladores |
| `Logger` | Registro técnico en `storage/logs` |
| `ErrorHandler` | Avisos como excepciones y manejador final |
| `Middleware\AuthMiddleware` | Exigir sesión |
| `Middleware\GuestMiddleware` | Redirigir al panel si ya hay sesión |
| `Middleware\RoleMiddleware` | Exigir un rol concreto |
| `Middleware\CsrfMiddleware` | Validar CSRF en mutaciones |
| `Exception\HttpException` | Errores HTTP con status |
| `Exception\NotFoundException` | 404 |
| `Exception\ForbiddenException` | 403 |

## Reglas

- No importar `App\Modules\...`.
- No contener reglas de negocio.
- No imprimir HTML directamente; usar `View`.
- No leer superglobales en servicios; `Request` las encapsula.
- No silenciar excepciones.
- Mantener las clases pequeñas y con una responsabilidad.
- Documentar el contrato de cada clase pública.
- Cubrir con pruebas las partes críticas: router, validación, CSRF y sesión.

## Bootstrap

`src/bootstrap.php`:

1. Carga el autoload PSR-4 y los helpers.
2. Carga `.env` y la configuración.
3. Configura zona horaria y manejo de errores.
4. Inicia la sesión con cookies seguras.
5. Construye el contenedor de servicios.
6. Registra las rutas de los módulos.
7. Devuelve la `Application` que ejecuta el front controller.

No contiene lógica de negocio.
