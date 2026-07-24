#!/usr/bin/env bash
# build-previews.sh — generate BOTH architecture/preview.html (dev) and
# architecture/preview-standalone.html (handoff) from ONE shared render core, so they render
# IDENTICALLY. The only per-file difference is the loader call. Pure bash + base64 + awk (no Python).
#
# Usage:  ARCH=/abs/path/to/architecture  ASSETS=/abs/path/to/references/preview-assets  bash build-previews.sh
set -euo pipefail
ARCH="${ARCH:?set ARCH to the architecture/ folder}"
ASSETS="${ASSETS:?set ASSETS to references/preview-assets}"
CORE="$(cat "$ASSETS/render-core.js")"
HEAD_TOP="$(cat "$ASSETS/head.html")"     # doctype .. #doc container (shared CSS + layout)
cd "$ARCH"

# --- island for the standalone (base64 so any content is safe; guard </ so it can't close the tag) ---
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
ISLAND_JSON="$(mktemp)"; printf '%s' "$ISLAND" | awk '{gsub(/<\//,"<\\/"); print}' > "$ISLAND_JSON"

# --- preview.html : shared HEAD + shared CORE + FETCH loader ---
{
  printf '%s\n' "$HEAD_TOP"
  echo '<script>'; printf '%s\n' "$CORE"
  cat <<'DEV'
document.getElementById("banner").innerHTML='<b>Architecture — live preview (dev).</b> Served over http; auto-discovers this folder. <b>Cmd/Ctrl+P → Save as PDF</b>.';
mermaid.initialize({startOnLoad:false,securityLevel:"loose",theme:matchMedia("(prefers-color-scheme:dark)").matches?"dark":"default"});
(async function(){ try{ const d=await ARCH_CORE.loadByFetch(); if(!d.length) throw new Error("no NN-*.md sections found"); await ARCH_CORE.render(d,mermaid);
}catch(e){ document.getElementById("doc").innerHTML='<div class="err">Preview build error: '+e.message+'. fetch() is blocked on file://. Serve architecture/ over http (python3 -m http.server, or PhpStorm) and open via http://.</div>'; }})();
DEV
  echo '</script></body></html>'
} > preview.html

# --- preview-standalone.html : shared HEAD + baked island + shared CORE + ISLAND loader ---
{
  printf '%s\n' "$HEAD_TOP"
  printf '<script type="application/json" id="island">'; cat "$ISLAND_JSON"; echo '</script>'
  echo '<script>'; printf '%s\n' "$CORE"
  cat <<'STD'
document.getElementById("banner").innerHTML='<b>Architecture — self-contained.</b> All docs & diagrams baked in — opens on file://, no server. <b>Cmd/Ctrl+P → Save as PDF</b>. Regenerate after edits.';
mermaid.initialize({startOnLoad:false,securityLevel:"loose",theme:matchMedia("(prefers-color-scheme:dark)").matches?"dark":"default"});
(async function(){ try{ await ARCH_CORE.render(ARCH_CORE.loadFromIsland(),mermaid);
}catch(e){ document.getElementById("doc").innerHTML='<div class="err">Render error: '+e.message+'</div>'; }})();
STD
  echo '</script></body></html>'
} > preview-standalone.html

rm -f "$ISLAND_JSON"
echo "wrote $ARCH/preview.html and $ARCH/preview-standalone.html"
# sanity: the ARCH_CORE block must be byte-identical in both
a=$(awk '/window.ARCH_CORE = /,/^\}\)\(\);/' preview.html | shasum | cut -d' ' -f1)
b=$(awk '/window.ARCH_CORE = /,/^\}\)\(\);/' preview-standalone.html | shasum | cut -d' ' -f1)
[ "$a" = "$b" ] && echo "OK: shared core identical ($a)" || { echo "FAIL: cores differ"; exit 1; }
