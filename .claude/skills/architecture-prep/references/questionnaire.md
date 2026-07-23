# Architecture intake questionnaire (the fillable question list)

This is the canonical list of questions the architecture-prep skill needs answered to fill an
`architecture/` folder to decision-grade depth WITHOUT interviewing you live. It is distilled from
real Spryker TADs (the same fields a Target Architecture Definition captures) and refined by gap
audits against the Scania, Daimler, and NORMA TADs so a full answer set is enough for a fully
autonomous run.

## Three ways to use it
1. **Pre-fill and skip the interview.** Copy this file, answer inline under each question, and give
   the skill the filled copy (a path, or paste). If every REQUIRED question is answered, the skill
   runs with NO interview - exactly like the TAD fast-path.
2. **Partial fill.** Answer what you know, leave the rest blank. The skill reads your answers and
   the interview asks ONLY the still-blank questions.
3. **Interactive.** Give nothing; the skill either hands you this whole list to fill at your own pace,
   or walks you through it in batched questions - your choice.

However it is collected, EVERY answer is written to the run's input artifact (`intake.md`) with its
source noted, so the section writers all read the same grounded input.

## How to answer
- Plain, short answers. A number, a name, a yes/no, one or two sentences. Ranges are fine.
- "unknown" / "TBD" is a VALID answer - it becomes an honest, owner-named TODO in the doc, not a
  fabricated value. Do not guess.
- Every question is tagged `[REQUIRED]` or `[optional]`, and shows `-> feeds arc42 section NN`.
- `[REQUIRED]` = the skill cannot write a grounded document without it (or an explicit "unknown").
- Questions are grouped A-J (+ run-config R). If a whole group does not apply (e.g. no external
  systems, single-store, no multi-tier org), write "none" or "n/a" once at the group top.
- Groups C and the per-integration questions repeat PER external system - a small table is ideal.

---

## Group A - Project frame  (-> section 01)
A1. `[REQUIRED]` What is this project, in one sentence? (What are you building and for whom?)
A2. `[REQUIRED]` Is it a brand-new build (greenfield), or changes to an existing running system?
A3. `[REQUIRED]` Business model: B2B, B2C, B2B2C / marketplace, or D2C?
A4. `[REQUIRED]` Is it a full production build, an MVP, or a POC (proof of concept)?
A5. `[REQUIRED]` Are you re-platforming from an existing system? If yes, name the old system AND why
    now (end-of-life / support ending, cost, missing capability, scale). -> also feeds 01 objectives
A6. `[REQUIRED]` Target go-live date (or "unknown"). If technical and public go-live differ, give both.
A6b. `[optional]` If technical and public go-live differ, what happens in the window between them
    (soft launch, data load, integration cut-over, pilot)? -> feeds sections 01, 10
A7. `[REQUIRED]` Pick and rank your top 3-5 quality goals: performance, scalability, maintainability,
    security, time-to-market, cost, availability, on-time-low-risk-go-live.
A8. `[optional]` Frontend approach: Spryker Yves (traditional), headless/API-first, or both (and any
    long-term plan to change)?

## Group B - Scope and stakeholders  (-> sections 01, 03, 05, 12)
B1. `[REQUIRED]` List the main capabilities/features IN scope. Examples: catalog, cart, checkout, OMS,
    quotes/RFQ, pricing, promotions, search, CMS, asset management, returns (portal + carrier labels),
    order splitting / multi-shipment, drop-shipment (incl. via the marketplace data model),
    merchant/dealer onboarding, product-feed generation.
B2. `[REQUIRED]` List anything explicitly OUT of scope (now, or "later phase").
B3. `[REQUIRED]` Which user types/actors are in scope? (Customer, B2B Company User, Back Office User,
    Merchant/Merchant Portal user, Agent, API consumer, other.)
B4. `[optional]` For each phase, which capabilities land in Phase 1 (go-live) vs later phases?
    (If you do not phase, say "all Phase 1".)
B5. `[optional]` Key stakeholders and what each expects from the system.
B6. `[optional]` Returns handling: is there a customer-facing returns portal (native or custom-built in
    Yves)? Which carriers issue return labels (DHL, DPD, GLS, ...)? -> feeds sections 01, 03, 06
B7. `[optional]` For each in-scope CUSTOM capability/module, any known build-complexity/effort signal
    (rough S/M/L/XL, or "unknown"). The skill sizes this itself; this only captures a hint if you have
    one. -> feeds section 05 t-shirt sizing

