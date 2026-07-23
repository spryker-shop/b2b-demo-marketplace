# Architecture Decision Records

This directory contains Architecture Decision Records (ADRs) that document significant architectural decisions made for the system.

## What is an ADR?

An Architecture Decision Record (ADR) captures a single architectural decision and its rationale. ADRs answer the question: **"Why did we choose this approach?"**

Each ADR documents:
- The context and problem
- The decision made
- The consequences (positive and negative)
- Alternatives that were considered

## When to Create an ADR

Create an ADR when:
- A significant architectural decision is made that impacts the system structure
- You choose between multiple technical approaches
- The decision affects multiple teams or components
- Future developers will need to understand why something was done a certain way
- Company rules or conventions require a specific approach to be documented

## Sources of ADRs

ADRs can originate from multiple sources:

```mermaid
flowchart TB
    SD[Solution Design<br/>Exploration & Proposal] --> ADR[ADR<br/>Decision Record]
    Meeting[Architecture Review<br/>or Engineering Meeting] --> ADR
    Convention[Company Rule<br/>or Convention] --> ADR
    Backfill[Backfilling<br/>Undocumented Decision] --> ADR

    style SD fill:#fff4e6
    style ADR fill:#e1f5ff
    style Meeting fill:#f0f0f0
    style Convention fill:#f0f0f0
    style Backfill fill:#f0f0f0
```

**1. From Solution Designs (Most Common)**
- An SD explores multiple options
- Team reviews and makes decisions
- Key decisions are extracted into ADRs
- For instance: SD-001 exploring ERP import options → ADR-003 "Delta-only import via Talend"

**2. From Meetings/Discussions**
- Decision made during architecture review
- Consensus reached in engineering meeting
- Document immediately as ADR

**3. From Company Rules/Conventions**
- Organization mandates specific approach
- Industry compliance requirement
- Document the constraint and rationale
- For instance: "Authenticate the Glue Backend API with OAuth 2.0 scopes for Talend"

**4. Backfilling Past Decisions**
- Important decisions were made but never documented
- Document them now while context is still available
- Mark with historical date in context

## Naming Convention

ADRs follow the pattern: `adr-XXX-brief-title.md`

Examples:
- `adr-001-use-postgresql.md`
- `adr-005-use-auth0-as-ciam.md`
- `adr-012-multi-region-deployment.md`

## Template

Use `adr-000-template.md` as the starting point for new ADRs.

## Index

| ADR | Title | Status |
|-----|-------|--------|
| [ADR-001](adr-001-payone-direct-module-over-acp.md) | Payone via direct module (not ACP) | Proposed (gate G1) |
| [ADR-002](adr-002-external-full-text-search-sitesearch360.md) | SiteSearch360 for full-text; OOTB ES for category PLPs | Accepted |
| [ADR-003](adr-003-delta-only-data-import-via-talend-glue-backend-api.md) | Delta-only data import via Talend over Glue Backend API | Accepted |
| [ADR-004](adr-004-dwh-access-mechanism.md) | DWH access mechanism (site2site VPN vs Data Exchange API) | Proposed (gate G2) |

## ADR Lifecycle

ADRs have a status that reflects their lifecycle:

- **Proposed**: Under discussion, not yet decided
- **Accepted**: Decision approved and being implemented
- **Implemented**: Decision fully implemented in the system
- **Deprecated**: No longer valid but kept for historical reference
- **Superseded by ADR-XXX**: Replaced by a newer decision

## Best Practices

- **Write concisely**: ADRs should be readable in 5 minutes
- **Focus on "why"**: Explain reasoning, not just the what
- **Be honest about trade-offs**: Every decision has downsides
- **Date everything**: Use ISO dates (YYYY-MM-DD)
- **One decision per ADR**: Keep it focused
- **Reference related docs**: Link to SDs, external documentation, research

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
