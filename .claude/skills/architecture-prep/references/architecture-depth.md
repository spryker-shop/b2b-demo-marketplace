# Depth bar — make the arc42 output a document an architect would sign off

The single biggest failure mode is a thin template fill: headings kept, cells left generic. This file
captures the *content depth* every section should reach so the result is a real, decision-grade
architecture document — expressed as version-controlled Markdown + diagrams-as-code. Read it alongside
`sections.md`; every writer should aim for this bar.

The depth below is what turns "the template, lightly edited" into "a document the team can build
from": release-phased scope, a Components + Connectors rationale pair beside every architecture
diagram, a rich per-phase volume table, an explicit multi-store strategy, and risk mitigations that
name real Spryker capabilities.

## Where the depth lands

When a section is selected, pull in the matching depth.

| Concern | Lands in arc42 | Depth to reach |
|---|---|---|
| Status / approval header | top of `01` (short block) | Status (e.g. Draft / In Review / Approved, optionally RAG), Author/Driver, Approver(s), key dates, document version |
| General overview | `01` Requirements Overview | Business + model (B2C / B2B / marketplace), objectives (often re-platforming from a named legacy system), go-live date, MVP-vs-full scope, delivery approach |
| Functional scope | `01` + `03` | Scope-item table **with a Release Phase column** (Phase 1 / Phase 2), each item described in project terms |
| Non-functional / volume | `10` | Full volume table with **Go-Live / +1Y / +…Y** columns — see the extended checklist below |
| Integrations | `03` external systems + `06` | System + function + **release phase**; then the two rationale tables below |
| Architecture diagram | `03` C1 + `05` C2/C3 + integration diagram | Plus **Components** and **Connectors** rationale tables (below) |
| Risks & challenges | `11` | Risk / impact / **mitigation that names a real Spryker feature or docs page** |
| Implementation roadmap | `01` or `10` | Phased timeline (Phase 1 → go-live → Phase 2) |
| Infrastructure / multi-store | `07` | Region / store / DB / codebase strategy; **Dynamic Multi-Store** when many countries |

## The two rationale tables (always pair these with the architecture diagram)

Never ship a diagram alone — pair it with two tables so nothing important lives only inside a picture.
Reproduce both in section 03 (and/or 05):

**Components** — one row per system on the diagram:

| System (type, name) | Description (what it provides / accepts) | Stakeholders |
|---|---|---|

**Connectors** — one row per interaction/edge on the diagram:

| Interaction | Description | Type | Frequency / Data volume / Constraints |
|---|---|---|---|

