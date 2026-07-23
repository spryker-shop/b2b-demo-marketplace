# Section-writer guidance

One `§` per arc42 section: what belongs in the file, which diagram(s) pair with it, and how to fill it
from the shared inputs. A **section-writer teammate reads only its own §** plus `intake.md`, the
relevant research file(s), and its part of [architecture-depth.md](architecture-depth.md) (the content-depth bar);
the orchestrator skims the map and the shared conventions.

> **Depth bar:** every section aims for real decision-grade substance (see [architecture-depth.md](architecture-depth.md)),
> not a template fill. In particular: release-phase tags on scope/integrations, the **Components +
> Connectors** rationale tables beside diagrams, a per-phase volume table, and risk mitigations that
> cite real Spryker features. Where a `§` below points at architecture-depth.md, read that part too.

## Shared conventions (every writer follows these)

- **Replace examples, keep structure.** The template files ship illustrative content (SAP, Akeneo,
  Auth0, sample volume tables, sample diagrams). Preserve the heading structure, the `*Corresponds to
  [arc42 Section N]*` footer, and the table shapes; swap the *content* for this project's reality from
  `intake.md`. Never leave a mix of real and example content without marking the example part.
- **Ground, don't guess.** Anything about how Spryker behaves comes from `research-docs.md`; anything
  about the real running system comes from `research-current-state.md`; external facts from
  `research-web.md`. If a fact isn't in the inputs, write `> **TODO:** <what's missing and who can
  answer it>` instead of inventing it.
- **Diagrams as code — Mermaid (`.mmd`) ONLY, for every diagram type including ERDs.** Use Mermaid
  `flowchart`/`sequenceDiagram`/`erDiagram`/etc. for flow, context, sequence, data-flow, integration,
  AND entity-relationship diagrams. **Do NOT use PlantUML (`.puml`)** — it can't render client-side, so
  it's invisible in the browser preview, the exported PDF, and GitHub's Markdown renderer. Model ERDs
  with Mermaid `erDiagram` (entities with `PK`/`FK` markers and crow's-foot cardinality). Store diagram
  files under `architecture/diagrams/<category>/` and reference them from the section with a relative
  link (`[C1 System Context](diagrams/c4/c1-system-context.mmd)`) — the template uses this external-file
  approach. Match the existing example diagrams' style.
  **Harvest before you author:** if the source doc (a TAD, a brief) already embeds a diagram, start
  from it — restyle to the color convention below; **if it's PlantUML, transcribe it to Mermaid** rather
  than keeping `.puml`. Don't redraw from scratch and drift from what the architect already approved.
  Two Mermaid syntax traps to avoid so diagrams actually render: never put a `;` in note/label text
  (it's a statement separator — use `,`), and keep any `%%` comments AFTER the `---config---`
  frontmatter, never before it.
- **File-reference format.** When a section cites code, use the project's absolute-clickable-path
  convention (`/abs/path/File.php:line`).
- **Return compact.** A writer returns: files written, a 2–3 line "what I put in it", and its open
  TODOs — not the prose it wrote.

### Universal diagram color scheme (readability-first — every fill/text pair must be legible in print)

Reuse these `classDef` styles so all diagrams read as one system. **Each style below pins BOTH a fill
and a contrasting text `color:`** — this is not optional. The rule of thumb: dark fill → `color:#fff`;
light fill → an explicit dark `color:` (never rely on the default, and never white text on a light or
mid-grey fill). These values are tuned to survive black-and-white-ish PDF printing.

| Element / class | `classDef` (copy verbatim) | Meaning |
|---|---|---|
| **user / actor** — `userStyle`,`authStyle` | `fill:#e1f5ff,stroke:#0366d6,stroke-width:2px,color:#0b2545` | user/actor (light fill, **dark navy text**) |
| **system (this project)** — `systemStyle` | `fill:#FDEBD0,stroke:#CA6F1E,stroke-width:3px,color:#7E3F0C` | the system under design (**soft amber, dark brown text** — a gentle highlight, not a loud orange block) |
| **communication / storefront edge** — `communicationStyle` | `fill:#FDEBD0,stroke:#CA6F1E,stroke-width:2px,color:#7E3F0C` | comm layer / storefront edge (same soft amber) |
| **backend / API / process** — `backendStyle`,`apiStyle`,`businessStyle`,`searchStyle`,`custom` | `fill:#2980B9,stroke:#1B4F72,stroke-width:2px,color:#fff` | backend services, APIs, processes (blue, white text) |
| **web app / external web** — `webStyle`,`webAppStyle`,`presentationStyle` | `fill:#27AE60,stroke:#1D8348,stroke-width:2px,color:#fff` | web apps, external web systems (green, white text) |
| **storage** — `storageStyle`,`persistenceStyle`,`core` | `fill:#9B59B6,stroke:#6C3483,stroke-width:2px,color:#fff` | DB, cache, search (purple, white text) |
| **external / infra** — `externalStyle`,`infraStyle` | `fill:#5D6D7E,stroke:#34495E,stroke-width:2px,color:#fff` | external systems, infrastructure (**dark slate, white text** — NOT the old light `#95A5A6` grey, which washes out white text) |