## Group C - External systems and integrations  (-> sections 02, 03, 06, 11)
Answer once per external system. If none: write "none - native Spryker only".
C1. `[REQUIRED]` List every external system to integrate, with its role. Cover the usual kinds if
    present: ERP, PIM, CIAM/identity, PSP/payment, tax, OMS/fulfilment, search, CDN, middleware
    (ETL / service bus), CRM/marketing, returns carriers, DWH/BI, address verification, consent
    management, tag manager, analytics / behaviour analytics, A/B testing & experimentation, product
    recommendations, order-confirmation / post-purchase tools, conversion tracking API, CAPTCHA / bot
    protection, dynamic FAQ / help, form builders, product-feed generation. (Integration-heavy B2C
    projects often have 15-25; list them all, JS widgets too.)
C2. `[REQUIRED]` For EACH system: which real product is it? (e.g. SAP, Plentymarkets, Akeneo, Auth0,
    Adyen, Payone, Talend, Emarsys, SiteSearch360, Usercentrics, Kameleoon, DHL - or "TBD".)
C3. `[REQUIRED]` For EACH system: direction (Spryker pulls / pushes / both), protocol (REST, SOAP,
    GraphQL, file via SFTP or S3, AMQP, webhook, JS widget, OAuth redirect, linkout/redirect
    round-trip, VPN DB link), and sync-or-async. If several systems serve the SAME role (e.g. two
    stock providers), describe the selection/fallback rule.
C4. `[REQUIRED]` For EACH system: classify it - storefront JS widget (client-side in Yves, no Spryker
    backend module), backend integration (server-side: REST/SOAP/file/AMQP/ACP/DB-link), or both.
    (Widgets are architecturally different - consent-gated, perf-budgeted, no backend module.)
    -> feeds sections 02, 03, 11
C5. `[optional]` For EACH system: frequency + data volume + any constraints (rate limits, batch
    windows, messages/day). Numbers here make the doc size-able; "TBD" is fine.
C6. `[optional]` For EACH system: who owns it (you/customer, integration partner, 3rd party). And -
    `[REQUIRED] if the project is phased` - in which phase it is needed (Phase 1 / later).
C7. `[REQUIRED]` How do users authenticate? Native Spryker login, or an external IdP/SSO/social login?
    If external, which provider, and is it storefront login, headless/API login, or Back Office?
    (Note: Spryker storefront SSO is supported; Glue/API-level SSO has gaps - the skill will flag it.)
C8. `[optional]` If an external IdP/SSO is used: on first login of an unknown user, does Spryker
    auto-create (JIT-provision) the customer from the IdP payload, or must the user pre-exist? What
    happens if creation fails (hard error / retry / silent skip)? -> feeds sections 06, 09
C9. `[optional]` For any identity/IdP system: what fields does its payload carry (name, email, company,
    country, roles, entitlements / purchased products)? Do its roles map to Spryker Company Roles, and
    1:1 or many:1? -> feeds sections 03, 08, 09
C10. `[optional]` Are any cart line items NOT real catalog products (services, quotes, configured or
    bundled items created on the fly)? If so, where does their price come from (external API at
    add-to-cart, at checkout, or both), and is the price locked when added or re-validated before
    order? -> feeds sections 04, 06, 09
C11. `[optional]` Does Spryker fire outbound notifications/calls to any external system from OMS state
    transitions (order created, paid, shipped...)? For each: how many, at which state, and delivery
    guarantee (fire-and-forget vs retried/guaranteed)? -> feeds sections 06, 03
C12. `[optional]` For EACH system: is the API spec/contract available now, or still TBD from the owner?
    Is there an existing/reference implementation you can reuse or reverse-engineer? -> feeds 03, 11
C13. `[optional]` Is there a consent-management platform, and which storefront widgets are gated behind
    consent? Is there a frontend performance budget for third-party scripts (max added page-load ms,
    or a script-count limit)? -> feeds sections 08, 10, 11
C14. `[optional]` Does any external system (DWH/BI/reporting) require direct database-level access to
    Spryker data (vs API)? Spryker has no native external-DB connection; the skill will flag
    site-to-site VPN / Data Exchange API options. -> feeds sections 03, 04, 11
