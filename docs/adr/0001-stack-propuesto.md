# ADR-0001: Stack inicial propuesto (rechazado)

- **Estado:** Rechazado — reemplazado por [ADR-0002](0002-stack-php-mysql.md)
- **Fecha:** 2026-10-25
- **Decisores:** Equipo del proyecto y responsable de RRHH

> **Nota histórica:** esta propuesta se evaluó al inicio del proyecto, cuando el stack
> aún no estaba definido. El proyecto universitario requiere PHP/MySQL y el hosting
> InfinityFree no admite Node.js, por lo que se adopta el stack del ADR-0002. Se
> conserva este documento para recordar las alternativas evaluadas.

## Contexto

Se va a construir una aplicación web interna de RRHH con tres módulos por definir.
Restricciones y características:

- Hosting gratuito y dominio propio.
- Equipo pequeño.
- Código limpio, modular y mantenible.
- Autenticación y permisos por roles.
- Datos personales sensibles.
- Necesidad de iterar rápido y validar flujos con usuarios.
- Presupuesto inicial cercano a cero, con posibilidad de migrar a un plan de pago.

Se necesita una base que permita autenticación, PostgreSQL, formularios, pruebas,
despliegues de preview y una ruta clara de salida si cambian los límites del hosting.

## Criterios de evaluación

1. Coste inicial cero y uso comercial permitido.
2. Dominio propio con HTTPS y previews por PR.
3. TypeScript de extremo a extremo.
4. Ejecución segura en servidor (no exponer lógica ni secretos en el cliente).
5. PostgreSQL estándar y migraciones versionadas.
6. Curva de aprendizaje razonable para el equipo.
7. Comunidad, documentación y mantenimiento activos.
8. Portabilidad: evitar dependencias propietarias difíciles de revertir.
9. Testing y accesibilidad soportados por el ecosistema.

## Opciones consideradas

### Opción A — Next.js full-stack + PostgreSQL gestionado

- Next.js (App Router) + TypeScript + Tailwind CSS + shadcn/ui.
- PostgreSQL en Neon o Supabase.
- Drizzle ORM.
- Auth.js o Better Auth (a validar en un spike).
- Zod, React Hook Form, Vitest, Testing Library y Playwright.
- Despliegue en Netlify Free o Cloudflare Pages.

Ventajas: un solo lenguaje, SSR, ecosistema amplio, buen soporte de previews,
lógica sensible en servidor y módulos fáciles de aislar.

Desventajas: acoplamiento al framework; el hosting gratuito tiene límites; Next.js
en Cloudflare requiere adaptación; Auth.js/Better Auth deben validarse.

### Opción B — React + Vite SPA + Supabase

- React + Vite + TypeScript + Tailwind.
- Supabase para base de datos, autenticación y storage.
- Despliegue estático en Netlify/Cloudflare Pages.

Ventajas: simplicidad de despliegue, menos infraestructura, rápido de arrancar.

Desventajas: la lógica vive más en el cliente; reglas complejas requieren Edge
Functions; mayor dependencia de Supabase; permisos a nivel de fila (RLS) que
requieren cuidado; SSR limitado.

### Opción C — Next.js + Supabase como backend

- Next.js para UI y servidor.
- Supabase para PostgreSQL, auth y storage.

Ventajas: buena integración y menos código de infraestructura.

Desventajas: dos fuentes de autenticación si no se integra bien; dependencia de
servicios y límites de Supabase; posible duplicación de reglas entre RLS y servidor.

## Decisión propuesta

Adoptar la **Opción A**, sujeta a un spike técnico y a la aprobación del equipo:

- Next.js con App Router y TypeScript en modo `strict`.
- Tailwind CSS + shadcn/ui para una base accesible y consistente.
- PostgreSQL (Neon como primera opción; Supabase como alternativa).
- Drizzle ORM y migraciones versionadas.
- Auth.js como primera opción, con Better Auth evaluado en el spike.
- Zod para validación y React Hook Form para formularios.
- Vitest + Testing Library para unitarias/integración y Playwright para E2E.
- pnpm, ESLint y Prettier.
- Netlify Free como hosting inicial; Cloudflare Pages como alternativa.
- GitHub Actions para CI.

La arquitectura modular descrita en [02-arquitectura.md](../02-arquitectura.md) se
mantiene independiente del framework: el dominio no importa Next.js ni la base de datos.

## Consecuencias

### Positivas

- Un solo lenguaje y un solo despliegue.
- Autenticación y autorización en servidor.
- Buen ecosistema para formularios, testing y accesibilidad.
- Despliegues de preview y dominio propio sin coste inicial.
- Fronteras de módulos claras y fáciles de probar.

### Negativas

- Acoplamiento inicial a Next.js.
- Los planes gratuitos pueden pausar la base de datos o limitar funciones.
- La elección de Auth.js/Better Auth aún tiene incertidumbre.
- Next.js en Cloudflare añade complejidad frente a Netlify.

### Riesgos y mitigaciones

| Riesgo | Mitigación |
| ------ | ---------- |
| Límites del hosting gratuito | Medir uso, monitorizar y definir plan de salida |
| Base de datos pausada | Alertas y plan de pago si la app es crítica |
| Acoplamiento al framework | Dominio puro y adaptadores en infrastructure |
| Auth incompatibilidad | Spike de login, sesiones y RBAC antes de aprobar |
| Cambios de términos | Revisión trimestral y proveedor alternativo documentado |

## Pendiente

- [ ] Ejecutar un spike de 2–3 días: login, RBAC, una entidad, migración y deploy con dominio.
- [ ] Verificar los términos y límites vigentes de Netlify y Cloudflare.
- [ ] Comparar Auth.js y Better Auth con los requisitos reales.
- [ ] Estimar coste de migración a plan de pago.
- [ ] Aprobar la decisión y cambiar el estado a `Aceptado`.

## Enlaces

- [Arquitectura propuesta](../02-arquitectura.md)
- [Hosting y dominio](../06-deploy-y-dominio.md)
- [Seguridad y privacidad](../07-seguridad-y-privacidad.md)