Deprecated/unreadable values to avoid (they were replaced for contrast): light grey `#95A5A6` fills,
and loud orange `#E67E22`/`#CA6F1E` fills with white text. Use `layout: elk` config header for larger
flowcharts as the examples do. When restyling a harvested diagram, remap its colors to this table.

## Section → diagram map

| Section | Paired diagram(s) | Category / file |
|---|---|---|
| 03 System Scope & Context | **C1 System Context** | `diagrams/c4/c1-system-context.mmd` |
| 05 Building Block View | **C2 Container**, **C3 Component** | `diagrams/c4/c2-*.mmd`, `c3-*.mmd` |
| 06 Runtime View | **Sequence** (per scenario), optional **data-flow** | `diagrams/sequence/*.mmd`, `diagrams/data-flow/*.mmd` |
| 03/06 integrations | **Integration overview** | `diagrams/integration/*.mmd` |
| 05/persistence | **ERD** (if data model matters) | `diagrams/erd/*.mmd` (Mermaid `erDiagram`) |

---

## § 01 — Introduction and Goals

**File:** `01-introduction-and-goals.md`. **Inputs:** intake tab groups A, B; migration from C.
**Depth:** the approval/status block, general overview, and release-phased functional scope — see
[architecture-depth.md](architecture-depth.md).

- **Status/approval header (recommended):** add a short metadata block at the top — Status (e.g.
  Draft / In Review / Approved), Author/Driver, Approver(s), key dates, document version.
  Fill what the interview gave; mark the rest `TBD`.
