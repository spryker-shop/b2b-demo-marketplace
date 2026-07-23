# Risks and Technical Debt

Risks and open questions for the Daimler Truck B2B Parts platform. The project is **Approved WITH RISKS (Yellow)** — these items must be tracked to closure.

## Technical Risks

| Risk | Impact | Mitigation (Spryker feature + docs) |
|------|--------|-------------------------------------|
| **Dynamic Multi-Store maturity** — DMS flagged Early Access at approval (committed GA in Q3), may have bugs/pitfalls; Go-Live is 100% scope. | 22-market storefront may hit defects late in a no-MVP timeline. | Confirm GA against target release notes; stage 1 country first then scale. [DMS overview](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview) |
| **Large XML import scale** — ~1.5M `<SET>#productdata` tags full per store in ≤30 min, growing to 26 mio parts. | Import timeouts / memory exhaustion; stale catalog. | Don't proxy files through Spryker (S3 direct); split on middleware; CTE/PDO bulk import; multi-queue P&S with tuned chunks. [Data-import optimization](https://docs.spryker.com/docs/dg/dev/data-import/latest/data-import-optimization-guidelines) · [Architecture performance](https://docs.spryker.com/docs/dg/dev/guidelines/performance-guidelines/architecture-performance-guidelines) · [ADR-003](09-architecture-decisions/adr-003-direct-s3-xml-import-not-proxied.md) |
| **Complex dealer prices / discount groups** — per-product/per-customer discount structure. | Incorrect prices shown to customers; hard-to-maintain custom logic. | Build on [Merchant Custom Prices](https://docs.spryker.com/docs/pbc/all/price-management/latest/base-shop/merchant-custom-prices-feature-overview) + custom discount-group model. [SD-001](04-solution-designs/sd-001-dealer-prices-discount-groups.md) |
| **Dealer hierarchy (branches)** — main instance + subordinate branches, shared prices, cross-branch login, branch roles. | Wrong branch can see/change prices; login scoping errors. | [BO User roles & groups](https://docs.spryker.com/docs/pbc/all/user-management/latest/base-shop/manage-in-the-back-office/best-practices-manage-users-and-their-permissions-with-roles-and-groups) + custom hierarchy restrictions. [SD-002](04-solution-designs/sd-002-dealer-hierarchy-branches.md) |
| **Market data isolation** — each market sees only its data; HQ sees all. | Cross-market data leakage in BO. | Persistence ACL pattern / custom controller restriction on the [ACL model](https://docs.spryker.com/docs/pbc/all/user-management/latest/user-management). Mechanism uncertain — confirm in installed modules. [ADR-002](09-architecture-decisions/adr-002-persistence-acl-for-market-isolation.md) |
| **External catalog/search dependency (IParts)** — PLP/search delegated to a 3rd party; Spryker ES not used for product search. | Catalog/search availability tied to IParts; a Spryker-native fallback is absent. | Custom search-client integration; define degradation behaviour. [Search PBC](https://docs.spryker.com/docs/pbc/all/search/latest/search) · [ADR-004](09-architecture-decisions/adr-004-external-catalog-search-delegation.md) |
| **Phased availability (DIMS)** — TruckLog DIMS not available in all markets at Go-Live. | Some markets lack live availability at launch. | MB LogBus DIMS (REST) as fallback where TruckLog absent; adapter abstracts protocol. |
| **IPS replacement mid-year** — IPS (SFTP/XML) to be replaced by two new services within the year. | Import source contract changes post-Go-Live. | Isolate import behind the middleware/adapter boundary so the source can change without touching Spryker import. |
| **Merchant Product Restrictions constraints** — excludelisting a single item in a product set/bundle still shows the rest. | Edge-case product visibility leaks. | Confirm against [restricted-products behavior](https://docs.spryker.com/docs/pbc/all/merchant-management/latest/base-shop/merchant-product-restrictions-feature-overview/restricted-products-behavior); sets/bundles not used at Go-Live (bundles N/A) so low current exposure. |

## Technical Debt

| Item | Impact | Plan |
|------|--------|------|
| **Custom market-isolation mechanism unconfirmed** | Risk that the chosen row-level restriction diverges from a supportable pattern. | Spike to confirm Persistence ACL vs custom controller restriction in installed modules; record in [ADR-002](09-architecture-decisions/adr-002-persistence-acl-for-market-isolation.md). |
| **Non-standard import pipeline** | Split-on-middleware + CTE import is a project pattern, not verbatim in Spryker docs; upgrade/maintenance burden. | Keep bulk-load logic behind a clear module boundary; benchmark each release. |
| **Bespoke discount-group pricing** | Custom pricing on top of Merchant Custom Prices adds complexity. | Contain in a dedicated module with strong tests; see [SD-001](04-solution-designs/sd-001-dealer-prices-discount-groups.md). |
| **10 external adapters, mixed protocols** | Broad integration surface to maintain (REST/SOAP/SFTP/OAuth/Linkout). | Uniform adapter boundary + contract tests per external system. |

---

*Corresponds to [arc42 Section 11](https://docs.arc42.org/section-11/)*
