# Registro de decisiones de arquitectura (ADR)

Un **ADR** (Architecture Decision Record) documenta una decisión técnica relevante,
su contexto y sus consecuencias. Sirve para que el equipo entienda por qué se eligió
algo y qué implicaría cambiarlo.

## Cuándo crear un ADR

- Elección de stack, framework, base de datos u hosting.
- Cambios en las fronteras entre módulos.
- Modelo de autenticación o autorización.
- Estrategia de datos, migraciones o backups.
- Cualquier decisión difícil de revertir o con impacto en seguridad/coste.

No hace falta un ADR para cambios locales, estilo o detalles reversibles.

## Ciclo de vida

| Estado | Significado |
| ------ | ----------- |
| `Propuesto` | En discusión; aún no se implementa |
| `Aceptado` | Decisión vigente |
| `Rechazado` | Se descartó |
| `Reemplazado por ADR-XXXX` | Sustituido por una decisión posterior |
| `Obsoleto` | Ya no aplica |

## Plantilla

Crear un archivo `docs/adr/000X-titulo-en-kebab-case.md`:

```markdown
# ADR-000X: Título

- **Estado:** Propuesto | Aceptado | Rechazado | Reemplazado por ADR-XXXX
- **Fecha:** YYYY-MM-DD
- **Decisores:** nombres o roles

## Contexto

Situación, restricciones y problema que se debe resolver.

## Opciones consideradas

### Opción A

Ventajas y desventajas.

### Opción B

Ventajas y desventajas.

## Decisión

Qué se decide y por qué.

## Consecuencias

- Positivas:
- Negativas:
- Riesgos y mitigaciones:

## Enlaces

Referencias, spikes o documentación relacionada.
```

## Índice

| ADR | Título | Estado | Fecha |
| --- | ------ | ------ | ----- |
| [0001](0001-stack-propuesto.md) | Stack inicial propuesto | Rechazado (reemplazado por 0002) | 2026-10-25 |
| [0002](0002-stack-php-mysql.md) | Stack PHP, MySQL y monolito modular | Aceptado | 2026-10-25 |

## Reglas

- Un ADR aceptado no se edita para cambiar la decisión: se crea uno nuevo que lo reemplaza.
- Se puede corregir redacción o añadir enlaces, manteniendo el histórico.
- La fecha es la de la decisión, no la de la última edición.
- Toda decisión aceptada debe reflejarse en la documentación y en el código.
