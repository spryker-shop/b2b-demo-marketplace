# System Scope and Context

Delimits the Daimler Truck B2B Parts platform from its communication partners and specifies the external interfaces. This is an **integration-heavy** system: 10 external Daimler systems plus a 3-tier actor hierarchy.

## Business Context

The platform sits between the Daimler after-sales organization (HQ → 22 Markets → Dealers) and its customers (organizations with workshops), while delegating catalog/search, availability, identity, pricing and vehicle intelligence to Daimler back-end systems.

**See diagram:** [C1 System Context](diagrams/c4/c1-system-context.mmd) · [Integration Overview](diagrams/integration/external-systems-overview.mmd)

### Actors

| Actor | Tier | Interaction |
|-------|------|-------------|
| **Headquarter user** | 1 | Manages all markets, per-market monitoring, global catalog config (Back Office). |
| **Market user** | 2 | Manages own market's dealers & settings; isolated to own data (Back Office). |
| **Dealer user** | 3 | Manages customer pool, prices, orders & shipping (Merchant Portal). |
| **Customer** | buyer | Registers with a dealer, finds parts, orders (Storefront/Yves). |

## Technical Context

### External Systems

| System | Responsibility | Technology | Stakeholders |
|--------|----------------|------------|--------------|
| **IParts** | Additional part info (does a part match a vehicle) + parts catalogue structure. **Source for the shop parts catalogue and PLP/search building.** | REST API | Daimler |
| **Vehicle Information Service (VIS)** | Info on registered vehicles (maintenance history, replaced parts); FIN/VIN validation & vehicle data card. | REST API | Daimler |
| **TruckLog (DIMS)** | Spare-parts availability info globally (future; not all markets yet). | SOAP / REST API | Daimler |
| **MB LogBus DIMS** | Availability info used where DT Logbus DIMS is not yet available. | REST API | Daimler |
| **CRISP** | Customer organisation information (excludes dealer relations — those are handled by the Shop). | REST API | Daimler |
| **Integrated Price Service (IPS)** | Prices & parts-master data via XML file over SFTP today; to be replaced by two new services within the year. | XML files (SFTP) | Daimler |
| **RetailNet** | Dealer (merchant) organisation information. | REST API | Daimler |
| **Dealer Locator** | Dealer locations; supports customer registration (connecting dealers). | Linkout | Daimler |
| **FUSO / BPC** | External parts catalogues for FUSO & Bus parts. | Linkout / REST API | Daimler |
| **DTAG IAM (Empower ID)** | User roles & rights management + IAM services; customer authentication. | OAuth | Daimler |

### Integration Details

| Integration | Direction | Protocol | Sync/Async | Frequency / Constraints | Release Phase |
|-------------|-----------|----------|-----------|-------------------------|---------------|
| IParts → Spryker | Pull | REST | Sync (on demand) | Catalog structure + part-vehicle match; drives PLP/search. Search NOT served by Spryker ES. | Go-Live |
| VIS → Spryker | Pull | REST | Sync (on demand) | FIN/VIN validation, vehicle data card. | Go-Live |
| TruckLog DIMS → Spryker | Pull | SOAP / REST | Sync (on demand) | Availability. **Not all markets at Go-Live.** | Phased |
| MB LogBus DIMS → Spryker | Pull | REST | Sync (on demand) | Availability fallback where TruckLog not yet available. | Go-Live (fallback) |
| CRISP → Spryker | Pull | REST | Sync/near-real-time | Customer org info; excludes dealer relations. | Go-Live |
| IPS → S3 → Spryker | Import | SFTP → XML on S3 | Async batch | **FULL import per store**, ~1.5M `<SET>#productdata` tags, ≤30 min (not strict). To be replaced by two new services this year. | Go-Live |
| RetailNet → Spryker | Pull | REST | Sync/near-real-time | Dealer (merchant) org info. | Go-Live |
| Spryker ↔ Dealer Locator | Redirect | Linkout | N/A | Locate/establish dealer relation, redirect back. | Go-Live |
| Spryker ↔ FUSO/BPC | Redirect + Pull | Linkout / REST | Sync | Redirect to external catalog, back; then request basket details. | Go-Live |
| Spryker ↔ DTAG IAM | Auth | OAuth (redirect/REST) | Sync | Customer authentication (OAuth flow). | Go-Live |

### Components Rationale

Why each major component exists in the solution.

| Component | Rationale |
|-----------|-----------|
| **Yves storefront** | Customer-facing shop; renders PLP/PDP using IParts-built catalog structure, dealer-scoped prices, VIS/DIMS lookups. |
| **Zed Back Office** | HQ + Market administration, per-market monitoring, market-isolated data access. |
| **Merchant Portal** | Dealer (merchant) self-service: customer-pool approval, prices/discount groups, order & shipping management. |
| **Glue Backend API** | System-to-system surface for Daimler back-ends and the import pipeline. |
| **Import pipeline (S3 + Middleware + CTE import)** | Absorbs the 1.5M-tag full XML import without proxying files through Spryker; splits and bulk-loads efficiently. |
| **External catalog/search adapter** | Delegates catalog structure and PLP/search to IParts instead of Spryker Search. |
| **Availability adapter (DIMS)** | Fetches live stock from TruckLog/MB LogBus per market. |
| **Identity adapter (DTAG IAM)** | Federated customer authentication via OAuth. |

### Connectors Rationale

Why each connector uses the protocol it does.

| Connector | Protocol | Rationale |
|-----------|----------|-----------|
| IParts, VIS, CRISP, RetailNet, MB LogBus | REST | Partner systems expose REST; called on demand for freshest data. |
| TruckLog DIMS | SOAP (/REST) | Legacy availability service exposes SOAP; wrapped behind an adapter so callers stay protocol-agnostic. |
| IPS | SFTP → XML on S3 | Bulk parts/price master delivered as large XML files; file transport (not API) is appropriate for full loads. |
| DTAG IAM | OAuth | Federated identity; industry-standard delegated auth. |
| Dealer Locator, FUSO/BPC | Linkout (+REST) | External UIs owned by Daimler; redirect out and back preserves their ownership; basket details fetched over REST on return. |

---

*Corresponds to [arc42 Section 3](https://docs.arc42.org/section-3/)*
