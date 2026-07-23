# Self-review — is the arc42 output signed-off-ready for a Spryker project?

This is the **quality gate** for Step 6, run by a dedicated review subagent (never the writers who
produced the sections — an independent reader catches what the author's context hides). The reviewer
reads the written `architecture/` files, the run's `intake.md`, and this checklist, then returns a
**per-section verdict + a ranked defect list**, not a rewrite.

The bar is not "does it look like arc42" — it's **"could a Spryker delivery lead build from this on
day 1, and would an architect sign it off?"** A generic arc42 checklist (goals count, page length,
stakeholder table) is necessary but not sufficient here. What makes *this* review Spryker-specific is
that every claim about the platform must be grounded in real Spryker capabilities, and the depth bar
(release phasing, sizing, named-feature mitigations) must actually be met — not gestured at.

## How the reviewer runs

- **Read-only.** The reviewer does not edit `architecture/`. It reports; the orchestrator (Gated) or a
  targeted fix pass (Autonomous) applies changes. Spawn it as an `Explore` or fresh `general-purpose`
  subagent on a Claude model, fed: the list of written files, `intake.md`, `research-docs.md`, and
  this `review.md`.
- **Scope = the sections actually written this run.** Don't fault an untouched template section that
  wasn't in scope. Do fault a section that was *supposed* to be written but left as template example
  content (SAP/Akeneo/Auth0 leftovers) — that's the #1 silent failure.
- **Every finding is actionable and located.** `file:heading` + one-line defect + the fix. Rank by
  severity: **BLOCKER** (wrong/fabricated/build-blocking) → **MAJOR** (depth-bar miss) → **MINOR**
  (polish, cross-link, terminology).
- **Verdict per section:** `PASS` / `PASS-with-TODOs` / `NEEDS-WORK`, plus one overall verdict.
- An honest, well-owned `> **TODO:**` is **not** a defect — it's a professional outcome. A *fabricated*
  number, an *invented* integration, or a TODO where the intake gave a real answer **is** a defect.

## A — Grounding & honesty (Spryker-specific, highest weight)

The failure mode that a generic reviewer misses. Check every platform claim:

- [ ] **No fabrication.** Every volume, integration, actor, and module name traces to `intake.md`, a
      provided doc, `research-docs.md`, or the codebase. A confident number with no source is a BLOCKER.
- [ ] **Spryker behavior is grounded, not remembered.** Any "Spryker does X / uses P&S / this module
      handles Y" claim is backed by `research-docs.md` (ideally with a docs URL). Unsourced platform
      assertions are MAJOR.
- [ ] **Named features are real and current.** Mitigations and building blocks cite features that
      actually exist and are GA (watch the known traps: ACP is being sunset → use direct PSP modules;
      Glue-API/OAuth SSO is not GA but storefront Federated Auth is; "Drop Shipment" is not a feature →
      Marketplace primitives; external search delegation is a documented pattern, not a connector;
      Persistence ACL is real and Propel-only; DMS GA since 202410.0). A cited-but-nonexistent or
      wrong-status feature is a BLOCKER.
- [ ] **No template leftovers.** Grep the written sections for the template's example entities (SAP,
      Akeneo, Auth0, the sample volume figures, sample diagram labels). Any survivor in an in-scope
      section is a BLOCKER — it means the section was not actually rewritten.
- [ ] **Gaps are triaged, not defaulted.** Each open item is one of the three honest forms
      (client-owned `TODO` w/ owner · launch-blocking → decision gate w/ spike/owner/date ·
      derivable-but-unconfirmed → stated interim assumption). Everything dumped as a bare `TODO` is MAJOR.

## B — Depth bar met (from architecture-depth.md)

Decision-grade, not template-thin. For the sections in scope:

- [ ] **Release phasing.** Scope items and integrations carry a **Phase 1 / Phase 2** tag; the roadmap
      and the volume table's +1Y/+…Y columns tell the same phased story. (§01/§03/§10)
- [ ] **Components + Connectors rationale tables** accompany every architecture diagram — never a
      diagram alone. Connector rows carry a real Type (REST/SOAP/SFTP/Widget/Redirect/AMQP/OAuth/VPN DB)
      and put the numbers in the Constraints cell (or an honest `TBD`). (§03/§05)
- [ ] **Volume table is rich and per-phase** — Go-Live / +1Y / +…Y columns, and it goes beyond the
      stock rows where relevant (price types & dimensions, bundles, delta-vs-full import, per-store
      infra split, historical migration counts). (§10)
- [ ] **Multi-store strategy is explicit** when >1 country/store: region/store/DB/codebase split,
      Dynamic Multi-Store noted where it applies. (§07)
