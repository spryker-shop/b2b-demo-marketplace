# ADR-004: DWH access mechanism (site2site VPN vs Data Exchange API)

## Status

**Proposed** (2024-07-25) — stays Proposed until decision gate **G2** closes (Spryker Cloud confirmation). See [Section 11](../11-risks-and-technical-debt.md).

## Context

The DWH needs NORMA data for BI. Spryker **does not support external DB connections by default** (research §9). Two options exist:
1. A secure **site2site VPN** DB connection (Spryker Cloud has confirmed this is feasible in principle).
2. The **Data Exchange API** — configurable REST endpoints over Spryker DB tables, no middleware, suitable for feeding a DWH.

The choice depends on Spryker Cloud confirmation, DWH team preference, and the extract volume (currently TBD).

## Decision

We will select **one of**: (a) a Spryker-Cloud-provided **site2site VPN DB connection**, or (b) the **Data Exchange API** (REST over Spryker DB, consumed via Talend). The decision is deferred to gate **G2**; the VPN path is the current front-runner because Spryker Cloud has confirmed feasibility, but the Data Exchange API is preferred if a middleware-less REST extract is sufficient and avoids direct DB coupling.

## Consequences

### Positive (once resolved)
- A supported, secure path for DWH/BI without ad-hoc DB access.
- Data Exchange API option needs no middleware and reuses the Backend API infrastructure.

### Negative
- Unresolved at TAD time — a blocker for the DWH extract build.
- VPN DB access couples BI to the physical schema; Data Exchange API has documented limitations and needs batch/perf tuning for volume.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
