# AGENTS.md — Instrucciones para agentes (Claude Code, Codex, Cursor, Antigravity/Gemini y cualquier otro)

Este archivo aplica a **cualquier agente de IA en cualquier workspace** que trabaje en este repositorio.

> [!IMPORTANT]
> Las reglas generales del proyecto están en [`CLAUDE.md`](CLAUDE.md) y **son obligatorias para todos los agentes**, no solo para Claude: protección de la base de datos (nunca borrar registros, nunca `migrate:fresh`/`refresh`/`db:wipe`/`truncate`), `APP_KEY` y datos cifrados, autenticación de git con `GITHUB_TOKEN`, presentar un plan antes de modificar y no hacer commits sin autorización. Léelo antes de empezar.

---

## Bóveda de documentación `vault/` (Obsidian)

`vault/` es una bóveda de Obsidian con la documentación completa y viva del proyecto: arquitectura, módulos, flujos, modelo de datos, permisos, correos/PDFs/Excel, integraciones, operación (local y producción), decisiones (ADRs) e historial de cambios. Punto de entrada: [`vault/00 Inicio.md`](vault/00%20Inicio.md).

### Regla obligatoria: actualizar la bóveda en cada cambio

**Después de cada modificación, ajuste o cambio al proyecto** (código, migración, configuración, dependencia, permiso, correo, reporte, procedimiento de operación o despliegue), el agente **debe actualizar la bóveda en el mismo cambio y en el mismo commit**. No hay cambio "terminado" si la bóveda no lo refleja.

Lista de verificación (aplica la que corresponda):

1. **Nota del módulo afectado** (`vault/02 Modulos/…`): reglas de negocio, pantallas, rutas, validaciones y correos que cambiaron.
2. **Flujos** (`vault/03 Flujos/…`): si cambia un estado, una transición, un aprobador o un correo del flujo, actualiza la tabla y el diagrama Mermaid.
3. **Datos** (`vault/04 Datos/…`): si hay migración, actualiza la nota del modelo en `04 Datos/Modelos/<Clase>.md`, `Modelo de datos` (diagrama ER) y `Migraciones`. Un modelo nuevo lleva su propia nota.
4. **Permisos** (`vault/05 Permisos/…`): roles, permisos de Shield, policies o middleware nuevos o modificados.
5. **Salidas** (`vault/06 Salidas/…`): correos (`Catalogo de correos`), `PDFs`, `Exportaciones Excel` y `Rutas` (toda ruta nueva o modificada).
6. **Arquitectura, integraciones y operación** (`01`, `07`, `08`): cambios de stack, versiones, configuración, comandos/scheduler, deploy, respaldos o infraestructura.
7. **Decisión** (`vault/09 Decisiones/`): si el cambio implica una decisión de diseño o de operación, crea un ADR con [`Plantillas/Plantilla decision`](vault/Plantillas/Plantilla%20decision.md) y enlázalo en `Indice de decisiones`.
8. **Historial** (`vault/10 Historial/Historial AAAA-MM.md`, mes en curso): agrega una entrada con [`Plantillas/Plantilla historial`](vault/Plantillas/Plantilla%20historial.md). Si el mes no existe, crea la nota y enlázala en `Indice del historial`. El sha corto se completa después del commit (o en el siguiente).
9. Actualiza el campo `actualizado:` del frontmatter de cada nota que toques. Si cambiaste la estructura de la bóveda, actualiza `00 Inicio`.

Si un cambio no requiere documentación (por ejemplo, solo formato con Pint), dilo explícitamente al reportar el trabajo.

### Formato de las notas

- **Español**, técnico y conciso. Documenta lo que el código hace hoy, verificado leyendo el código. Si algo no está claro, usa un callout `> [!question] Por confirmar`.
- **Frontmatter obligatorio:** `tipo`, `aliases` (título con acentos), `tags` (`payment-request/<area>`), `fuentes` (rutas reales del código) y `actualizado` (AAAA-MM-DD).
- **Nombres de archivo** sin acentos ni ñ (con espacios); el título `#` y los `aliases` sí llevan acentos. Las notas de modelos se llaman igual que la clase (`InvestmentPaymentRequest.md`).
- **Enlaces internos** con `[[wikilinks]]` al nombre exacto de la nota (sin ruta). Enlaces externos con `[texto](url)`.
- Referencia los archivos de código en backticks: `app/Models/Strategy.php`, `Strategy::amountsFor()`.
- Usa tablas, callouts (`> [!info]`, `> [!warning]`, `> [!danger]`) y diagramas Mermaid. En Mermaid pon el texto entre comillas: `A["Envío del lote"]`.
- No dejes notas huérfanas: cada nota nueva debe enlazarse desde su índice o desde una nota relacionada, y terminar con una sección `## Relacionado`.

### Contenido PROHIBIDO en la bóveda

La bóveda se versiona en GitHub y se despliega a producción. **Nunca** escribas en ella:

- Contraseñas, tokens, API keys, `APP_KEY`, cadenas `base64:`, llaves SSH ni rutas o huellas de llaves.
- Valores de `.env` (solo nombres de variables). No leas `.env` completo.
- Nombres de personas reales, correos, RFCs, montos o folios reales de producción.
- Correcciones de datos hechas en producción con sus casos reales (solo se documenta el procedimiento genérico en `08 Operacion/Correcciones de datos en produccion`).

Usa roles ("el PM", "el CEO") y ejemplos ficticios.

### Skills de Obsidian

El repo incluye skills en `.claude/skills/` (formato Agent Skills, de [kepano/obsidian-skills](https://github.com/kepano/obsidian-skills), licencia MIT):

| Skill | Úsalo para |
|---|---|
| `obsidian-markdown` | Escribir notas con wikilinks, callouts, propiedades y embeds válidos. |
| `obsidian-bases` | Crear vistas tipo base de datos (`.base`) sobre las notas. |
| `json-canvas` | Crear diagramas `.canvas`. |
| `obsidian-cli` | Operar la bóveda desde la terminal cuando Obsidian está abierto. |

Los agentes que no cargan skills de `.claude/skills/` (Codex, Cursor, Antigravity) pueden leer el `SKILL.md` correspondiente como referencia de sintaxis.

### Archivos de Obsidian

- Se versionan: `vault/.obsidian/app.json`, `core-plugins.json` y `templates.json`.
- No se versionan (en `.gitignore`): `vault/.obsidian/workspace*.json`, `vault/.obsidian/cache` y `vault/.trash/`.
- No instales plugins de la comunidad en la bóveda sin autorización del responsable del proyecto.
