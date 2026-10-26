# Despliegue, hosting y dominio

## 1. Entornos

| Entorno | Propósito | URL | Base de datos |
| ------- | --------- | --- | ------------- |
| Local | Desarrollo y pruebas | `http://localhost` | MySQL local |
| Producción | Demostración y entrega | Dominio propio | MySQL de InfinityFree |

No se usan datos reales en ninguno de los entornos. La base local se puede borrar y
recrear con el esquema y el seed.

## 2. Entorno local

### Requisitos

- PHP 8.2 o superior (objetivo 8.4, compatible con InfinityFree).
- Extensiones: `pdo_mysql`, `mbstring`, `openssl`, `session`, `json`.
- MySQL 8 o MariaDB 11.
- Apache con `mod_rewrite` o el servidor integrado de PHP para pruebas básicas.
- Un cliente de base de datos (phpMyAdmin, DBeaver o similar).

XAMPP o Laragon incluyen Apache, PHP y MySQL. En Windows, Laragon permite cambiar el
document root a `public/` con mayor facilidad; XAMPP también puede configurarse con un
Virtual Host.

> En este equipo **no hay una instalación permanente de PHP ni MySQL**. La Fase 1 se
> verificó con binarios portátiles (PHP 8.4 y MariaDB 11.4). Para el trabajo diario se
> recomienda instalar XAMPP o Laragon y dejar el entorno estable.

### Puesta en marcha prevista

1. Copiar `.env.example` a `.env`.
2. Crear la base `gestion_rrhh` con `utf8mb4`.
3. Importar `database/schema.sql`.
4. Importar `database/seed.sql` (solo desarrollo).
5. Configurar el document root en `public/`.
6. Ajustar `DB_*` y `APP_URL` en `.env`.
7. Crear el primer administrador con `scripts/create-admin.php` o con el hash indicado
   en el seed.
8. Abrir la aplicación y comprobar el login.

El archivo `.env` no se versiona. El `.htaccess` raíz permite desplegar sin cambiar el
document root, pero en local se recomienda apuntar Apache directamente a `public/`.

### Servidor integrado de PHP

Para una prueba rápida sin Apache:

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

Abrir `http://127.0.0.1:8000`. El front controller sirve los assets existentes y
gestiona las rutas de la aplicación. Este servidor es solo para desarrollo.

### Pruebas automatizadas

```bash
composer install
composer test
```

Composer solo se usa en desarrollo para PHPUnit. El runtime no requiere `vendor/`.

## 3. InfinityFree

### Características relevantes

- Hosting gratuito con PHP y MySQL.
- No permite Node.js ni cambiar el document root.
- No hay SSH ni Composer CLI; tampoco cron jobs en el plan gratuito.
- El dominio principal sirve desde `htdocs`.
- Se pueden usar dominios propios y certificados SSL gratuitos.
- Las versiones de PHP/MySQL deben confirmarse en el panel al crear la cuenta.
- Los términos, límites y suspensiones por inactividad deben revisarse antes de publicar.

La estrategia del proyecto es subir el repositorio completo a `htdocs` y usar un
`.htaccess` raíz para redirigir todas las peticiones a `public/`, que contiene el
front controller y los assets. Los archivos sensibles quedan fuera de `public/` y se
bloquean con reglas de Apache.

### Pasos de despliegue

1. Crear la cuenta y el dominio en InfinityFree.
2. Verificar en el panel la versión de PHP y el soporte de `.htaccess`.
3. Crear una base de datos MySQL y anotar:
   - host,
   - nombre de la base,
   - usuario,
   - contraseña,
   - puerto si se indica.
4. Subir el proyecto por FTP (FileZilla) o por el administrador de archivos a `htdocs`.
5. Comprobar que el `.htaccess` raíz redirige a `public/`.
6. Crear el `.env` de producción en la raíz con `APP_DEBUG=false` y
   `SESSION_SECURE=true`.
7. Importar `database/schema.sql` con phpMyAdmin.
8. Crear el primer administrador. No importar `database/seed.sql` en producción salvo
   que sea una demo controlada.
9. Configurar el dominio propio en el panel y apuntar los DNS.
10. Activar/verificar el certificado SSL.
11. Probar login, navegación, permisos, CRUD y reportes.
12. Descargar un backup de la base y de los archivos.

### Despliegue real (26/10/2026)

| Dato | Valor |
| ---- | ----- |
| URL | `https://evolvehr.freehosting.dev` |
| Hosting | InfinityFree, plan gratuito, subdominio temporal |
| Document root | `htdocs` con `.htaccess` raíz hacia `public/` |
| PHP | 8.4.27 (`apache2handler`) |
| Motor de base | MariaDB 11.4.13 |
| Host MySQL | `sql311.infinityfree.com` |
| Base y usuario | `if0_43278720_rrhr` / `if0_43278720` |
| Zona horaria de la app | `UTC` |

Procedimiento aplicado:

1. Se generó el `.env` de producción en local con `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_URL=https://evolvehr.freehosting.dev` y
   `SESSION_SECURE=true`.
2. Se subieron 139 archivos por FTP a `htdocs` (runtime + `.env`), sin `vendor/`,
   `tests/`, `docs/`, `scripts/`, `exceso/`, `database/` ni READMEs.
3. Como el MySQL gratuito no acepta conexiones externas, el esquema y el seed se
   importaron con un instalador PHP temporal protegido por token. Después se borraron
   el instalador y los `.sql` del hosting.
