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

## Both files share ONE render core — this is what keeps them identical

The single most important rule, and the one that has bitten this skill before: **`preview.html` and
`preview-standalone.html` MUST embed a byte-identical render core.** If they use two separately-written
renderers they drift — different section ordering, a missing ADR, different mermaid handling — and the
"dev preview" no longer matches the "handoff file". They are the *same page fed by two content sources*,
not two pages.

**Do not hand-write either file.** Generate both with the committed builder, which splices one shared
HEAD + one shared render core + a one-line loader that is the ONLY difference between them:

- `preview-assets/head.html` — shared `<!doctype>` … `#doc` container: CDN `<script>`s, all CSS
  (theme-aware + `@media print`), the sidebar/TOC layout, and an empty `#banner`. Identical in both.
- `preview-assets/render-core.js` — the shared `window.ARCH_CORE` module: `render(docs, mermaid)`
  (ordering via `orderKey`, `renderable` filter, `.mmd` inlining, relative-link rewriting to in-page
  anchors, `DOMPurify.sanitize(marked.parse(...))`, mermaid run) **plus both loaders**:
  `loadByFetch()` (dev) and `loadFromIsland()` (handoff). Identical in both files — this is the core
  whose hash the builder asserts.
- `preview-assets/build-previews.sh` — emits both files. `preview.html` = HEAD + core + a loader that
  calls `ARCH_CORE.loadByFetch()`; `preview-standalone.html` = HEAD + a baked base64 JSON island +
  core + a loader that calls `ARCH_CORE.loadFromIsland()`. The script ends by asserting the
  `ARCH_CORE` block is byte-identical in both (`shasum`) and **fails loudly if they ever diverge**.

Run it (from anywhere) at Step 7; copy the three assets into the run cache or invoke them in place:

```bash
ARCH="<ABS>/architecture" \
ASSETS="<SKILL>/references/preview-assets" \
bash "<SKILL>/references/preview-assets/build-previews.sh"
# -> wrote architecture/preview.html and architecture/preview-standalone.html
# -> OK: shared core identical (<sha1>)
```

Both files are project-agnostic (no baked file list, no project name) — the same `head.html` +
`render-core.js` drop into any Spryker `architecture/`. Only the standalone's island is project-specific,
and the builder regenerates it from whatever `.md`/`.mmd` are on disk.

### What the shared core does (the contract — do not fork it)

- **Discovery, dev (`loadByFetch`)**: HEAD-probe `NN-<slug>.md` for `NN`=`01..20` over the arc42 slug
  set; find SD/ADR filenames by **reading the `04`/`09` folder READMEs** (never autoindex scraping —
  `python3 -m http.server` has an autoindex but PhpStorm's 63342 server and others do not, which is
  exactly how ADRs/SDs went missing). Then prefetch every `.mmd` the docs reference.
- **Discovery, handoff (`loadFromIsland`)**: base64-decode the baked island — same `{path,text}` shape
  `loadByFetch` returns, so `render()` can't tell them apart. That sameness is the whole point.
- **Ordering**: `orderKey` sorts `01..NN`, folding `sd-*`/`adr-*` right after their parent number.
- **Skips**: `README.md`, `PUBLIC-DOC-GUIDELINE.md`, `*-000-template.md`.
- **Diagrams**: replace `[..](*.mmd)` with fenced ```` ```mermaid ````, resolved by suffix match; run
  `cleanMermaid` (strip pre-`---` `%%` comments; `;`→`,`).
- **Cross-links**: relative `NN-*.md` / `sd-*.md` / `adr-*.md` → in-page `#id`; `http(s)` → new tab.
- **id per doc** = path slugified (`09-architecture-decisions/adr-002-…` → `09-architecture-decisions-adr-002-…`).
- **Sanitize** every fragment with `DOMPurify.sanitize(marked.parse(...))` (XSS-safe + satisfies hooks).
- Pin CDN versions (`marked@12`, `mermaid@11`, `dompurify@3`); keep the renderer in **one** `<script>`.

If you must edit rendering behavior, edit `render-core.js` **once** — both files pick it up and stay
identical. Never patch one file's renderer alone.

### Serve preview.html (dev)

```bash
cd <ABS>/architecture
python3 -m http.server 8912 --bind 127.0.0.1     # or PhpStorm's built-in server (works: README discovery)
# open: http://127.0.0.1:8912/preview.html
```

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
- **Skill assets (source, in the skill — not copied per run):** `references/preview-assets/head.html`,
  `render-core.js`, `build-previews.sh`. The builder reads them via `$ASSETS` and writes the two files
  directly into `architecture/`; nothing intermediate needs to land in the deliverable or a cache dir.
- **Reusability contract:** neither output file contains project-specific content or a hardcoded file
  list; the shared `head.html` + `render-core.js` drop into any Spryker `architecture/` unchanged. Only
  the standalone's baked island is project-specific, and the builder regenerates it from disk.
- **Anti-divergence guarantee:** the builder asserts (`shasum`) that the `ARCH_CORE` block is
  byte-identical in both files and fails if not — so the two previews can never silently drift apart.
