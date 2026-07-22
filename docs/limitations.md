# Limitations & Roadmap

## Search Tool

- **`rules` is raw text, not a struct**: real FormRequest rules mix plain strings, arrays, and PHP expressions (`'unique:users,email,'.$userId`). Parsing that into clean JSON without executing the file would mangle the common cases — the source text is kept per field instead.
- **`interface_fields` only captures the first `interface`/`type` in a file** with multiple — the `exports` list already covers every name, but field detail stays shallow for the rest.
- **`defineProps<NamedType>()` (a separate named type) isn't resolved** — only the inline forms (`defineProps<{ ... }>()` and `defineProps([...])`) are captured. Resolving the named type would require cross-referencing the interface declaration elsewhere in the file (or an import), out of scope for the current parser.
- **A loose gap between anchor and opening delimiter is a real trap**: an early version of `defineProps`/`useForm` matched the wrong occurrence of the anchor word inside an unrelated `import` line, because the gap (`.*?`) skipped ahead to the first parenthesis anywhere in the file. Fixed by requiring `$afterPattern` to reach the delimiter directly (whitespace-only gap) wherever the anchor could appear loose elsewhere — a wide gap is only safe when the anchor is syntactically unique in the file (e.g. `function rules()`).
- **No incremental cache**: `build()` always scans the whole project. For very large projects, an incremental index (only re-indexing files with a changed mtime) would be the natural next step.

## Database Tool

- **`runQuery()` executes raw SQL**: the caller decides what runs — the tool only guarantees a single statement, an allowed type (SELECT/WITH always, INSERT/UPDATE/DELETE only with `allow_write_queries` on), and a row cap. No DDL/DCL (`CREATE`/`DROP`/`GRANT`/...) under any configuration.
- **`describeTable()` covers one table per call**, and Spatie Permission's "teams" feature (`model_has_roles.team_id` etc.) isn't mapped — only the plain roles↔permissions graph.