- [ ] **Risk mitigations name real Spryker features** with a docs link, not generic advice. (§11)

## C — Build-ready additions (the review-proven five)

What a delivery lead needs on day 1 — reach for these wherever the inputs allow; where they don't, the
**bounded/interim form** is required, not silence:

- [ ] **Sized, not just sequenced.** T-shirt effort (S/M/L/XL + one-line driver) per custom module in
      §05/SDs; first-pass infra sizing (hot-table row counts, Redis key volume, worker/queue counts) in
      §07/§10 where volumes were given. A large volume jump (e.g. 300k→26M) is *worked through* as the
      central scaling decision, not just flagged.
- [ ] **Every Solution Design has a real Data Model section** — table sketches (Propel-style names, key
      columns, FKs), transfers, read/write ownership. A directional SD with a bare-TODO data model is a
      MAJOR defect; if genuinely underivable, it must be a **named spike** (question/owner/timebox).
- [ ] **Launch-blocking unknowns are decision gates**, not standing tech debt: decision + spike + owner
      + resolve-by date tied to the roadmap, in §11 (ADR stays `Proposed` until the gate closes).
- [ ] **Load/capacity tests are anchored** by an explicit interim assumption derived from what IS given
      (visitors × conversion × seasonality), stated as "replace on client confirmation". (§10)
- [ ] **One integration-readiness checklist** (egress/allow-listing per system, secret management,
      per-adapter timeout/retry/circuit-breaker) with named owners in §07/§08, cross-linked from §11 —
      not scattered one-line footnotes.

## D — arc42 structure & internal consistency (the generic-but-necessary layer)

- [ ] **§01 header block** present: Status (Draft/In Review/Approved, optional RAG), Author/Driver,
      Approver(s), key dates, version.
- [ ] **Quality goals**: 3–5, each with a measurable scenario (a real number/threshold), prioritized.
- [ ] **Stakeholders**: each party has a stated expectation; deployment/ops roles present if §07 written.
- [ ] **Cross-links resolve**: §03→C1, §05→C2/C3, §06→sequences; every "See diagram" link points at a
      file that exists; §04/§09 README indexes list their SDs/ADRs.
- [ ] **Terminology reconciles with §12** — every domain/Spryker term used is defined in the glossary.
- [ ] **External systems are consistent** across §03/§02/§06/§11 and the diagrams (same names, no system
      appearing in a diagram but missing from the Components table).
- [ ] **Diagrams are Mermaid `.mmd` only** (ERDs = `erDiagram`), follow the color convention, and
      actually render — no PlantUML/`.puml`, no binaries. A `.puml` file, or a `;` in a note / a `%%`
      comment before the `---` frontmatter (both break the Mermaid parser), is a defect.

## What the reviewer returns

```
OVERALL: PASS-with-TODOs   (or PASS / NEEDS-WORK)

Per-section:
  01-introduction-and-goals.md ...... PASS-with-TODOs
  03-system-scope-and-context.md .... NEEDS-WORK
  ...

Findings (ranked):
  [BLOCKER] 03 §External Systems — "SAP S/4HANA" is template leftover, intake names Infor M3. Replace.
  [MAJOR]   10 §Volumes — no +1Y column; intake gives year-2 country expansion. Add per-phase columns.
  [MINOR]   05 §Components — C2 link points at c2-container.mmd which doesn't exist (file is c2-containers.mmd).
  ...
```

The reviewer never rewrites sections — it hands this report back so the fix is a deliberate, logged
step.

## Review is a loop, not a report — every finding must be resolved

This checklist exists to **drive fixes**, not to produce a verdict and stop. Per SKILL.md Step 6, the
orchestrator runs **review → fix → re-verify** until it passes:

1. **Fix is mandatory** after any round that finds a BLOCKER or MAJOR — in both Gated and Autonomous
   mode. Fixes go back to writer teammates (same per-section / per-deliverable isolation as the
   original write); small mechanical fixes the orchestrator applies inline.
2. **Every finding resolves in exactly one honest way**: *fixed*, or *converted to a triaged owned gap*
   (client-owned TODO · launch-blocking decision gate w/ owner+date · derivable interim assumption).
   Never silently dropped, never left standing as a defect.
3. **Re-verify with a fresh reviewer** (new independent context) after each fix pass — an author, or a
   reviewer re-reading its own pass, can't see what it missed. Loop until **zero open BLOCKER/MAJOR**
   (capped at 3 rounds; an unresolved blocker at round 3 is escalated in the summary, not hidden).

So a reviewer's job is to make each finding *fixable*: located, actionable, and severity-ranked — so
the fix pass knows exactly what to change and the re-verify knows exactly what to re-check.