- **Requirements Overview:** turn the one-paragraph goal + in-scope capabilities into a short bulleted
  list of functional drivers, phrased for *this* project (not the template's generic B2B bullets).
  State the business model, the legacy system being re-platformed (if any), the go-live date, and
  whether it's MVP or 100% scope.
- **Functional scope table:** list scope items with a **Release Phase** column (Phase 1 / Phase 2),
  each described in project terms.
- **Migration Requirements:** if migrating, name the source system, data entities & volumes, what's
  replaced, and the timeline. If not migrating, say "No migration — greenfield build" (don't delete
  the subsection, just state it).
- **Quality Goals:** fill the priority table with the user's ranked 3–5 goals and a concrete scenario
  per goal (these pair with section 10's measurable scenarios — keep them consistent).
- **Stakeholders:** the real roles + expectations from tab group B.

## § 02 — Constraints

**File:** `02-constraints.md`. **Inputs:** intake tab group D; conventions from CLAUDE.md.

Fill the three tables (Technical / Organizational / Conventions) with real constraints and their
*motivation* (the "why" column is the point — a constraint without a reason is noise). Hosting model,
compliance, team size, release cadence, PHPStan level, branching model. Pull Spryker/PHP conventions
from the project's own `CLAUDE.md`/`.claude/rules` where relevant.

## § 03 — System Scope and Context  ⟶ C1

**File:** `03-system-scope-and-context.md`. **Inputs:** intake B, C; `research-docs.md` (actor names,
integration patterns); `research-current-state.md` (real integrations, for existing projects).

- **Business Context:** the system and every communication partner (actors + external systems), with
  the business reason for each. Point to the C1 diagram.
- **Technical Context — External Systems table:** each external system, its responsibility, the
  technology, and stakeholders. Use the *real* products named in the interview.
- **Integration Details table:** direction, protocol, sync/async, frequency, constraints — straight
  from tab group C, corrected against docs research (e.g. confirm the real Spryker integration
  pattern for that system type). Tag each with its **Release Phase**.
- **Components + Connectors rationale tables (required):** beside the diagram, always include the
  two tables from [architecture-depth.md](architecture-depth.md) — **Components** (system / description /
  stakeholders) and **Connectors** (interaction / description / type / frequency-volume-constraints).
  Nothing important should live only inside the picture.
- **C1 diagram** (`diagrams/c4/c1-system-context.mmd`): the platform as one box, all actors and
  external systems around it with labeled edges. Follow the example's style and color classes exactly.
  For an integration-heavy project, also produce an integration overview diagram under
  `diagrams/integration/`.

## § 04 — Solution Designs (sub-workflow, only if selected)

**Dir:** `04-solution-designs/`. **Template:** `sd-000-template.md`. **Naming:** `sd-XXX-brief-title.md`.

Create a Solution Design per exploration area the user named (tab group G): major integrations,
new capabilities, significant changes. For each, copy the template and fill: metadata (status
`Draft`, today's date, author/stakeholders), problem statement, goals & requirements (functional /
non-functional / constraints), proposed solution + architecture diagrams, key components table,
integration points, data model (Propel schema / transfers where relevant), implementation plan
(phases, dependencies, rollout & cost), trade-offs, alternatives considered, open questions, related
docs. Ground Spryker-specific parts (modules, transfers, plugin contracts) in `research-docs.md`.
Update `04-solution-designs/README.md`'s list if it enumerates SDs. An SD is exploration — it's fine
for it to end with open questions.

**The Data Model section is the one part that must not be a TODO.** Reviews of real outputs found
this the #1 gap between "directional" and "buildable": draft the table sketches (key columns, FKs,
Propel-style names), the transfers, and ownership/read-write rules from whatever the inputs give —
even as a "proposed, refine at spike" draft. Only when the model genuinely can't be drafted, write
a **named spike** (question, owner, timebox) instead of a bare TODO. See
[architecture-depth.md](architecture-depth.md) ("From decision-grade to build-ready", item 2).
Where two SDs pull the underlying model in different directions (e.g. one wants live-fetched
services, another needs them as catalog products), don't just note the tension — add a **decision
gate** naming the fork, its trigger, and the ADR that will record the resolution.

## § 05 — Building Block View  ⟶ C2, C3

**File:** `05-building-block-view.md`. **Inputs:** intake B; `research-docs.md`;
`research-current-state.md` (installed modules, for existing projects).

- **C4 Level 2 (Container):** the main Spryker containers in scope (Yves, Zed, Glue, DB, Redis,
  Elasticsearch/OpenSearch, RabbitMQ, plus any project-specific service) and their responsibilities.
  Pair with `diagrams/c4/c2-*.mmd`.
- **C4 Level 3 (Component):** decompose the containers relevant to this project — for Zed, the
  standard layers (Presentation / Communication / Business / Persistence, + Service); for a custom
  domain, the real modules. Pair with `diagrams/c4/c3-*.mmd`.
- For an **existing project**, list the actual custom modules under `src/Pyz` / other namespaces
  (from current-state research), not a generic module list.
- **Size the custom domains:** give each custom module/domain row a rough t-shirt effort estimate
  (S/M/L/XL + a one-line driver) so the table plans, not just enumerates — see
  [architecture-depth.md](architecture-depth.md) ("build-ready", item 1). Mark it as a planning
  estimate; refine-at-spike is expected.

## § 06 — Runtime View  ⟶ sequences

**File:** `06-runtime-view.md`. **Inputs:** intake F (key scenarios); `research-docs.md` (how the flow
actually works in Spryker).

Document the key runtime scenarios the user picked (checkout/payment, Publish & Sync, a headline
integration flow, order fulfillment). For each: a short prose "Key Steps" list + a sequence diagram
under `diagrams/sequence/`. Confirm the real step order against docs research — Publish & Sync,
checkout, and OMS flows have canonical shapes; don't paraphrase from memory. Reuse the existing
`api-payment.mmd` / `publish-sync.mmd` examples as style templates.

## § 07 — Deployment View

**File:** `07-deployment-view.md`. **Inputs:** intake F (topology);
`research-current-state.md` (`deploy.*.yml`, infra, for existing projects).

Fill the infrastructure table and deployment pattern for the project's real hosting model — Spryker
Cloud (PaaS) vs custom Kubernetes vs on-prem/hybrid. The template flags that this section matters most
for non-standard/on-prem setups — if that's the case, go deeper (network topology, data residency,
scaling). For existing projects, read the deploy files rather than guessing.

**Multi-store setup (required when >1 store/country):** document the store strategy explicitly —
region(s), stores per country, one-DB-vs-per-store, shared codebase, and whether **Dynamic
Multi-Store** is used (note its maturity at the time). State what's shared vs separate across stores
and any data-residency/firewall constraints. A small setup diagram (Mermaid) showing region → store →
DB/Redis/ES helps. Multi-store projects should always document this store strategy explicitly.

**Integration-readiness prerequisites (integration-heavy projects):** host the single
pre-integration checklist here (or in § 08, linked from here) — outbound egress/allow-listing per
external system, credential & secret management, per-adapter timeout/retry/circuit-breaker policy —
each with a named owner (customer side + Spryker Cloud networking). This is the item most likely to
silently block integration work; collect it in one place instead of scattering TODOs. Cross-link it
from § 11. When the volume table implies serious growth, add the first-pass infra sizing here too
(projected hot-table row counts, Redis key volume, worker/queue counts) — see
[architecture-depth.md](architecture-depth.md) ("build-ready", items 1 and 5).