Use arrows in the Interaction cell (`Spryker → ERP`, `Spryker ↔︎ PSP`) matching the diagram edges, and
a real "Type" (REST API, SOAP, SFTP/XML files, JS Widget, Redirect, AMQP, OAuth, VPN DB link). The
Constraints cell is where the numbers go ("~60k products, ~50 imported / 10 min", "4000–6000
messages/day") — this is what makes the doc useful for sizing, so fill it whenever the interview gave a
number and mark `TBD` otherwise (an honest `TBD` is fine and expected).

## Extended volume checklist (section 10 — go beyond the bare template table)

Size across **Go-Live / +1Y / +…Y** and cover more than the stock template. Add rows the template
lacks when relevant:

- Products (active / abstract), concretes-per-abstract, **bundles**, categories (root/sub), **price
  types & dimensions** (standard / RRP / sale / customer-specific), offers.
- Cart: avg & max item lines. Merchants (if marketplace).
- Loads: seasonality, visitors/sessions, orders/day, **conversion rate**, concurrent
  customers / BO users / MP merchants.
- Customers, users, merchants counts.
- **Country expansion:** regions, countries, stores-per-country, languages, currencies — per phase.
- **Per-store infra split:** separate RDS / Redis / ES, or shared?
- **Data import/export:** frequency, full vs **delta**, push vs pull, format, size & speed (e.g.
  "delta every 10 min ≈ 50 products", "full XML on S3, import < 30 min").
- **Shared vs separate** data & functionality across stores.
- **Data residency & firewall restrictions** (multi-store).
- **Historical data migration:** which entities, how many (customers, orders, returns, passwords…).
- Staging environments.

## Mitigations name real Spryker capabilities (section 11 + Solution Designs)

What separates a strong Spryker architecture doc from a generic one: risks are mitigated with **named
Spryker features**, and the writer confirms they exist via `spryker-docs-research`. Common patterns:

- Market/store data isolation for BO users → **Persistence ACL**.
- Dealer/branch hierarchy, customer-approval-before-order, merchant-specific pricing → **Merchant
  Relationship** + **Merchant Product Restrictions** + custom BO user **roles**.
- Order comments between customer and merchant → **Comments** module (+ OMS comments integration).
- Large-file / high-volume import + P&S → Spryker **Data Import optimization** & **Publish & Sync**
  best-practice guidelines (split files on middleware, CTE-based import, don't proxy files through
  Spryker — upload to S3).
- Many countries/stores under one domain → **Dynamic Multi-Store** (note maturity/EA status).
- External DB access for a DWH/BI → **Data Exchange API** or a site-to-site VPN DB connection
  (Spryker Cloud constraint).

When a mitigation names a feature, link its real docs page (get the URL from docs research) so the doc
is actionable.

## Release phasing

Tag scope items and integrations with **Phase 1 / Phase 2** (and note Go-Live vs Public Go-Live if
they differ). Phase 1 = what ships at go-live; later phases = the expansion vision (e.g. "marketplace
later", "more countries in year 2"). Carry the phase into the roadmap and into the volume table's
+1Y / +…Y columns so the whole document tells one consistent story.

## From decision-grade to build-ready (the review-proven gaps)

Independent architect reviews of real outputs from this skill rated them decision-grade but found
the same five gaps in every document. A delivery lead needs these on day 1 — reach for them whenever
the inputs allow, and where they don't, produce the bounded/interim form rather than nothing:

1. **Size, don't just sequence.** Phase-tagged scope tables sequence the work; none of it is sized.
   Add a rough **t-shirt effort estimate per custom module** (S/M/L/XL with a one-line driver) in
   §5's custom-domain table or the SDs, and — where the intake gives volumes — a **first-pass infra
   sizing** in §7/§10: projected row counts for the hot tables (`spy_product_abstract`,
   `spy_price_product_store`, price-dimension rows), Redis key volume for storefront data,
   worker/queue counts for the import path. A bounded estimate marked "planning estimate, refine at
   spike" is the deliverable; a bare `TODO` where numbers were derivable is the failure. When the
   volume table shows a large jump (e.g. 300k → 26M products on a shared DB/Redis), *work the
   implication through* — that jump is usually the central scaling decision of the whole document,
   and naming it as a risk without sizing it leaves the topology question unanswered.
2. **A Solution Design's Data Model section is mandatory content, not a TODO.** The schema is
   precisely the part a developer cannot start without: table sketches with key columns and FKs
   (Propel-style naming), the transfers, and ownership/read-write rules. A directional SD without a
   data model is not yet buildable. If the model genuinely can't be drafted from the inputs, replace
   the TODO with a **named spike** (question to answer, owner, timebox) — that's a plan, not a gap.
3. **Promote launch-blocking unknowns to decision gates.** Honest `UNCERTAIN`/`TODO` flags are
   right, but for a project with a hard deadline the launch-blocking ones (a data-isolation
   mechanism, a migration cut-over/password fallback, a payment-provider maturity question) must not
   sit as standing tech debt. Give each a **pre-Go-Live gate**: the decision to make, the spike that
   answers it, an owner, and a resolve-by date tied to the roadmap. This lands in §11 (and the ADR
   stays `Proposed` until the gate closes).
4. **Anchor load/capacity tests with explicit interim assumptions.** When concurrency targets are
   TBD, the peak-load test is unsized and stays unsized. Derive an interim planning number from
   what IS given (visitors/day × conversion × seasonality skew; sessions/month + active orgs) and
   state it in §10 as an **explicit assumption to be replaced on client confirmation** — that
   unblocks sizing now without pretending the client confirmed anything.
5. **One integration-readiness checklist, not scattered TODOs.** External systems are the top risk
   of nearly every project, and the items most likely to silently block integration work are the
   unglamorous prerequisites: outbound egress/allow-listing per system, credential & secret
   management, and a per-adapter timeout/retry/circuit-breaker policy. Collect them into a single
   first-class **pre-integration checklist** with named owners (customer side + Spryker Cloud
   networking) in §7 or §8, cross-linked from §11 — not as one-line footnotes per section.

## Honest status over false completeness

A `> **TODO:**` / `TBD` naming the open question and who owns it is a first-class, professional outcome
— far better than a fabricated number or an invented integration. Strong architecture docs are full of
named gaps; that is what makes them trustworthy.

Triage every gap into one of three honest forms, though — they are not interchangeable:
- **Client-owned unknown** → `TODO` with an owner (the classic case).
- **Launch-blocking unknown** → a decision gate with spike/owner/date (item 3 above).
- **Derivable-but-unconfirmed number** → a stated interim assumption (item 4 above).
Defaulting everything to the first form is how a document ends up sequenceable but not plannable.