4. Se verificó el bloqueo de `.env`, `src/`, `config/`, `storage/` y `database/`, el
   forzado de HTTPS y la carga de assets y reportes.

Notas del hosting:

- El primer acceso muestra un reto JavaScript (`aes.js`) y luego carga la aplicación.
- No hay SSH, Composer CLI, cron ni conexiones MySQL externas.
- Reimportar `database/seed.sql` devuelve la demo a sus líneas base y a las
  contraseñas demo.

### Archivos que no deben subirse

- `.env` de desarrollo.
- `database/seed.sql` (salvo demo).
- `tests/`, `docs/`, `scripts/`, `exceso/` y `.github/` (no son necesarios en producción).
- `vendor/` y `.phpunit.cache/` (solo se usan para pruebas locales).
- Logs y backups.
- Credenciales en texto plano.

### Archivos sensibles y protección

El `.htaccess` raíz bloquea el acceso directo a `src/`, `config/`, `database/`,
`storage/`, `tests/`, `docs/`, `scripts/` y `exceso/`. Además, cada carpeta sensible
puede incluir un `.htaccess` con `Require all denied` como defensa adicional. El archivo
`.env` no debe estar dentro de `public/`.

## 4. Variables de entorno

| Variable | Ejemplo local | Secreta | Uso |
| -------- | ------------- | ------- | --- |
| `APP_NAME` | `Gestión RR. HH.` | No | Nombre visible |
| `APP_ENV` | `local` / `production` | No | Entorno |
| `APP_DEBUG` | `true` / `false` | No | Errores detallados solo en local |
| `APP_URL` | `http://localhost` | No | URL base |
| `APP_TIMEZONE` | `America/Mexico_City` | No | Zona horaria de presentación |
| `DB_HOST` | `127.0.0.1` | Sí | Servidor MySQL |
| `DB_PORT` | `3306` | No | Puerto |
| `DB_DATABASE` | `gestion_rrhh` | Sí | Nombre de la base |
| `DB_USERNAME` | `root` | Sí | Usuario |
| `DB_PASSWORD` | vacío en local | Sí | Contraseña |
| `SESSION_NAME` | `gestion_rrhh_session` | No | Nombre de cookie |
| `SESSION_SECURE` | `false` / `true` | No | Cookie solo HTTPS |
| `SESSION_LIFETIME` | `120` | No | Minutos de inactividad |

Nunca prefijar secretos como públicos ni imprimir el contenido de `.env`.

## 5. Dominio propio

1. Elegir y registrar el dominio (Cloudflare Registrar, Namecheap, Porkbun, etc.).
2. Activar renovación automática y verificar el titular de la empresa o del estudiante.
3. Añadir el dominio en InfinityFree.
4. Configurar los nameservers o registros DNS indicados.
5. Esperar la propagación.
6. Verificar HTTPS y la redirección de `www`.
7. Actualizar `APP_URL` y las URLs internas si corresponde.
8. Documentar registrador, fecha de renovación y responsables.

Checklist:

- [ ] Dominio registrado y vigente.
- [ ] DNS apuntando a InfinityFree.
- [ ] HTTPS válido sin advertencias.
- [ ] `www` y dominio raíz son consistentes.
- [ ] `APP_URL` actualizado.
- [ ] Correo del administrador del dominio accesible.

## 6. Flujo de despliegue

```text
Desarrollo local
  → Pruebas (PHPUnit + prueba manual)
    → Backup de producción
      → Subida por FTP
        → Migraciones SQL pendientes
          → Prueba de humo
            → Registro del despliegue
```

Checklist post-despliegue:

- [ ] La portada carga por HTTPS.
- [ ] Login y logout funcionan.
- [ ] Cada rol ve solo lo permitido.
- [ ] Los formularios validan y guardan.
- [ ] Los reportes coinciden con la base.
- [ ] No hay errores en `storage/logs`.
- [ ] El backup previo existe y se puede restaurar.

## 7. Backups

- Exportar la base desde phpMyAdmin antes de cada despliegue importante.
- Descargar los archivos modificados o el proyecto completo.
- Guardar los backups fuera del hosting y con acceso restringido.
- Probar una restauración antes de la entrega.
- No incluir `.env` con credenciales en el mismo paquete que se comparte.
- Retención recomendada: 7 copias diarias, 4 semanales y 3 mensuales.

## 8. Riesgos del hosting gratuito

| Riesgo | Impacto | Mitigación |
| ------ | ------- | ---------- |
| Suspensión por inactividad | Alto | Mantener actividad y revisar correos |
| Límites de recursos | Medio | Optimizar consultas, assets y sesiones |
| Sin SSH/Composer | Medio | Autoload propio y despliegue FTP |
| Sin cron | Medio | Tareas manuales o desde la app |
| Sin entorno de staging | Medio | Probar en local y usar backup antes de subir |
| Cambios de términos o versiones | Medio | Revisar el panel y documentar la versión |
| Pérdida de datos | Alto | Exportaciones periódicas y restauración probada |
| Dominio mal configurado | Medio | Checklist de DNS y SSL |

## 9. Referencias

- InfinityFree: <https://www.infinityfree.com/>
- Panel de cliente: <https://dash.infinityfree.com/>
- Foro de soporte: <https://forum.infinityfree.com/>
- Document root y `.htaccess`: <https://forum.infinityfree.com/t/how-to-change-the-root-directory/88574>
- PHP: <https://www.php.net/manual/es/>
- MySQL: <https://dev.mysql.com/doc/>
- Bootstrap 5: <https://getbootstrap.com/docs/5.3/>