## § 08 — Cross-Cutting Concepts

**File:** `08-crosscutting-concepts.md`. **Inputs:** intake F (cross-cutting); `research-docs.md`.

Document the concepts that span components: auth/authz approach (incl. Spryker permission model if
relevant), logging/observability, error handling, i18n & multi-store, caching (Redis + Publish &
Sync), security patterns. Prefer real implementation examples over generic prose; small diagrams
welcome. Only include concepts that actually apply to this project.

## § 09 — Architecture Decisions (sub-workflow, only if selected)

**Dir:** `09-architecture-decisions/`. **Template:** `adr-000-template.md`. **Naming:**
`adr-XXX-brief-title.md`.

Create one ADR per decision already made (tab group G). Each is short (readable in ~5 min): Status
(`Proposed`/`Accepted`/`Implemented` + ISO date), Context (the problem + constraints), Decision (active
voice), Consequences (positive **and** negative — be honest about trade-offs). One decision per ADR.
Link related SDs/ADRs. If a decision came from a Solution Design, reference it. Update
`09-architecture-decisions/README.md`'s list if it enumerates ADRs.

## § 10 — Quality Requirements

**File:** `10-quality-requirements.md`. **Inputs:** intake tab group E. **Depth:** the extended
per-phase volume checklist in [architecture-depth.md](architecture-depth.md) — go beyond the stock template rows.

- **Volume Planning table:** fill Go-Live and Go-Live+1yr (add a +…Y column if the user gave a
  longer horizon) across all groups (catalog, cart, user/peak load, B2B customers, marketplace,
  internationalization, orders). Add the extended rows the template lacks when relevant — bundles, price
  types/dimensions, conversion rate, per-store infra split (separate RDS/Redis/ES), data import/export
  (frequency, full vs delta, push/pull, format, size/speed), shared-vs-separate data & functionality,
  data residency/firewall restrictions, historical-data migration entities & counts, staging
  environments. Ranges are fine; leave a cell `TODO` (not blank-looking-done) where the user genuinely
  doesn't know — a marked unknown is honest and actionable.
- **Testing & Environment Strategy:** CI/CD, non-prod environments, test data, external-integration
  simulation. **Anchor the load test even when concurrency is TBD:** derive an interim concurrency
  number from the figures the intake DOES give (visitors/day × conversion × seasonality; or
  sessions/month + active orgs), state it as an explicit planning assumption to be replaced on
  client confirmation, and size the peak-load test against it — an unanchored "load test TBD" is the
  gap reviewers flag every time. See [architecture-depth.md](architecture-depth.md)
  ("build-ready", item 4).
- **Quality Scenarios:** measurable stimulus/response/measure rows, consistent with section 1's
  quality goals.

## § 11 — Risks and Technical Debt

**File:** `11-risks-and-technical-debt.md`. **Inputs:** intake C (integration risks), D (constraints);
decision log OPEN QUESTIONS. **Depth:** mitigations cite real Spryker features — see the
mitigation patterns in [architecture-depth.md](architecture-depth.md).

Fill the Risks table (risk / impact / mitigation) and Technical Debt table (item / impact / plan) with
project-specific entries. Integration uncertainties, external-system delivery risks, migration risks,
high-volume import / Publish & Sync performance, large category trees, and any debt surfaced during
current-state research are prime candidates. **Where a mitigation relies on a Spryker capability, name
it and link its docs page** (Persistence ACL, Merchant Relationship, Merchant Product Restrictions,
Comments module, BO user roles, Data Import & P&S optimization guidelines, Dynamic Multi-Store, Data
Exchange API) — confirm it exists via docs research first. The run's own OPEN QUESTIONS from
`decisions.md` often map straight to rows here.

**Decision gates, not standing debt:** when the project has a hard deadline, triage the risk rows —
any *launch-blocking* unknown (data-isolation mechanism, migration cut-over/password fallback, a
provider-maturity question) gets a **pre-Go-Live decision gate**: the decision, the spike that
answers it, an owner, and a resolve-by date tied to the roadmap. Related ADRs stay `Proposed` until
their gate closes. Everything else may remain an owner-named TODO. See
[architecture-depth.md](architecture-depth.md) ("build-ready", item 3, and the gap-triage rule).

## § 12 — Glossary

**File:** `12-glossary.md`. **Inputs:** every other section's terms; `research-docs.md` (correct
Spryker definitions).

Define every domain, technical, and Spryker-specific term used across the chosen sections. Keep the
three-table split (Business / Technical / Spryker-Specific). This section is written/updated **last or
during the cross-link pass**, because it must cover exactly the terms the other sections introduced —
the orchestrator can own it at Step 5 if no dedicated writer did (in a multi-deliverable run, the
deliverable's own writer owns it as part of its cross-link pass).
