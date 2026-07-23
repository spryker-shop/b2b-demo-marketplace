# Risks and Technical Debt

The POC's risks are dominated by **missing external specs** and **two genuine discovery areas** (dynamic
pricing, configurable-product feasibility). Mitigations that rely on a Spryker capability name it and
link the docs.

## Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **External API specs unavailable (all 4: CIAM, FMAT, SECM, PIM-SPISA)** | Integration cannot be finalised; POC blocked. | Request specs early; build against **mock APIs** behind per-system Client modules so real endpoints swap in cleanly; block integration finalisation until specs land. |
| **Dynamic service/price modelling (no proven pattern)** | Services aren't Spryker catalog products; wrong model risks rework of cart/checkout. | Technical discovery in **SD-001** (3 options A/B/C); spike the chosen approach. Options build on OOTB **Cart/Checkout** and, for option B, **SSP Service Management** ([docs](https://docs.spryker.com/docs/pbc/all/self-service-portal/latest/ssp-service-management-feature-overview)). |
| **Configurable Product compatibility with PIM-SPISA unknown** | Service wizard (Future) may not be feasible via OOTB external configurator. | Discovery in **SD-002**; confirm PIM-SPISA can act as an external configurator (parameter redirect contract). May need a custom wizard. [docs](https://docs.spryker.com/docs/pbc/all/product-information-management/latest/base-shop/tutorials-and-howtos/howto-create-a-product-configurator) |
| **FMAT post-order = 2 separate integrations** | Two independent async contracts; mis-modelling couples them. | Request both specs; implement each as an independent **OMS command** on its own transition. [docs](https://docs.spryker.com/docs/pbc/all/order-management-system/latest/base-shop/state-machine-cookbook/state-machine-cookbook-state-machine-fundamentals) |
| **Role-mapping complexity (6–8 CIAM roles)** | Over-modelling ACL for a POC wastes time. | Start with a simple **1:1** mapping to Spryker **Company Roles**; agree a POC subset. [docs](https://docs.spryker.com/docs/pbc/all/customer-relationship-management/latest/base-shop/company-account-feature-overview/company-user-roles-and-permissions-overview) |
| **No docs for the old Scania project** | Reverse-engineering CIAM/FMAT patterns costs time. | Review the existing code; allocate reverse-engineering time. |
| **POC scope creep** | Timeline/demo at risk. | Strict out-of-scope list; additions need explicit approval. |
| **DMS GA/maturity uncertain from docs** | Multi-store proof could hit an early-access limitation. | Confirm DMS status against the target release's release notes before committing; validating DMS is itself part of the POC. [docs](https://docs.spryker.com/docs/pbc/all/dynamic-multistore/latest/base-shop/dynamic-multistore-feature-overview) |

## Technical Debt (accepted for POC, must revisit for production)

These are deliberate POC shortcuts, not accidental debt.

| Item | Impact | Plan (Future) |
|------|--------|---------------|
| **No caching of assets/services/prices (live fetch)** | External latency directly hits UX; load on FMAT/SECM/PIM-SPISA. | Introduce asset caching/sync; consider P&S for a synced catalog. |
| **Fire-and-forget FMAT notifications (no retry/error handling)** | Lost notifications on FMAT downtime go unnoticed. | Add retry/back-off + monitoring on the OMS commands. |
| **Exception on customer-creation failure (no graceful path)** | Poor UX on SSO edge cases. | Graceful error handling across all integrations. |
| **Invoice only, no PSP** | Not a real payment flow. | Real PSP integration (ACP app or native PSP module). [docs](https://docs.spryker.com/docs/pbc/all/payment-service-provider/latest/payment-service-provider) |
| **No OMS customization** | Only basic statuses. | Model service-specific order lifecycle if needed. |
| **One global price / global catalog** | No per-country differentiation. | Per-store price dimensions if services/prices differ by country. |

## Open Questions for Client (from the TAD — 20)

- **CIAM:** provider? full payload spec? the 6–8 roles & permissions? purchased/licensed products per
  customer + format?
- **FMAT:** asset attributes (VIN/model/year/mileage/location)? max assets per customer? full API spec?
  both post-order specs (order-creation, payment+activation)?
- **SECM:** are services categorized? price fixed or variable? service types & structure? full API spec?
- **PIM-SPISA:** does it return wizard steps or does Spryker define them? is the external-configurator
  endpoint capable? full API spec?
- **All:** API protocol (REST/SOAP/GraphQL)?
- **Cart:** quantity changeable or always 1 per service/asset? price re-validation timing?
- **Checkout:** is an approval workflow needed? (POC suggestion: no.)
- **Multi-Store:** will services/prices differ per country in future?

---

*Corresponds to [arc42 Section 11](https://docs.arc42.org/section-11/)*
