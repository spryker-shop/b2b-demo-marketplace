# Risks and Technical Debt

Known technical risks and debt that should be addressed.

## Technical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **ERP Batch API Delivery** - ERP system (SAP ECC 6.0) needs to implement batch stock update API on time | Cannot receive near-real-time stock updates, project timeline at risk | Fall back to file-based import if API not ready X months before deadline |
| **CIAM Integration Uncertainty** - Unclear documentation with proprietary CIAM provider, integration pattern must be confirmed | Integration may fail or require significant rework | Either migrate to another CIAM provider, or start with native Spryker authentication and migrate to CIAM later |

## Technical Debt

| Item | Impact | Plan |
|------|--------|------|
| **Legacy Code Modules** - Some older modules lack proper test coverage | Risky to modify, slower development | Incrementally add tests during feature work |
| **Manual Deployment Steps** - Some deployment steps still manual | Risk of human error, slower releases | Automate remaining steps |

---

*Corresponds to [arc42 Section 11](https://docs.arc42.org/section-11/)*
