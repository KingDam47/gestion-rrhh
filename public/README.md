# public — Directorio web

Es el **único directorio que debe exponerse** en producción. Contiene el front
controller y los assets estáticos.

## Contenido previsto

```text
public/
├── index.php          # Front controller
├── .htaccess          # Rewrite al front controller y cabeceras
└── assets/
    ├── css/app.css    # Estilos propios
    └── js/app.js      # JavaScript progresivo
```

## Reglas

- No colocar código de negocio, configuración ni secretos.
- No subir `.env`, backups, logs ni archivos SQL.
- Los assets se referencian con rutas relativas a `public/`.
- Bootstrap 5 puede servirse por CDN o autoalojado; si se autoaloja, va en
  `public/assets/vendor/`.
- No habilitar listados de directorio.
- El front controller incluye la aplicación desde `../src/bootstrap.php`.

## Document root

- **Local:** apuntar Apache a `public/`.
- **InfinityFree:** el document root es `htdocs` y no se puede cambiar. El `.htaccess`
  raíz redirige las peticiones a `public/`.

## Seguridad

- El acceso directo a `src/`, `config/`, `database/`, `storage/`, `tests/`, `docs/`,
  `scripts/` y `exceso/` debe estar bloqueado por `.htaccess`.
- No confiar solo en ocultar rutas.
- Servir siempre por HTTPS en producción.
