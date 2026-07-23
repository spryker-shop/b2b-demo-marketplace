# TAD fast-path — a structured architecture brief IS the intake

A Spryker **Target Architecture Definition (TAD)** — the structured Confluence page shape used by
Spryker Professional Services — already contains, in a fixed structure,
essentially every answer the Step 1 interview seeks. The same is true of any comparably structured
architecture brief a client or partner provides. Running the interview on top of one is redundant
work for the user and pure token cost for the run — and when the user has asked for no questions,
it's impossible.

**Rule: when the intake includes one or more TADs (or an equivalently structured brief), treat each
TAD as that project's complete `intake.md` and skip the interview entirely.** Only fall back to the
batched interview when no structured document exists, and only ask follow-ups for facts the TAD
genuinely lacks *and* that block a selected section (in Autonomous mode, don't ask — mark the TODO).

## Getting the TAD

- Confluence link + Atlassian MCP connected → pull the page (and any child pages it links for
  diagrams/tables). If the MCP isn't available, ask the user to paste/export the page.
- Save the pulled content verbatim as `intake-<slug>.md` (or `intake.md` for a single-deliverable
  run) in the run directory, then annotate it with the mapping below so writers know where each part
  lands. Don't rewrite or summarize the TAD — writers should read the source tables, not a lossy
  paraphrase.

## TAD section → arc42 mapping

The mapping is nearly 1:1. Writers use this to find their inputs; the orchestrator uses it to check
the TAD actually covers the selected sections.

| TAD section | Feeds arc42 |
|---|---|
| Metadata / status header (driver, approver, dates, RAG) | `01` status/approval block |
| General Overview (business model, re-platforming, go-live, MVP-vs-100%, delivery approach) | `01` Requirements Overview |
| Functional requirements table (usually with a phase column) | `01` functional scope table (keep the phase tags) + `03` |
| Non-functional requirements / volumes | `10` volume planning table (Go-Live / +1Y / +…Y) |
| Integrations table | `03` External Systems + Integration Details tables |
| Connectors / interaction details | `03` **Connectors** rationale table — often usable near-verbatim |
| Target Architecture Diagram | `03` C1 — **harvest it, don't redraw** (see below) |
| Challenges / Risks tables | `11` risks + the seed list for ADRs/SDs — TAD mitigations often already name Spryker features |
| Multi-store / infrastructure setup | `07` deployment + multi-store strategy |
| Implementation Roadmap | `01`/`10` phasing |
| Open Questions | carried **verbatim** into `11` / the consolidated TODO list — never silently answered |

## Harvest diagrams-as-code from the source

TADs frequently embed a full Mermaid C1 (or similar) in the page body. If the source doc contains
any diagram-as-code, **reuse it as the starting point** — restyle it to the project's universal
color convention (see sections.md) rather than authoring a new diagram from scratch and risking
drift from what the architect already drew. Note in the diagram file header where it came from.

## What a TAD usually does NOT contain

Know the typical gaps so you mark them as TODOs (with owners) instead of hunting or inventing:
effort/estimates and team capacity, concurrent-user counts, per-adapter timeout/retry policy,
PHPStan level / team conventions, environment sizing, historical-migration record counts. These are
exactly the items the depth bar (architecture-depth.md) says to handle with interim assumptions,
spikes, or owner-named TODOs.