C15. `[optional]` Are any CORE storefront functions delegated to an EXTERNAL system rather than owned
    by Spryker - product search, catalog browsing, category tree, filtering, or pricing? For each:
    which function, which system, and what minimal data does Spryker still hold locally (e.g. "names +
    price only")? (This inverts the standard architecture - the skill treats it as a headline decision.)
    -> feeds sections 03, 05, 06, 09

## Group D - Migration  (-> section 01, maybe a Solution Design)
If not migrating: write "no migration - greenfield".
D1. `[REQUIRED]` Which data entities must be migrated? (products, customers, orders, open/in-flight
    orders, returns, carts, encrypted passwords, price/rebate classes, dealer-customer relations...)
D2. `[REQUIRED]` Roughly how many of each? (counts or ranges, or "unknown".) `[REQUIRED when
    re-platforming]` - a 20-year re-platforming cannot be sized without these.
D3. `[optional]` Full one-time load, or delta/incremental, or full-built-on-delta? Source format?
D4. `[optional]` Migration timeline / cut-over constraints. `[REQUIRED when re-platforming]`.

## Group E - Constraints and conventions  (-> sections 02, 07, 08)
E1. `[REQUIRED]` Hosting model: Spryker Cloud (PaaS), self-hosted Kubernetes, on-prem, or hybrid?
E2. `[REQUIRED]` Compliance/legal constraints: GDPR, PCI, data residency, country-specific rules?
E3. `[optional]` Mandated technologies or legacy systems that cannot change.
E4. `[optional]` Browser/device support requirements.
E5. `[optional]` Team size, delivery method (e.g. Scrum, sprint length), release cadence, hard
    deadlines/milestones.
E6. `[optional]` Coding conventions: PHPStan level, coding standard, branching model. (Defaults
    pulled from the project's CLAUDE.md if not given.)
E7. `[optional]` Who owns integration prerequisites - outbound egress / allow-listing, secret &
    credential management, per-adapter timeout/retry/circuit-breaker policy? (customer side vs Spryker
    Cloud networking) -> feeds sections 07/08 integration-readiness checklist

## Group F - Volumes and quality  (-> section 10)
Give figures at Go-Live and +1 year (add +2/+5Y if you have them). "TBD" is fine per cell.
F1. `[REQUIRED]` Products: # active, # abstract, avg/max concretes per abstract, # bundles.
F2. `[REQUIRED]` Categories: how many (root + sub)?
F3. `[REQUIRED]` Prices: which price types (standard, RRP, sale, customer-specific), single or multiple
    price dimensions?
F4. `[REQUIRED]` Load: visitors/sessions per day or month; orders per day; conversion rate;
    seasonality (peak months).
F5. `[optional]` Cart size: average and maximum line items.
F6. `[REQUIRED]` People (per phase where they change): # customers, # Back Office users, # merchants
    (if marketplace - e.g. "N/A now, 300 dropshippers later").
F7. `[optional]` Peak concurrency: max concurrent customers / BO users / merchants. (If unknown, the
    skill derives an interim number from F4 and flags it as an assumption.)
F8. `[REQUIRED]` Country expansion per phase: # regions, # countries, # stores per country, # languages,
    # currencies.
F9. `[optional]` Per-store infrastructure: shared DB/Redis/ES across stores, or separate per store?
F10. `[REQUIRED]` Data import/export: what data, frequency, full vs delta, push vs pull, format, and
    size/speed (e.g. "delta every 10 min ~50 products", "full XML on S3, import < 30 min",
    "~1.5M XML tags"). -> also feeds 05/07/11
F16. `[optional]` Do any volumes grow dramatically post-go-live (an order of magnitude within a year,
    e.g. 300k -> 26M products), or does the import/processing volume itself pose a scaling risk? Give
    the trajectory and any known mitigation direction. The skill treats this as a central design
    driver, not a footnote. -> feeds sections 05, 07, 10, 11, 09
F11. `[optional]` What is shared vs separate across stores/countries (products, customers, prices)?
F12. `[optional]` Data residency / firewall restrictions (multi-store only).
F13. `[optional]` Staging/non-prod environments planned (dev, staging, pre-prod, prod).
F14. `[optional]` Testing strategy: CI/CD, test data, ability to simulate external integrations.
F15. `[optional]` Concrete measurable targets: page load, search latency, API p95, batch/import time.

## Group G - Runtime, deployment, cross-cutting  (-> sections 06, 07, 08)
G1. `[REQUIRED]` Which key runtime flows deserve a sequence diagram? (e.g. checkout+payment,
    publish & sync, a headline integration flow, order fulfilment, login/SSO, returns, data import.)
G2. `[optional]` Deployment topology details beyond E1: environments, CI/CD, monitoring/APM, backup.
G3. `[optional]` Multi-store strategy (if >1 store): one region vs per-country regions; one domain?;
    is Dynamic Multi-Store (DMS) intended? (Shared-vs-separate data + residency are covered in F9/F11/F12.)
G4. `[optional]` Cross-cutting needs: authorization/permission model, logging/observability, error
    handling, i18n, caching strategy, security patterns.

## Group H - Decisions and exploration  (-> sections 04, 09, 11; only if those are selected)
H1. `[optional]` Decisions already MADE that should be recorded as ADRs (with the "why"). e.g.
    "invoice-only, no PSP for POC", "Payone direct module not ACP", "single shared DB".
H2. `[optional]` Open decisions / hard problems that need an RFC-style Solution Design before building
    (e.g. dynamic pricing model, large-scale import strategy, dealer hierarchy, returns portal).
H3. `[optional]` Known open questions / risks to carry into the doc as TODOs or decision gates. For any
    launch-blocking one, give an owner AND a resolve-by date so it becomes a pre-Go-Live decision gate
    rather than standing debt. -> feeds section 11 decision gates

## Group I - Document governance  (-> section 01 header, 04/09 metadata)
I1. `[REQUIRED]` Document status for this run: Draft / In Review / Approved? Optional RAG (Red/Amber/
    Green). -> feeds section 01 status block
I2. `[REQUIRED]` Author/Driver name(s) for the document. -> feeds sections 01, 04, 09
I3. `[optional]` Approver(s) / sign-off owners - internal approver + external/client approver (with
    dates or "TBD"). -> feeds section 01
I4. `[optional]` Document version label (e.g. 0.1, 1.0), and any handover session (date, attendees).
    -> feeds section 01
(Key dates default to today; ADR/SD dates the skill fills - you need not supply those.)

## Group J - Organization & access model  (-> sections 03, 05, 08, 11; B2B / multi-tenant / multi-market)
If the project is a single flat B2C shop with no org hierarchy: write "n/a" once here.
J1. `[optional]` Is there a multi-tier commercial hierarchy (e.g. Headquarter -> Market/Region ->
    Dealer/Merchant -> Customer)? Describe the tiers and who manages whom. -> feeds sections 03, 05
J2. `[optional]` Do merchants/dealers have an internal hierarchy (main instance + subordinate branches,
    shared vs branch-specific pricing, cross-branch login)? -> feeds sections 05, 08 + likely an SD
J3. `[optional]` Is there a customer-approval-before-order workflow (customer requests access to a
    dealer/merchant; the dealer approves before the customer can buy; merchant-specific customer IDs)?
    -> feeds sections 06, 08 + likely an SD
J4. `[optional]` Data isolation: must each market/store/tenant see ONLY its own data in the Back Office
    (with only HQ seeing all)? -> feeds sections 08, 11 (skill will consider Persistence ACL)
J5. `[optional]` Any HQ/central monitoring or reporting across markets/stores (import status, revenue
    thresholds, legal-info status)? -> feeds sections 05, 11
J6. `[optional]` Any per-store / per-country CUSTOM functionality (e.g. license-plate search in one
    country, a price-upload rule or integration only one store needs)? -> feeds sections 01, 03, 05, 07
J7. `[optional]` Do stores/markets need their own legal or content templates (T&C, warranty,
    disclaimers, sales regulations) with a default + per-store override/fallback? Who authors them and
    who consumes them (e.g. dealers reuse a market template)? -> feeds sections 05, 08
J8. `[optional]` Is there order-level messaging between buyer and merchant/dealer (customer comments on
    an order, merchant replies, thread in order history)? -> feeds sections 05, 06, 09

---

## Run-configuration questions (always confirm, regardless of the above)
These decide HOW the skill runs, not the architecture content:
R1. `[REQUIRED]` Which arc42 sections to produce this run? (Default: all 12 + C4 + ADRs + SDs.)
R2. `[REQUIRED]` Mode: Autonomous (interview once, then write everything) or Gated (confirm each
    batch)?
R3. `[REQUIRED]` Output target: the git-tracked `architecture/` folder in place (default), or a
    worktree/branch/PR (default for multi-deliverable runs)?
R4. `[optional]` For an EXISTING project: may the skill read the codebase / run the app to ground
    sections in the real system?

## Minimum viable answer set (if you answer nothing else)
A1-A7, B1-B3, C1-C4, C7, D1-D2, E1-E2, F1-F4, F6, F8, F10, G1, I1-I2, R1-R3. With just these the skill
can produce a grounded first draft; everything else becomes an honest TODO. Groups H and J are only
needed when ADRs/SDs are selected (H) or the project has a real org/tenant hierarchy (J).
