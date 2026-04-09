# Newsletter Subscription — Product Requirements Document

## Figma Reference

**Design**: [Demo-DS-master, node 789-17729](https://www.figma.com/design/6kdOU2Ez2KVChX26VSMPe7/Demo-DS-master?node-id=789-17729)

> **⚠️ FIGMA VERIFICATION REQUIRED** — Items marked with `[FIGMA]` need specific values confirmed from the design file before implementation.

---

## Goal

Align the newsletter subscription footer component with the new Figma design and the project's design token system, closing all visual/structural gaps while preserving existing behavior.

---

## Requirements

### Layout & Structure

1. The subscription strip MUST render inside the `footer__subscribe` container with `--background-brand-primary` background and `--text-inverse` text color.
2. The strip MUST use the existing grid system (`.grid` + `.col--*`) for the two-column layout (info left, form right).
3. `[FIGMA]` Column proportions on each breakpoint MUST match the Figma design. Current: `col--md-5/col--md-6` and `col--lg-6/col--lg-5`. Verify and adjust.
4. `[FIGMA]` Vertical alignment of the two columns (currently `grid--middle`) MUST match Figma. Verify if center-aligned or top-aligned.
5. `[FIGMA]` Horizontal distribution (currently `grid--justify` = space-between) MUST match Figma. Verify.
6. On mobile (`< md`), both columns MUST stack vertically, info on top, form below.
7. The subscription molecule MUST have its own SCSS file for component-level layout control.

### Typography

8. `[FIGMA]` Title MUST use the correct heading token scale. Current: `--heading-sm-*` (20px/28px semibold). Verify.
9. `[FIGMA]` Description text MUST use the correct body token scale. Current: `--body-md-*` (14px/20px regular). Verify.
10. `[FIGMA]` Input placeholder text MUST use `--body-md-*` tokens (currently correct). Verify size.
11. All typography MUST use `var(--*)` design tokens — no hardcoded `rem()` or `px` values.

### Colors

12. Title color MUST be `--text-inverse` (inherited from `footer__subscribe`).
13. Description color MUST be `--text-inverse` (inherited from `footer__subscribe`).
14. `[FIGMA]` Input background: currently `--input-background-default` (white). Verify.
15. `[FIGMA]` Input border: currently `--input-border-default`. Verify.
16. `[FIGMA]` Input text color: currently `--input-text-value`. Verify.
17. `[FIGMA]` Button background: currently `transparent`. Verify if filled or outlined.
18. `[FIGMA]` Button border color: currently `--border-subtle`. Verify.
19. `[FIGMA]` Button icon/text color: currently `--text-inverse`. Verify.

### Spacing

20. `[FIGMA]` Container vertical padding (footer__subscribe): currently `--scale-32` mobile / `--scale-40` desktop. Verify.
21. `[FIGMA]` Gap between title and description: currently `--scale-8` mobile / `--scale-16` desktop (via margin). Verify.
22. `[FIGMA]` Gap between input and button: currently `--scale-8` (via form--subscription gap). Verify.
23. `[FIGMA]` Gap between info column and form column: currently determined by grid space-between. Verify if explicit gap is needed.
24. `[FIGMA]` Description bottom margin on mobile (before form): currently `--scale-16`. Verify.

### Shape & Borders

25. `[FIGMA]` Input border-radius: currently `--radius-sm` (4px). Verify.
26. `[FIGMA]` Button border-radius: currently `--radius-sm` (4px). Verify.
27. `[FIGMA]` Input border width: currently `--stroke-sm` (1px). Verify.

### Interactive States

28. `[FIGMA]` Button hover state: currently changes `border-color` to `--border-default`. Verify background/color changes.
29. `[FIGMA]` Button focus-visible state: verify if focus ring is visible on dark background (may need custom `--focus-ring` adaptation).
30. `[FIGMA]` Input focus state: verify border color on focus within dark background context.
31. `[FIGMA]` Input hover state: verify.

### Success/Error Messages

32. Success message MUST use BEM naming (`subscription__message subscription__message--success`) instead of legacy utility classes (`text-big text-center text-success`).
33. Error message MUST use BEM naming (`subscription__message subscription__message--error`) instead of legacy utility classes.
34. `[FIGMA]` Success message typography: verify font size, weight, color against tokens.
35. `[FIGMA]` Error message typography: verify font size, weight, color against tokens.
36. `[FIGMA]` Message alignment: currently `text-center`. Verify.
37. `[FIGMA]` Message spacing from form: currently no explicit spacing. Verify.

### Markup Cleanup

38. The unnecessary wrapper `<div>` around `<strong class="title title--subscription">` in `subscription-info.twig` SHOULD be removed (single child, adds no styling or semantic value).
39. The `button--hollow-icon` class on the form submit SHOULD be replaced with a valid button variant or removed (no matching SCSS exists).
40. `[FIGMA]` Submit button: verify if it should show an icon (`chevron-right`), text, or both.

---

## Non-Functional Requirements

### Responsiveness
- Component MUST work correctly at all breakpoints: `< sm`, `sm`, `md`, `lg`, `xl`
- Column stacking on mobile MUST maintain visual hierarchy (info → form)

### Accessibility
- Form input MUST have an associated label (currently hidden via `display: none` in form--subscription — label exists in DOM for screen readers)
- Button MUST be keyboard-accessible
- Success/error messages SHOULD be announced to screen readers (consider `role="status"` or `aria-live="polite"`)

### Performance
- No new JS dependencies
- No layout shifts — spacing must be deterministic via CSS

### Maintainability
- All visual values MUST use design tokens (`var(--*)`)
- SCSS MUST follow the `@mixin shop-ui-{name}` / `@include` pattern
- BEM naming MUST be consistent with project conventions

---

## Out of Scope

- Newsletter form backend logic (controller, Symfony form type)
- Email validation behavior
- CMS widget configuration
- Other footer sections (logos, navigation, contact info)
- Translations / i18n content
- JS behavior changes

---

## UI/UX Summary

### Components Affected

| Component | Change |
|-----------|--------|
| `subscription` molecule | Add SCSS file, minor Twig cleanup |
| `subscription-info` molecule | Remove unnecessary wrapper div, verify token alignment |
| `form--subscription` modifier | Update button/input tokens per Figma |
| `title--subscription` modifier | Verify and update tokens per Figma |
| `footer__subscribe` | Verify and update spacing per Figma |

### Interaction Behavior

- User enters email → clicks arrow button (or presses Enter)
- On success: success message appears below form
- On error: error message appears below form
- No client-side validation changes

### Responsive States

| Breakpoint | Layout |
|------------|--------|
| `< md` | Single column, stacked (info on top, form below) |
| `≥ md` | Two columns side by side (info left, form right) |
| `≥ lg` | Same two-column, potentially wider info column |

---

## Design System Impact

### Tokens Used (Current)

| Token | Usage |
|-------|-------|
| `--heading-sm-*` | Title typography |
| `--body-md-*` | Description text, input text |
| `--background-brand-primary` | Container background (via footer) |
| `--text-inverse` | All text within strip |
| `--input-background-default` | Input bg |
| `--input-border-default` | Input border |
| `--input-text-value` | Input text color |
| `--input-text-placeholder` | Placeholder color |
| `--border-subtle` | Button border |
| `--radius-sm` | Input + button radius |
| `--stroke-sm` | Border width |
| `--scale-8` | Form gap, description margin |
| `--scale-16` | Description margin |
| `--scale-32` / `--scale-40` | Container padding |

### SCSS Changes

- **New file**: `subscription/subscription.scss` with `@mixin shop-ui-subscription`
- **Modified**: `subscription-info/subscription-info.scss` — token updates
- **Modified**: `form/form.scss` — `&--subscription` block updates
- **Possibly modified**: `title/title.scss` — `&--subscription` block
- **Possibly modified**: `footer/footer.scss` — `&__subscribe` block

---

## Edge Cases

1. **Empty email input** — form validation handles this server-side; no visual change needed
2. **Long success/error messages** — must not overflow container; use `word-wrap: break-word`
3. **Very narrow viewport** (`< 320px`) — single column must not clip or overflow
4. **Missing icon sprite** — if `chevron-right` icon is missing, button should still be clickable
5. **Multiple form submissions** — server handles dedup; messages replace each other (existing behavior)
6. **RTL languages** — not in scope for this project, but grid flex-wrap handles it naturally

---

## Manual Verification

### Steps

1. Navigate to the homepage footer section
2. Verify newsletter strip visual match against Figma at:
   - Mobile viewport (375px)
   - Tablet viewport (768px / md breakpoint)
   - Desktop viewport (1200px / lg breakpoint)
3. Check title typography (font-size, weight, line-height, color)
4. Check description text typography
5. Check input styling (border, background, placeholder color, padding)
6. Check button styling (border, background, icon color)
7. Hover over the button → verify hover state
8. Tab to the button → verify focus ring visibility on dark background
9. Submit with valid email → verify success message styling
10. Submit with invalid/duplicate email → verify error message styling
11. Resize browser from 1400px → 320px → verify responsive transition
12. Check other pages that may render the footer (cart page, PDP, etc.)

### Pages to Check

- Homepage (`/`)
- Cart page (`/cart`)
- Any PDP page
- Category listing page
