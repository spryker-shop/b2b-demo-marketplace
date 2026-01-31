# Architecture as Code for Spryker Projects

Architecture documentation for Spryker B2B Marketplace platform, structured as version-controlled code using industry standards.
Learn more about motivation and our decisions here: [PUBLIC-DOC-GUIDELINE.md](PUBLIC-DOC-GUIDELINE.md)

## What's Inside

Out-of-the-box, this folder provides **structure, templates, and examples**. By adopting this approach, your project receives living architecture documentation that evolves with your codebase:

- **arc42 Documentation** - Industry-standard architecture documentation structure with templates
- **C4 Model Diagrams** - Example diagrams showing Context, Container, and Component views
- **Data Flow & Integration Diagrams** - Example diagrams for system interactions and data movements
- **Sequence Diagrams** - Example process flows and system behaviors
- **Architecture Decision Records (ADRs)** - Template and examples for documenting decisions
- **Solution Designs** - Template for RFC-style exploration documents

## Standards Used

- **[arc42](https://arc42.org/)** - Proven template for architecture documentation
- **[C4 Model](https://c4model.com/)** - Clear system visualization at multiple abstraction levels
- **[Mermaid](https://mermaid.js.org/)** - Diagrams as code (renders in GitHub, IDEs, and most markdown viewers)
- **[PlantUML](https://plantuml.com/)** - Diagrams as code for entity-relationship diagrams and precision modeling

## Documentation Structure

All sections follow the arc42 template:

| Section | Description |
|---------|-------------|
| [01 - Introduction and Goals](01-introduction-and-goals.md) | Requirements overview, quality goals, stakeholders |
| [02 - Constraints](02-constraints.md) | Technical, organizational, and convention constraints |
| [03 - System Scope and Context](03-system-scope-and-context.md) | System boundaries, external interfaces, integrations |
| [04 - Solution Designs](04-solution-designs/) | RFC-style exploration documents (before decisions) |
| [05 - Building Block View](05-building-block-view.md) | System decomposition and module structure |
| [06 - Runtime View](06-runtime-view.md) | Behavior and interactions at runtime |
| [07 - Deployment View](07-deployment-view.md) | Infrastructure and deployment topology |
| [08 - Crosscutting Concepts](08-crosscutting-concepts.md) | Overarching patterns and approaches |
| [09 - Architecture Decisions](09-architecture-decisions/) | ADRs documenting key decisions (after consensus) |
| [10 - Quality Requirements](10-quality-requirements.md) | Volume planning, testing strategy, quality scenarios |
| [11 - Risks and Technical Debt](11-risks-and-technical-debt.md) | Known risks and technical debt items |
| [12 - Glossary](12-glossary.md) | Domain terminology and definitions |

## Diagrams

All diagrams are code-based (Mermaid and PlantUML format) located in `diagrams/`. These are **examples** that must be adapted for your project:

- `c4/` - System Context (C1), Container (C2), Component (C3) diagram examples
- `data-flow/` - Data flow diagram examples showing data movement between systems
- `integration/` - Integration overview diagram examples with protocols and directions
- `sequence/` - Process flow and system interaction examples
- `erd/` - Entity-relationship diagrams (if needed)

## How to Use

1. If your current Spryker project doesn't have this folder - copy it over and start documenting your architecture
2. Start by filling in Section 1, Section 3, Section 10
3. Create/adapt C4 Level 1 diagram (system context)
4. Continue with other sections as needed

**For architecture changes:**
- Create a Solution Design first (exploration), then an ADR after decision (documentation)

**For diagrams:**
- Edit `.mmd` (Mermaid) and `.puml` (PlantUML) files directly - they render automatically in GitHub and most IDEs
- Adapt example diagrams to your project's needs

**For reviews:**
- Use standard Git pull requests for architecture review process

## Recommended Adoption Path

**Essential (start here):**
1. **Section 1** - Introduction and Goals
2. **Section 3** - System Scope and Context + **C4 Level 1 diagram** (system context)
3. **Section 10** - Quality Requirements (volume planning, testing strategy)

**Recommended next:**
4. **Section 5** - Building Block View + **C4 Level 2 diagrams** (containers)
5. **Section 2** - Constraints

**What makes it living:**
- **ADRs** (Section 9) - Document decisions as you make them
- **Solution Designs** (Section 4) - Explore options before deciding

**Nice to have:**
- Sections 6, 7, 8, 11, 12 - Importance depends on your project's specific needs and priorities

## Working with AI

AI tools (like Claude, Codex, Copilot) work exceptionally well with this structure:
- Markdown and code-based diagrams are AI-friendly formats
- Templates provide clear patterns for AI to follow
- Use AI to help draft sections, create diagrams, or refine documentation
- Ask AI to review your architecture docs for clarity and completeness

---

*This architecture documentation is version-controlled, collaborative, and evolves with your codebase.*
