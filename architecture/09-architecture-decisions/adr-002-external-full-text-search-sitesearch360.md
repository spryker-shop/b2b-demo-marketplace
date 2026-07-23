# ADR-002: SiteSearch360 for full-text search, OOTB Elasticsearch for category PLPs

## Status

**Accepted** (2024-07-25)

## Context

NORMA requires a full-text site search experience delivered by **SiteSearch360**. Spryker ships **no packaged SiteSearch360 connector** — external search is a documented **pattern** ("Integrate any search engine into a project"), not a turnkey feature (research §16). At the same time, category listing pages (PLPs) work well on Spryker's OOTB Elasticsearch and do not need an external engine.

## Decision

We will **delegate full-text search to SiteSearch360** via a custom integration following Spryker's "integrate any search engine" pattern (replace the storefront search/suggestion `QueryInterface` plugins, build the provider query, and map responses back to the shop format), while **keeping OOTB Elasticsearch for category PLPs**. SiteSearch360 is fed from the product feed produced by the feed-generation domain.

## Consequences

### Positive
- Delivers NORMA's required search behaviour without over-fitting OOTB ES.
- Clear architectural boundary: full-text vs category browse.
- PLPs stay on the standard, well-understood ES path.

### Negative
- Two search backends to operate and keep consistent; SiteSearch360 freshness depends on the feed, independent of P&S.
- Custom integration code (no connector) — carries its own maintenance and testing burden.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
