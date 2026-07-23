# ADR-002: Live-Fetch Assets (No Caching) for the POC

## Status

**Accepted (for POC)** (2026-07-23)

## Context

Fleet vehicles/assets live in **FMAT** and are needed when the customer opens the asset-selection page.
The TAD scopes the POC to **fetch assets live** from FMAT (paginated), with **no caching/sync in
Spryker**. Fleet sizes and asset attributes are unknown, and FMAT's API spec/protocol is **TBD**.
Performance is explicitly not a POC quality driver, and external API latency has no SLA.

## Decision

We will **fetch assets live from FMAT on each request** (paginated), behind a dedicated
`FmatAssetClient` module, and **not** persist or cache assets in Spryker for the POC. Caching/sync is
deferred to a Future phase.

## Consequences

### Positive
- Simplest correct behaviour — always shows FMAT's current asset state; no staleness.
- No sync pipeline, no asset persistence schema, no cache-invalidation logic to build for the POC.
- The `FmatAssetClient` boundary means adding caching later changes only one module.

### Negative
- **FMAT latency/availability directly affects UX** — a slow or down FMAT degrades the asset page (no
  SLA, accepted for POC).
- Repeated load on FMAT for large fleets; pagination mitigates response size but not call frequency.
- No offline/degraded-mode behaviour (accepted debt; graceful error handling is Future — see §11).

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
