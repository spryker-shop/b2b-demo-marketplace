# Preview the architecture in a browser + export to PDF

At the end of a run, offer the user a **localhost HTML preview** of the finished document — Markdown +
Mermaid rendered in the browser — and a one-step **Save-as-PDF**. This is optional, presentation-only,
and never touches the deliverable: the `architecture/` Markdown stays the single source of truth; the
preview is a throwaway render generated under the **run cache dir**, not committed.

Offer it in Step 7 (Gated: ask; Autonomous: mention the command so the user can run it). For a
multi-deliverable run, one preview page can list every deliverable in its sidebar.

## Why the browser is also the PDF route

Mermaid diagrams only render in a browser, so "download as PDF" always goes through one. Two paths:

1. **Manual (default, zero extra deps):** serve the page on localhost, open it, `Cmd/Ctrl+P` →
   **Save as PDF**. The page is built as **one long printable document** (all selected sections +
   diagrams concatenated, page-breaks between sections via `@media print`), so a single print gives the
   whole architecture as one PDF. This always works — recommend it first.
2. **Headless (automated, needs Chrome):** drive headless Chrome to print the same page:
   ```bash
   # after the page is served at http://127.0.0.1:PORT/arch-preview.html
   "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
     --headless --disable-gpu --no-pdf-header-footer \
     --print-to-pdf="architecture.pdf" \
     --virtual-time-budget=10000 \
     "http://127.0.0.1:PORT/arch-preview.html?print=1"
   ```
   The `virtual-time-budget` gives Mermaid time to render before printing. Use this only when the user
   wants a scripted export; otherwise the manual print is simpler and needs nothing installed.

> Not `mmdc`/`pandoc` as the primary route: they render diagrams and Markdown separately and you lose
> the single-document layout + the arc42 cross-links. The browser page keeps it one cohesive document.

## Generate the preview page (into the run cache dir, never `architecture/`)

Write a **self-contained** `arch-preview.html` under the run dir
(`.claude/.cache/architecture-prep/<run-id>/preview/`). It fetches the section `.md` files and any
referenced `.mmd` diagrams over localhost (that's why it's served, not opened directly —
`fetch()` needs http, not `file://`). Key properties:

- **Concatenate mode for print:** render every selected section in order into one scrolling document
  with a sidebar TOC; the Markdown file supplies its own top-level `#` heading, so **don't inject a
  second section title** (that double-titles every section). `@media print` hides the sidebar and
  black-on-whites the page.
- **Render Solution Designs (04) & ADRs (09) too**, not just the 12 numbered sections — the data-model
  ERDs live in the SD files, and the user wants everything visible. Their filenames vary per project,
  so **discover them** from the `python -m http.server` directory listing (parse
  `href="sd-*.md"` / `href="adr-*.md"`, skip `*-000-template.md` and `README.md`).
- **Diagrams inline & visible:** rewrite fenced ```` ```mermaid ```` blocks to `<pre class="mermaid">`,
  and inline every `[link](…/diagrams/*.mmd)` by fetching the file and rendering it in place (resolve
  `../diagrams/…` relative to the SD/ADR file's own folder). **Every diagram is Mermaid `.mmd`,
  ERDs included (`erDiagram`)** — the skill is Mermaid-only (see sections.md). The browser can't render
  PlantUML `.puml` without a server, so if you ever meet a legacy `.puml`, transcribe it to `.mmd` at
  the source so it renders here, on GitHub, and in the PDF.
- **Readable colors in print.** Diagrams must use the readability-first `classDef` palette in
  [sections.md](sections.md) — every fill paired with a contrasting text `color:` (dark fill→white
  text; light fill→dark text). Avoid light-grey fills with white text and loud full-orange blocks —
  they wash out in a printed PDF. If a harvested diagram uses those, remap it to the palette.
- **Pin CDN versions** (marked@12 + mermaid@11) and load DOMPurify; sanitize `marked.parse(md)` before
  inserting (`el.insertAdjacentHTML(..., DOMPurify.sanitize(html))`). This is the user's own local
  Markdown, but sanitizing is cheap and satisfies the safety hooks.

### Two rendering gotchas that WILL break diagrams if unhandled (verify by rendering, not by eye)

Both were hit on real TAD output; a preview that skips them shows a "Syntax error in text" bomb icon:

1. **Leading `%%` comments before the `---config---` frontmatter** — harvested `.mmd` files often carry
   provenance comments above the frontmatter, but Mermaid requires the `---` (or the diagram-type line)
   to be the *first* text. **Strip everything before the first `---`** (or leading `%%`/blank lines).
2. **A `;` inside note/label text** — Mermaid treats `;` as a statement separator and errors. **Replace
   `;` with `,`** in the diagram text before rendering (safe: these diagrams use commas in labels).

Apply both in a `sanitizeMermaid(text)` pass used for BOTH fenced blocks and fetched `.mmd` files. The
same `;`→`,` and comment issues mean the source `.mmd` files should be fixed too (they'd fail in
GitHub's renderer as well) — a preview fix that only patches the render hides a real content bug.

### Clickable links + visible diagrams in the PDF (a hard requirement)

The PDF is just the browser's print of this page, so it inherits whatever the page does — get these
right or the PDF has dead links and missing pictures:

- **In-page cross-references stay clickable.** Rewrite a relative `NN-name.md[#anchor]` link to the
  in-page anchor `#<project>/<NN-name>` (every section is on this one page). Chrome preserves live
  `<a href>` as clickable links in the PDF. Force external `http(s)` links to a new tab and keep them.
  Turn only genuinely unresolvable relative paths into plain text.
- **Diagrams don't get cut across pages:** `@media print { .mermaid, svg, tr, img { break-inside:
  avoid } }`, and let sections break naturally (`break-inside:auto`) so a long section still flows.
- **Keep link styling visible in print** (`a { color:#1558b0 !important; text-decoration:underline }`)
  and set a sane `@page { margin:14mm }`.

**The working, browser-tested implementation of all of the above is the reference renderer at the
repo root's `arch-preview.html`** (built and verified against the 3-TAD run — all 26 diagrams render,
ERDs included, links clickable). Copy its structure rather than re-deriving these fixes; it is the
canonical template for this step.

## Serve it

Serve from the **project root** (so the page's `architecture/...` fetches resolve), pick a free port,
run in the background, and hand the user the URL:

```bash
# from the project root
python3 -m http.server 8899 --bind 127.0.0.1
# then open:  http://127.0.0.1:8899/.claude/.cache/architecture-prep/<run-id>/preview/arch-preview.html
```

(For a multi-deliverable run whose docs live in per-deliverable worktrees, either serve each worktree
root on its own port, or copy the rendered sections into one preview dir — the sidebar then groups by
deliverable, as the 3-TAD run did.)

Tell the user plainly: **the URL is a live preview; `Cmd/Ctrl+P` → Save as PDF gives the whole
document as one file.** Remind them the server is a background process and how to stop it
(`kill <pid>`), and that the preview dir is throwaway scratch — the committed deliverable is the
Markdown under `architecture/`.
