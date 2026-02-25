# Architecture Constraints

Any requirement that constrains software architects in their freedom of design decisions or the development process.

## Technical Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| Must integrate with SAP ECC 6.0 | Legacy ERP system (orders & inventory) to be replaced in 2027, REST API only |
| CIAM System integration required | Existing Auth0 tenant for all user authentication |
| PIM System: Akeneo PIM | Product data source, limited to scheduled batch imports |
| GDPR compliance | EU market presence, affects data storage and processing locations |
| Browser support: IE11+ | Corporate customer base requirement, limits frontend technology choices |

## Organizational Constraints

| Constraint | Background / Motivation |
|------------|------------------------|
| Team size: 8 developers | Limits parallel work and complexity |
| Monthly release cycle | Defines deployment cadence |
| Distributed team | Requires async communication |

## Conventions

| Convention | Background / Motivation |
|------------|------------------------|
| PHPStan level 6 | Static analysis standard for code quality |
| arc42 documentation | Architecture documentation standard |
| GitFlow | Version control workflow |

---

*Corresponds to [arc42 Section 2](https://docs.arc42.org/section-2/)*
