# ADR-004: Delegate catalog structure and PLP/search to IParts (external service)

## Status

**Accepted** (2024-06-04)

## Context

Determining whether a part fits a vehicle, and building the parts catalogue structure/filtering, is domain logic owned by Daimler's **IParts** service. The catalog is huge (300k → 26 mio parts) and abstract products need only minimal data (name + price) to be listable. Running Spryker's Elasticsearch/OpenSearch product search over this volume would duplicate IParts and inflate the index (indexing abstract + concrete multiplies documents — [search index deduplication](https://docs.spryker.com/docs/dg/dev/guidelines/performance-guidelines/search-index-deduplication)).

## Decision

**Delegate catalog structure, category tree and PLP/search filtering to IParts** over REST. Spryker Search/ES is **not** used for product search/filtering. Spryker stores minimal abstract products (name + price) so parts are orderable and priceable; PLP building is a custom search-client integration against IParts ([Search PBC](https://docs.spryker.com/docs/pbc/all/search/latest/search)).

## Consequences

### Positive
- Single source of truth for part-vehicle matching and catalog structure (IParts).
- Avoids indexing 26 mio parts in Spryker ES and its growth/cost.

### Negative
- **Not a documented turnkey Spryker feature** — external search delegation is a custom integration; treat as architecture design and confirm scope.
- Catalog/search availability is coupled to IParts; a Spryker-native search fallback is absent — degradation behaviour must be defined ([Section 11](../11-risks-and-technical-debt.md)).
- Some Spryker features that assume ES-backed catalog (e.g. facet-driven UI) are unavailable OOTB.

---

*Corresponds to [arc42 Section 9](https://docs.arc42.org/section-9/)*
