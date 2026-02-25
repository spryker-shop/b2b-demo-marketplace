# System Scope and Context

Delimits your system from its (external) communication partners. Specifies the external interfaces.

## Business Context

Shows the system's business environment - all communication partners with business/technical reasoning.

**See diagram:** [C1 System Context](diagrams/c4/c1-system-context.mmd)

## Technical Context

Provide important details about external systems and interfaces that don't fit on the diagram above.

### External Systems

Describe external systems, their responsibilities, and key stakeholders:

| System | Description | Technology | Stakeholders |
|--------|-------------|------------|--------------|
| CIAM System | Identity and access management for all user types | OAuth 2.0 / SAML | *List contact person/team* |
| ERP System (SAP ECC 6.0) | Master system for orders and inventory | REST API | *List contact person/team* |
| PIM System (Akeneo) | Product data management and base prices | CSV export via S3 | *List contact person/team* |
| Payment Gateway | Payment processing | REST API / Webhooks | *List contact person/team* |

### Integration Details

Describe key integration characteristics (protocol, sync/async, frequency, constraints):

| Integration | Direction | Protocol | Frequency / Constraints |
|-------------|-----------|----------|------------------------|
| PIM → Spryker | Pull | CSV via S3 API | Daily batch import, ~10K products |
| Spryker → ERP | Push | REST API (sync) | Real-time order creation, <100/min limit |
| Spryker ↔ CIAM | Bidirectional | OAuth 2.0 / SAML | Real-time authentication |
| Spryker ↔ Payment | Bidirectional | REST + Webhooks | Real-time transactions |

---

*Corresponds to [arc42 Section 3](https://docs.arc42.org/section-3/)*
