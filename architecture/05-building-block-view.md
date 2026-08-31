# Building Block View

Static decomposition of the system into building blocks (modules, components, subsystems) and their relationships.

## c4 level 2: Container View

**See diagram:** [C2 Container View](diagrams/c4/c2-spryker-container.mmd)

### Main Containers

| Container | Responsibility |
|-----------|----------------|
| **Yves (Storefront)** | Customer-facing web application |
| **Zed (Backend)** | Business logic, back-office, APIs |
| **Glue API** | RESTful API layer |

## C4 Level 3: Component View

**See diagram:** [C3 Component View](diagrams/c4/c3-component-diagram.mmd)

### Zed Application Layers

- **Presentation** - Controllers, Forms, UI components
- **Communication** - Facades, Plugins, Gateway (RPC)
- **Business** - Business models and logic
- **Service** - Shared utilities
- **Persistence** - Entities, Repositories, Entity Managers

---

*Corresponds to [arc42 Section 5](https://docs.arc42.org/section-5/)*
