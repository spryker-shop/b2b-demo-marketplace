/* ===== SHARED RENDER CORE — byte-identical in preview.html AND preview-standalone.html =====
   The ONLY difference between the two files is loadDocs(): fetch (dev) vs baked island (standalone).
   Everything below — ordering, mermaid inlining, link rewriting, TOC, sanitize — is identical,
   so both files render exactly the same. */
window.ARCH_CORE = (function(){
  const SLUGS = ["introduction-and-goals","constraints","system-scope-and-context","solution-designs",
    "building-block-view","runtime-view","deployment-view","crosscutting-concepts",
    "architecture-decisions","quality-requirements","risks-and-technical-debt","glossary"];

  function idFor(p){ return p.replace(/\.md$/,"").replace(/[^a-z0-9]+/gi,"-").replace(/^-|-$/g,"").toLowerCase(); }
  function orderKey(p){ const s=p.toLowerCase(); const m=s.match(/(?:^|\/)(\d\d)[-\/]/);
    const base=m?parseInt(m[1],10):99; const sub=/\/(sd|adr)-\d/.test(s)?1:0;
    return String(base*10+sub).padStart(4,"0")+"_"+s; }
  function renderable(p){ if(!p.endsWith(".md")) return false;
    const b=p.split("/").pop().toLowerCase();
    return b!=="readme.md" && b!=="public-doc-guideline.md" && !/-000-template\.md$/.test(b); }
  function cleanMermaid(t){ const L=t.replace(/\r/g,"").split("\n"); let i=0;
    while(i<L.length && (/^\s*%%/.test(L[i])||/^\s*$/.test(L[i]))) i++;
    return L.slice(i).join("\n").replace(/;/g,",").trim(); }

  // docs: [{path, text}] for .md AND .mmd. Renders into #doc + #toc. Identical for both files.
  async function render(docs, mermaid){
    const byMmd = new Map(docs.filter(f=>f.path.endsWith(".mmd")).map(f=>[f.path,f.text]));
    const linkMap = new Map(docs.filter(f=>f.path.endsWith(".md")).map(f=>[f.path,"#"+idFor(f.path)]));
    const md = docs.filter(f=>renderable(f.path)).sort((a,b)=>orderKey(a.path)<orderKey(b.path)?-1:1);
    const doc=document.getElementById("doc"), toc=document.getElementById("toc");
    doc.innerHTML=""; toc.innerHTML="";
    for(const f of md){
      let src=f.text;
      src=src.replace(/\[[^\]]*\]\(([^)]+\.mmd)\)/g,(w,rel)=>{ const c=rel.replace(/^(\.\.\/|\.\/)+/,"");
        for(const [p,t] of byMmd){ if(p.endsWith(c)||p.endsWith(c.split("/").pop()))
          return "\n```mermaid\n"+cleanMermaid(t)+"\n```\n"; }
        return "\n> _(diagram not found: "+rel+")_\n"; });
      src=src.replace(/\]\((\.{0,2}\/?[^)#]*\.md)(#[^)]*)?\)/g,(w,path)=>{ const k=path.replace(/^(\.\.\/|\.\/)+/,"");
        for(const [p,a] of linkMap){ if(p.endsWith(k)) return "]("+a+")"; } return w; });
      const sec=document.createElement("section"); sec.className="section"; sec.id=idFor(f.path);
      sec.innerHTML=DOMPurify.sanitize(marked.parse(src));
      sec.querySelectorAll("a[href^='http']").forEach(a=>a.target="_blank");
      doc.appendChild(sec);
      const a=document.createElement("a"); a.href="#"+sec.id; a.textContent=f.path.replace(/\.md$/,""); toc.appendChild(a);
    }
    doc.querySelectorAll("code.language-mermaid").forEach(c=>{ const pre=c.closest("pre")||c;
      const d=document.createElement("div"); d.className="mermaid"; d.textContent=c.textContent; pre.replaceWith(d); });
    try{ await mermaid.run({querySelector:".mermaid"}); }catch(e){ console.error("mermaid",e); }
  }

  // ---- content source A: live fetch (preview.html / dev) ----
  async function loadByFetch(){
    async function grab(u){ const r=await fetch(u); if(!r.ok) throw new Error(u+" -> "+r.status); return r.text(); }
    async function exists(u){ try{ const r=await fetch(u,{method:"HEAD"}); if(r.ok) return true; }catch(e){}
      try{ return (await fetch(u)).ok; }catch(e){ return false; } }
    async function listByReadme(dir,re){ try{ const md=await grab(dir+"README.md");
      return [...new Set([...md.matchAll(/\(([^)]+\.md)\)/g)].map(m=>m[1].split("/").pop())
        .filter(f=>re.test(f)&&!/-000-template\.md$/.test(f)))].sort(); }catch(e){ return []; } }
    const out=[];
    for(let n=1;n<=20;n++){ const nn=String(n).padStart(2,"0");
      for(const s of SLUGS){ const f=nn+"-"+s+".md"; if(await exists("./"+f)){ out.push({path:f,text:await grab("./"+f)}); break; } } }
    for(const [dir,re] of [["04-solution-designs/",/^sd-.*\.md$/],["09-architecture-decisions/",/^adr-.*\.md$/]]){
      for(const f of await listByReadme(dir,re)){ if(await exists(dir+f)) out.push({path:dir+f,text:await grab(dir+f)}); } }
    // diagrams referenced by the docs are fetched lazily inside render() via byMmd? No — prefetch them:
    const mmdRefs=new Set();
    for(const d of out){ for(const m of d.text.matchAll(/\(([^)]+\.mmd)\)/g)) mmdRefs.add(m[1].replace(/^(\.\.\/|\.\/)+/,"")); }
    for(const rel of mmdRefs){ try{ out.push({path:rel,text:await grab("./"+rel)}); }catch(e){} }
    return out;
  }
  // ---- content source B: baked island (preview-standalone.html / handoff) ----
  function loadFromIsland(){
    function b64d(b64){ const bin=atob(b64); const b=new Uint8Array(bin.length);
      for(let i=0;i<bin.length;i++)b[i]=bin.charCodeAt(i); return new TextDecoder("utf-8").decode(b); }
    const raw=JSON.parse(document.getElementById("island").textContent);
    return raw.map(d=>({path:d.path,text:b64d(d.b64)}));
  }
  return { render, loadByFetch, loadFromIsland };
})();
