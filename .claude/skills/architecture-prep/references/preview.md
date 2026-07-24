# Preview the architecture in a browser + export to PDF

Two preview files, **both written into the `architecture/` folder** and **both fully
project-agnostic** (no baked-in content, no per-project edits — the same two files work in any
Spryker project's `architecture/`, just copy them):

| File | Mode | Reads docs by | Server? | `file://` double-click? | Use |
|---|---|---|---|---|---|
| `architecture/preview.html` | **dev / live** | `fetch()` at runtime | **yes** | no | working on the docs — edit `.md`, refresh, see it. No regeneration. |
| `architecture/preview-standalone.html` | **compiled / handoff** | content baked inside | no | **yes** | share/archive/PDF at the end — one self-contained file, opens anywhere. |

The `architecture/` Markdown is always the single source of truth. `preview.html` renders it live
(needs a server). `preview-standalone.html` is a **compiled snapshot** built **on demand at the end**
(re-run the builder after edits). Copy `preview.html` in from day one; produce `preview-standalone.html`
only when you want a shareable/offline artifact.

Offer/generate in Step 7 (Gated: ask first; Autonomous: write `preview.html`, and compile the
standalone as part of handover). For a multi-deliverable run, both files live in each worktree's
`architecture/`.

---

## Why two files (the `file://` vs server tradeoff — do not try to collapse it)

A browser **blocks `fetch()` on `file://`** (CORS/same-origin). So a single HTML file can either:
- read the folder live (needs `fetch()` → **needs a server**; can't double-click), **or**
- be double-clicked from disk (**content must be baked in** → it's a compiled snapshot, not live).

Those two are mutually exclusive by browser rule — there is no single file that both opens on
`file://` and reads the current folder. Hence one file for each need. (`<script src>` is *not*
blocked on `file://`, which is why the baked file works: its content ships inside the page, not
fetched.) Don't waste a run trying to make one file do both — it isn't possible.

---

## File 1 — `architecture/preview.html` (dev, served, reusable)

A single static page you **copy unchanged** into any `architecture/`. On load it **auto-discovers**
the docs in its own folder and renders them — no hardcoded file list, no manifest.

**Discovery must be server-agnostic.** Do NOT discover by scraping a directory-listing/autoindex page:
`python3 -m http.server` serves an autoindex, but **PhpStorm's built-in server (port 63342) and many
others do not** — autoindex scraping silently finds nothing and the ADRs/SDs go missing. Instead:

- **Top-level sections:** probe `NN-<slug>.md` for `NN` = `01..20` against the known arc42 slug set
  (`introduction-and-goals`, `constraints`, `system-scope-and-context`, `solution-designs`,
  `building-block-view`, `runtime-view`, `deployment-view`, `crosscutting-concepts`,
  `architecture-decisions`, `quality-requirements`, `risks-and-technical-debt`, `glossary`) with a
  `HEAD` request (fall back to `GET`); render the ones that respond `200`.
- **SDs / ADRs:** **read the folder README** (`04-solution-designs/README.md`,
  `09-architecture-decisions/README.md`) and extract the linked filenames
  (`/\(([^)]+\.md)\)/`, keep `sd-*`/`adr-*`, drop `*-000-template.md`). Every Spryker arc42 SD/ADR
  folder ships a README that indexes its docs, so this works on **any** static server. Numeric probe
  (`sd-001.md`, `adr-001.md`, …) is the fallback if a README is absent.
- **Diagrams:** replace each `[label](….mmd)` link with a fenced ```` ```mermaid ```` block by
  `fetch()`ing the `.mmd` relative to the section file, running `clean_mermaid` (see gotchas).
- **Cross-links:** rewrite relative `NN-name.md[#…]` / `sd-…md` / `adr-…md` to in-page `#anchor`s;
  keep `http(s)` links (open in new tab).
- Render Markdown with `marked`, **sanitize with `DOMPurify.sanitize(marked.parse(...))`** (XSS-safe
  + satisfies repo safety hooks), diagrams with `mermaid`.

Serve the `architecture/` folder (so relative fetches resolve) and open over http:

```bash
cd <ABS>/architecture
python3 -m http.server 8912 --bind 127.0.0.1     # or PhpStorm's built-in server, or any static server
# open: http://127.0.0.1:8912/preview.html
```

It renders **only over http** — the in-page error banner says so and points at the serve command.

---

## File 2 — `architecture/preview-standalone.html` (compiled, no server, one file)

Built **on demand at the end** of a run. All selected sections + SDs + ADRs + every `.mmd` diagram
are baked into one file that opens on `file://` by double-click — nothing beside it, nothing fetched
from disk. The only network need is the mermaid/marked/DOMPurify CDN (first open; then browser-cached).

### Build it with a pure-Bash builder (no Python, no build dependency)

The skill runs a shell; use it. The builder base64-encodes each `.md`/`.mmd` into a JSON island
(base64 sidesteps all HTML/JS escaping traps), then the page decodes and renders on load. Write the
builder to the run cache dir, run it, then delete it — the **output** file is the only committed
artifact.

```bash
# build-standalone.sh  — run from anywhere; ARCH points at the deliverable folder. Pure bash + base64.
ARCH="<ABS>/architecture"
OUT="$ARCH/preview-standalone.html"
cd "$ARCH"

# 1) Collect docs into a JSON island: selected sections in order, then SDs, then ADRs, then diagrams.
#    (List ONLY the sections written this run; base64 avoids every escaping problem.)
ISLAND=$(
  echo "["
  first=1
  for f in $(ls -1 [0-9]*.md 2>/dev/null | sort) \
           $(ls -1 04-solution-designs/sd-*.md 2>/dev/null | grep -v '000-template' | sort) \
           $(ls -1 09-architecture-decisions/adr-*.md 2>/dev/null | grep -v '000-template' | sort) \
           $(find diagrams -name '*.mmd' 2>/dev/null | sort); do
    b64=$(base64 < "$f" | tr -d '\n')
    if [ $first -eq 1 ]; then first=0; else echo ","; fi
    printf '{"path":"%s","b64":"%s"}' "$f" "$b64"
  done
  echo "]"
)

# 2) Write the template with an __ISLAND__ placeholder (see tpl below), then substitute.
#    Guard </ -> <\/ so no embedded "</script>" closes the island early. Pure awk, no Python.
printf '%s' "$ISLAND" | awk '{gsub(/<\//,"<\\/"); print}' > /tmp/arch-island.json
awk 'NR==FNR{isl=isl $0 ORS; next} {sub(/__ISLAND__/, isl); print}' /tmp/arch-island.json "$ARCH/tpl.html" > "$OUT"
rm -f /tmp/arch-island.json
echo "wrote $OUT"
```

> The island is base64 so the section Markdown can contain any characters (backticks, `</script>`,
> quotes) without breaking the page. The renderer base64-decodes each entry at load. The `<\/` guard
> is still applied because base64 alphabet never contains `<`, but the template's own JS must not
> accidentally emit a literal `</script>` either — keep the renderer as one `<script>` block.

### The template (`tpl.html`, one `__ISLAND__` placeholder)

Same look as `preview.html`; the only difference is **it reads the JSON island, not `fetch()`** — so
there is zero runtime fetch and it opens on `file://`. It: base64-decodes each entry, orders sections
`01..NN` with SDs/ADRs folded after their number, skips `README.md`/`PUBLIC-DOC-GUIDELINE.md`/
`*-000-template.md`, inlines `.mmd` links as fenced mermaid (resolved by suffix match against the
baked diagrams), rewrites relative `.md` links to in-page anchors, and renders with
`DOMPurify.sanitize(marked.parse(...))`.

Key renderer contract (keep these — they are what the NORMA run verified):
- `id` per doc = the path slugified (`09-architecture-decisions/adr-002-…` → `09-architecture-decisions-adr-002-…`); the cross-link rewriter maps relative `.md` targets to those ids.
- `clean_mermaid`: strip any `%%` comment lines that precede the `---` frontmatter, and replace `;`→`,` in labels (both otherwise show Mermaid's "Syntax error" bomb).
- Pin CDN versions (`marked@12`, `mermaid@11`, `dompurify@3`); keep DOMPurify.
- One `<script>` for the renderer (no stray `</script>` in emitted strings).
- Theme-aware CSS (`prefers-color-scheme`) and `@media print` page-breaks between sections so
  `Cmd/Ctrl+P → Save as PDF` yields the whole document as one PDF.

> Use the NORMA `architecture/preview-standalone.html` produced on the reference run as the canonical
> template — copy its `<head>`+`<style>`+renderer verbatim and only swap the JSON island. It is the
> validated recipe; don't re-derive the renderer from scratch.

### Export to PDF

1. **Manual (default, zero deps):** open `preview-standalone.html`, `Cmd/Ctrl+P` → **Save as PDF**.
   One long printable document (page-breaks between sections via `@media print`).
2. **Headless (scripted):**
   ```bash
   "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
     --headless --disable-gpu --no-pdf-header-footer \
     --print-to-pdf="architecture.pdf" --virtual-time-budget=10000 \
     "file:///ABS/PATH/architecture/preview-standalone.html"
   ```
   `virtual-time-budget` gives Mermaid time to render before printing.

> Not `mmdc`/`pandoc`: they render diagrams and Markdown separately and lose the single-document
> layout + arc42 cross-links. The browser page keeps it one cohesive document.

---

## Two rendering gotchas that WILL break diagrams if unhandled (fix at the SOURCE too)

Both show a "Syntax error in text" bomb icon; `clean_mermaid` handles them at build time, but fix the
source `.mmd` as well (they also fail on GitHub):

1. **Leading `%%` comments before the `---config---` frontmatter** — Mermaid needs `---` (or the
   diagram-type line) to be the *first* text. Strip everything before it.
2. **A `;` inside a note/label** — Mermaid treats `;` as a statement separator and errors. Replace
   `;` with `,`.

---

## Verify by rendering — with a caveat

Confirm the diagrams and all docs render; don't trust by eye.

- **`preview.html` (http):** serve the folder and render-verify in the browser — this IS reachable by
  the Chrome automation (`mcp__claude-in-chrome__navigate` to the `http://127.0.0.1:…` URL). Read the
  sidebar TOC and assert every selected section **plus every SD and ADR** is listed (the classic bug
  is missing SDs/ADRs when discovery falls back to autoindex on a server that has none). Check the
  console for errors.
- **`preview-standalone.html` (file://):** the Chrome automation extension **refuses `file://` URLs**,
  so render-verify it by **serving it over http temporarily** (byte-identical render logic) OR by
  **static validation**: parse the JSON island back out, confirm it is valid JSON with the expected
  entry count, base64-decode a sample, and confirm each embedded ```` ```mermaid ```` block's first
  non-`%%` line is a valid header (`flowchart`/`graph`/`sequenceDiagram`/`erDiagram`/`classDiagram`/
  `stateDiagram`/`---`) with no stray `;` and no pre-frontmatter `%%`.

Always stop any test server you start (`pkill -f "http.server <port>"`).

---

## What's committed vs scratch

- **Committed (deliverable), both in `architecture/`:** `preview.html` (copy-only, from the start) and
  `preview-standalone.html` (compiled on demand at handover; a regenerable snapshot — re-run the
  builder after editing sections).
- **Scratch (run cache, never committed):** `build-standalone.sh` and `tpl.html` under
  `.claude/.cache/architecture-prep/<run-id>/preview/`. Delete after the build.
- **Reusability contract:** neither file contains project-specific content or a hardcoded file list.
  The same two files drop into any Spryker `architecture/` folder unchanged.
