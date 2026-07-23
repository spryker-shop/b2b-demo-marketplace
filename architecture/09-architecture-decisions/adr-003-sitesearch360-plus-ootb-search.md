# ADR-003: SiteSearch360 for full-text + OOTB Elasticsearch for category pages

## Status

**Accepted** (2024-07-25)

## Context

NORMA wants a specific full-text search experience (SiteSearch360) while retaining Spryker's category browsing. Spryker's **Search PBC** provides catalog/category search on Elasticsearch/OpenSearch fed by Publish & Sync ([docs](https://docs.spryker.com/docs/pbc/all/search/latest/search)). There is **no OOTB "external search delegation" feature** — an external provider must be a custom integration.

## Decision

We will run **two search paths**: full-text search delegated to **SiteSearch360** (custom search-client integration, JS widget + REST), and **category pages served by OOTB Elasticsearch** via the Search PBC. Product data continues to be indexed into ES via P&S for category/browse; SiteSearch360 is fed/queried independently for full-text.

## Consequences

### Positive
- Best-of-both: SiteSearch360's full-text relevance + Spryker's native category/filter behaviour.
- Category pages stay on the standard, well-understood ES path.

### Negative
- Two systems to operate and keep in sync; relevance/behaviour split across engines is a UX and maintenance risk.
- Custom search-client integration (no OOTB support) — implementation and test effort.
- ES index still grows with both `product_abstract` and `product_concrete`; apply index-deduplication guidance to curb growth.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
