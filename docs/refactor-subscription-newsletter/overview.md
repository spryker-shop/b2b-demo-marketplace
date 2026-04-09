# Newsletter Subscription Component — Refactoring Overview

## Figma Reference

**Design**: [Demo-DS-master, node 789-17729](https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=789-17729)

> **⚠️ FIGMA VERIFICATION REQUIRED**: The planner could not access Figma directly. All gaps identified below are based on a thorough code audit against the project's design token system and established patterns. Specific design values (colors, spacing, typography, layout proportions) must be verified against the Figma node before implementation.

## What Is Being Changed

The newsletter subscription strip in the footer — a teal-branded area containing a title, description text, email input, and submit button.

## Why

The current implementation has several technical inconsistencies with the project's design token system and component conventions:

1. **No SCSS file** for the `subscription` molecule — relies entirely on grid utilities with no component-level styling control
2. **Legacy utility classes** — `text-big`, `text-center`, `text-success`, `text-alert` for success/error messages are not token-based
3. **Orphan button class** — `button--hollow-icon` is referenced but has no definition in `button.scss`; the `form--subscription` modifier overrides `.button` styles anyway
4. **Unnecessary wrapper `<div>`** around the title in `subscription-info.twig`
5. **Missing focus/accessibility states** on the form within the dark-background context
6. **Old spacing patterns** — some margins use non-token values or mix token and non-token approaches

## High-Level Approach

Refactor-in-place approach: update existing files, add a missing SCSS file for the `subscription` molecule, and align all visual properties with design tokens. No structural rewrites — the component hierarchy (view → subscription → subscription-info + form) stays intact.

### Components Affected

| Component | File | Change Type |
|-----------|------|-------------|
| `subscription` molecule | `subscription.twig` | Minor Twig adjustments |
| `subscription` molecule | `subscription.scss` (NEW) | New SCSS file for component-level layout |
| `subscription` molecule | `index.ts` (NEW) | Empty webpack entry point |
| `subscription-info` molecule | `subscription-info.twig` | Minor markup cleanup |
| `subscription-info` molecule | `subscription-info.scss` | Token alignment, Figma-driven values |
| `form--subscription` modifier | `form/form.scss` | Button/input token updates per Figma |
| `title--subscription` modifier | `title/title.scss` | Token alignment per Figma |
| `footer__subscribe` | `footer/footer.scss` | Spacing/padding adjustments per Figma |

### What Stays Unchanged

- Widget view (`subscription-form.twig`) — pass-through, no changes needed
- Form behavior (Symfony form rendering, submit handling)
- Success/error event flow from controller
- Overall footer structure
- Grid system usage pattern (flex-based `.grid` + `.col--*`)
