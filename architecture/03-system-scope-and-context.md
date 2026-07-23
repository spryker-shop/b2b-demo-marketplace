# System Scope and Context

Delimits the Daimler Truck B2B Parts platform from its external partners and specifies the external interfaces.

## Business Context

Daimler Headquarter provides one Spryker platform for 22 country **Markets**; each market's **Dealers** (Spryker merchants) sell HQ-provided parts to their approved **Customers**. The platform integrates with ~12 Daimler systems for catalog, prices, vehicle/part data, availability, organisation data, identity, and external catalog search. Product search/filtering is delegated to an external Daimler catalog API rather than Spryker's own Elasticsearch.

**See diagram:** [C1 System Context](diagrams/c4/c1-system-context.mmd) · [Integration Overview](diagrams/integration/integration-overview.mmd)

## Technical Context

### External Systems

Near-verbatim from the TAD "Additional Components Information" table.

| System | Description | Technology | Stakeholders |
|--------|-------------|------------|--------------|
| **IParts** | Additional parts info (part↔vehicle fitment) + parts catalogue structure for all spare parts; source for the catalogue in the shop | REST API | Daimler |
| **Vehicle Information Service (VIS)** | Info for registered vehicles (maintenance history, replaced parts); FIN/VIN validation + vehicle data card | REST API | Daimler |
| **TruckLog (DIMS - SOAP)** | Global spare-part availability (rolling out per market; not all markets yet) | SOAP / REST API | Daimler |
| **MB LogBus DIMS (REST)** | Availability info; used where TruckLog DIMS not available | REST API | Daimler |
| **CRISP** | Customer organisation information (excludes dealer relations, which the Shop handles) | REST API | Daimler |
| **Integrated Price Service (IPS)** | Prices + parts-master data as XML via SFTP (being replaced by two new services this year) | XML files | Daimler |
| **RetailNet** | Dealer (merchant) organisation information | REST API | Daimler |
| **Dealer Locator** | Dealer locations; supports customer registration (connecting dealers) | Linkout | Daimler |
| **FUSO / BPC** | External parts catalogues for FUSO and Bus parts | Linkout / REST API | Daimler |
| **DTAG IAM (Empower ID)** | User roles & rights management + IAM services | OAuth | Daimler |
| **External Catalog Search API** | Catalog search, filtering, and category tree (delegated from Spryker; see [ADR-002](09-architecture-decisions/adr-002-external-catalog-search-delegation.md)) | REST API | Daimler |

### Integration Details

| Integration | Direction | Protocol | Sync/Async | Frequency / Constraints | Phase |
|-------------|-----------|----------|-----------|------------------------|-------|
| IParts → Spryker | Pull | REST | Sync | Catalog data + part info; volume TBD | Phase 1 |
| VIS → Spryker | Pull | REST | Sync | FIN/VIN validation + vehicle card, on demand | Phase 1 |
| TruckLog (DIMS-SOAP) → Spryker | Pull | SOAP / REST | Sync (read-through) | Availability; phased per market | Phase 1 |
| MB LogBus DIMS → Spryker | Pull | REST | Sync (read-through) | Availability fallback | Phase 1 |
| CRISP → Spryker | Pull | REST | Sync/batch | Customer organisation info | Phase 1 |
| IPS → Spryker (via S3) | Pull | SFTP / XML on S3 | Async batch | **Full import per store, ~1.5M `<SET>` tags, ~30 min target** | Phase 1 |
| RetailNet → Spryker | Pull | REST | Sync/batch | Dealer (merchant) org info | Phase 1 |
| Spryker ↔ Dealer Locator | Round-trip | Linkout | N/A | Redirect to find/establish dealer relation, redirect back | Phase 1 |
| Spryker ↔ FUSO / BPC | Round-trip | Linkout / REST | Sync | Redirect to external catalog, redirect back, then request basket details | Phase 1 |
| Spryker ↔ DTAG IAM | Bidirectional | OAuth (redirect + REST) | Sync | Customer/BO authentication (Authorization Code); **Glue-API SSO not GA** | Phase 1 |
| Spryker → External Catalog Search API | Push (query) | REST | Sync | PLP search/filter/category tree per request | Phase 1 |

### Component Rationale

Why each external component participates (from the TAD Components table).

| Component | Rationale |
|-----------|-----------|
| IParts | Authoritative source of the parts catalogue structure and part-to-vehicle fitment — the shop catalogue derives from it. |
| VIS | Validates FIN/VIN and supplies the vehicle data card so customers find the right parts for a registered vehicle. |
| TruckLog / MB LogBus DIMS | Live availability; TruckLog is the target global source, MB LogBus DIMS the fallback where TruckLog is not yet available. |
| CRISP | Customer organisation master data (org identity, structure) — dealer relations are owned by the Shop, not CRISP. |
| IPS | Source of prices + parts-master via XML — feeds the large full import that drives catalog and pricing. |
| RetailNet | Dealer/merchant organisation master data — seeds merchant records. |
| Dealer Locator | Helps a customer find and connect to a dealer; supports the registration flow via linkout. |
| FUSO / BPC | External catalogues for FUSO and Bus parts; customers punch out and return with a basket. |
| DTAG IAM | Central Daimler identity — user roles/rights and authentication for the platform. |
| External Catalog Search API | Owns catalog search/filter/category tree at Daimler scale (26M parts), removing that load from Spryker ES. |

### Connector Rationale

Near-verbatim from the TAD "Connectors information" table; numbers live in the Frequency/Volume/Constraints column.

| Interaction | Description | Type | Frequency / Volume / Constraints |
|-------------|-------------|------|----------------------------------|
| IParts → Spryker | Source of catalog data + part additional info | REST API | TBD (volume not stated in TAD) |
| VIS → Spryker | FIN/VIN validation + vehicle data card | REST API | On demand per lookup; TBD |
| TruckLog (DIMS-SOAP) → Spryker | Availability/stock info | SOAP / REST | Read-through per PDP/cart; phased per market |
| MB LogBus DIMS → Spryker | Availability/stock info | REST API | Read-through fallback |
| CRISP → Spryker | Customer organisation info | REST API | TBD |
| IPS → Spryker | XML files as source of data import | SFTP / XML on S3 | Full import per store, ~1.5M `<SET>` tags, ~30 min (not strict) |
| RetailNet → Spryker | Dealer (merchant) organisation info | REST API | TBD |
| Spryker ↔ Dealer Locator | Redirect to find/establish dealer relation, redirect back | Linkout | Per registration attempt |
| Spryker ↔ FUSO / BPC | Redirect to external catalog, redirect back, then request basket details | Linkout / REST | Per punch-out session |
| Spryker ↔ DTAG IAM | Customer authentication, OAuth flow | Redirect / REST | Per login; Glue-API SSO not GA |

> **TODO:** Fill the "TBD" volumes/frequencies for IParts, VIS, CRISP, RetailNet call rates and payload sizes. Owner: Daimler integration owners + solution architect (needed before load-test sizing in §10).

---

*Corresponds to [arc42 Section 3](https://docs.arc42.org/section-3/)*
