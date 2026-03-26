# Project Instructions

## Auto-update skills

Whenever I discover or confirm a new pattern, convention, or non-obvious behaviour specific to this project, I must update the relevant skill file in `.claude/skills/` **without being asked**.

### When to update

- New Twig/SCSS/TS pattern confirmed by working code
- Spryker API or helper function used successfully
- Component override/extend approach that differs from vendor docs
- Grid, design token, or build system behaviour clarified through actual usage
- A mistake was made and the correct approach was found — add it as a rule

### Which skill to update

| Topic | Skill file |
|-------|-----------|
| Twig templates, BEM, SCSS mixins, design tokens, line-clamp, grid | `frontend-markup` |
| Component structure, override/extend, include/embed, vendor atoms | `frontend-components` |
| Webpack, build commands, index.ts, lazy/eager loading, namespaces | `frontend-build` |

If a finding doesn't fit any existing skill, create a new one under `.claude/skills/`.

### How to update

- Add to the relevant section — don't duplicate existing content
- Keep entries concrete: show code, not just prose
- Mark project-specific deviations from Spryker docs clearly
