# Documentación

Esta carpeta es la fuente de verdad del proyecto. Si el código y la documentación
no coinciden, se abre un issue y se corrige la diferencia.

## Índice

| Documento | Propósito | Actualizar cuando |
| --------- | --------- | ----------------- |
| [01-vision-y-alcance.md](01-vision-y-alcance.md) | Problema, usuarios, alcance y criterios de éxito | Cambie el objetivo o el alcance |
| [02-arquitectura.md](02-arquitectura.md) | Capas, front controller, reglas y stack | Se tome una decisión técnica |
| [03-modulos.md](03-modulos.md) | Fronteras y dependencias entre módulos | Cambie un módulo o su contrato |
| [04-convenciones.md](04-convenciones.md) | PSR-12, naming, SQL, Git y Definition of Done | Se acuerden nuevas reglas |
| [05-roadmap.md](05-roadmap.md) | Fases, tareas y entregables | Se cierre o replanifique una fase |
| [06-deploy-y-dominio.md](06-deploy-y-dominio.md) | Entorno local, InfinityFree, dominio y backups | Cambie el hosting o el flujo de despliegue |
| [07-seguridad-y-privacidad.md](07-seguridad-y-privacidad.md) | Sesiones, CSRF, roles y datos sensibles | Cambien roles, datos o controles |
| [08-modelo-de-datos.md](08-modelo-de-datos.md) | Diagrama ER, tablas, estados y restricciones | Cambie el esquema o una regla de datos |
| [adr/](adr/README.md) | Decisiones de arquitectura | Se tome una decisión relevante |

## Reglas de documentación

- Escribir en español; los identificadores del código van en inglés.
- Mantener cada documento enfocado en un tema.
- Toda decisión técnica relevante se registra en un ADR.
- No incluir datos reales de empleados en ejemplos, pruebas ni capturas.
- Usar fechas absolutas (`2026-10-25`), no expresiones relativas.
- Los documentos describen el estado real del proyecto, no solo la intención.
