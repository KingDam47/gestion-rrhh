# exceso — Material conservado fuera del runtime

Esta carpeta conserva material que no participa en la aplicación y que se retiró de
`src/` durante la limpieza. **No se carga con el autoload y no debe desplegarse.**

## Contenido

- `_template/`: plantilla histórica para crear módulos. Se conserva como referencia,
  pero los módulos actuales ya están implementados y la plantilla no se usa en runtime.

Si el material deja de ser útil, puede eliminarse esta carpeta completa sin afectar a
la aplicación.
