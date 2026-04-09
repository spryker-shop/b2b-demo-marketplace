# Newsletter Subscription — Task List

## Summary

Refactor the newsletter subscription footer component to match the new Figma design, align all visual properties with design tokens, and clean up legacy markup.

## Complexity

**Medium** — multiple files touched but all changes are CSS/Twig-only with no behavioral modifications.

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking responsive layout | High | Test all breakpoints after grid/column changes |
| Affecting other form--subscription consumers | Medium | Verify that `form--subscription` modifier is only used by this component |
| Missing button--hollow-icon side effects | Low | Search all usages before removing; the class has no SCSS definition |
| Footer CMS slot overrides | Medium | Check if any page layout overrides the `subscribe` block in footer |

---

## Tasks

### Task 1 — Verify Figma design values

- **ID**: `SUB-01`
- **Title**: Extract and document exact design values from Figma
- **Description**: Open the Figma design at node 789-17729 and document all visual properties: spacing, typography tokens, colors, border-radius, button variant, responsive breakpoints, success/error message styling. Update `[FIGMA]` placeholders in `prd.md`.
- **Target files**: `docs/refactor-subscription-newsletter/prd.md`
- **Dependencies**: None
- **Acceptance criteria**:
  - All `[FIGMA]` items in prd.md have concrete token values
  - Column proportions per breakpoint are documented
  - Button variant (filled/outlined/icon-only) is confirmed
  - Interactive states (hover, focus, active) are documented
- **PRD refs**: 3–6, 8–10, 14–19, 20–27, 28–31, 34–37, 40

---

### Task 2 — Create subscription molecule SCSS file + index.ts

- **ID**: `SUB-02`
- **Title**: Add missing SCSS and webpack entry for subscription molecule
- **Description**: Create `subscription.scss` with the `@mixin shop-ui-subscription` / `@include` pattern. Create empty `index.ts`. Add component-level layout styles (gap between columns on mobile, message container spacing). The SCSS file bridges the gap between the grid utility classes and any Figma-specific layout adjustments.
- **Target files**:
  - `src/Pyz/Yves/NewsletterWidget/Theme/default/components/molecules/subscription/subscription.scss` (NEW)
  - `src/Pyz/Yves/NewsletterWidget/Theme/default/components/molecules/subscription/index.ts` (NEW)
- **Dependencies**: `SUB-01` (need confirmed spacing values)
- **Acceptance criteria**:
  - SCSS file follows `@mixin shop-ui-subscription` pattern
  - Styles use only `var(--*)` design tokens
  - `index.ts` exists (can be empty)
  - Build succeeds: `npm run yves`
- **PRD refs**: 7, 23, 37

---

### Task 3 — Clean up subscription-info markup

- **ID**: `SUB-03`
- **Title**: Remove unnecessary wrapper div in subscription-info.twig
- **Description**: The title `<strong>` is wrapped in a pointless `<div>`. Remove it so the title element is a direct child of the component body. Verify no CSS targets this wrapper.
- **Target files**:
  - `src/Pyz/Yves/NewsletterWidget/Theme/default/components/molecules/subscription-info/subscription-info.twig`
- **Dependencies**: None
- **Acceptance criteria**:
  - `<div>` wrapper around `<strong class="title title--subscription">` is removed
  - Title renders identically (no visual regression)
  - No SCSS selectors targeted the removed `<div>`
- **PRD refs**: 38

---

### Task 4 — Update subscription-info SCSS

- **ID**: `SUB-04`
- **Title**: Align subscription-info styles with Figma tokens
- **Description**: Update `subscription-info.scss` to match Figma values. Adjust `__text` margins/spacing per confirmed design. Ensure all values use `var(--*)` tokens.
- **Target files**:
  - `src/Pyz/Yves/NewsletterWidget/Theme/default/components/molecules/subscription-info/subscription-info.scss`
- **Dependencies**: `SUB-01`, `SUB-03`
- **Acceptance criteria**:
  - All spacing values use `var(--scale-*)` tokens
  - Typography values use `var(--body-*-*)` tokens
  - Margins match Figma at all breakpoints
  - No hardcoded `rem()` or `px` values
- **PRD refs**: 9, 21, 24

---

### Task 5 — Update title--subscription modifier

- **ID**: `SUB-05`
- **Title**: Align title--subscription with Figma heading tokens
- **Description**: In `title/title.scss`, update the `&--subscription` block to match the Figma-specified heading scale. Currently uses `--heading-sm-*`. Confirm and adjust if needed.
- **Target files**:
  - `src/Pyz/Yves/ShopUi/Theme/default/components/atoms/title/title.scss`
- **Dependencies**: `SUB-01`
- **Acceptance criteria**:
  - Title font-size, weight, line-height, letter-spacing match Figma
  - Values use heading token scale (`var(--heading-{scale}-*)`)
  - No visual regression on other pages using `title--subscription` (search confirms only one usage)
- **PRD refs**: 8, 12

---

### Task 6 — Update form--subscription modifier

- **ID**: `SUB-06`
- **Title**: Align form--subscription input and button styles with Figma
- **Description**: In `form/form.scss`, update the `&--subscription` block:
  - Adjust input tokens (border, background, radius, padding) per Figma
  - Replace button styling to match Figma variant (filled/outlined/icon-only)
  - Add proper hover, focus-visible, and active states for the dark-background context
  - Verify gap between input and button
- **Target files**:
  - `src/Pyz/Yves/ShopUi/Theme/default/components/molecules/form/form.scss`
- **Dependencies**: `SUB-01`
- **Acceptance criteria**:
  - Input styling matches Figma (border, bg, radius, padding, typography)
  - Button styling matches Figma variant
  - Hover state on button matches Figma
  - Focus-visible state is clearly visible on teal background
  - Gap between input and button matches Figma
  - All values use `var(--*)` tokens
- **PRD refs**: 14–19, 22, 25–31

---

### Task 7 — Update subscription molecule Twig + message styles

- **ID**: `SUB-07`
- **Title**: Replace legacy utility classes on success/error messages, update button class
- **Description**:
  1. Replace `text-big text-center text-success` → `subscription__message subscription__message--success`
  2. Replace `text-big text-center text-alert` → `subscription__message subscription__message--error`
  3. Add corresponding BEM styles in `subscription.scss`
  4. Replace `button button--hollow-icon` with the correct button variant per Figma
  5. Consider adding `role="status"` for accessibility on the message container
- **Target files**:
  - `src/Pyz/Yves/NewsletterWidget/Theme/default/components/molecules/subscription/subscription.twig`
  - `src/Pyz/Yves/NewsletterWidget/Theme/default/components/molecules/subscription/subscription.scss`
- **Dependencies**: `SUB-01`, `SUB-02`, `SUB-06`
- **Acceptance criteria**:
  - No legacy utility classes (`text-big`, `text-center`, `text-success`, `text-alert`) remain
  - Messages use BEM naming under `subscription` namespace
  - Message typography and color use design tokens
  - Button class matches a defined button variant
  - Messages render correctly for both success and error states
- **PRD refs**: 32–37, 39–40

---

### Task 8 — Update footer__subscribe spacing (if needed)

- **ID**: `SUB-08`
- **Title**: Adjust footer subscribe container padding per Figma
- **Description**: If Figma specifies different container padding than current `--scale-32` / `--scale-40`, update the `&__subscribe` block in `footer.scss`.
- **Target files**:
  - `src/Pyz/Yves/ShopUi/Theme/default/components/organisms/footer/footer.scss`
- **Dependencies**: `SUB-01`
- **Acceptance criteria**:
  - Container padding matches Figma at all breakpoints
  - Values use `var(--scale-*)` tokens
  - No regression in footer layout
- **PRD refs**: 20

---

### Task 9 — Build verification and visual QA

- **ID**: `SUB-09`
- **Title**: Build, verify no regressions, cross-page check
- **Description**:
  1. Run `npm run yves` — confirm successful build
  2. Open homepage footer in browser at 375px, 768px, 1200px
  3. Compare against Figma design at each breakpoint
  4. Test form submit (success + error states)
  5. Keyboard-navigate to verify focus states on dark background
  6. Check cart page and at least one PDP to verify no footer regression
- **Target files**: None (verification only)
- **Dependencies**: `SUB-02` through `SUB-08`
- **Acceptance criteria**:
  - `npm run yves` completes without errors
  - Visual match at all three breakpoints
  - Success/error messages display correctly
  - Focus states visible on dark background
  - No regression on cart page or PDP footer
- **PRD refs**: Manual Verification section

---

## Task Dependency Graph

```
SUB-01 (Figma values)
  ├── SUB-02 (subscription SCSS + index.ts)
  │     └── SUB-07 (Twig + messages)
  ├── SUB-03 (subscription-info markup cleanup)
  │     └── SUB-04 (subscription-info SCSS)
  ├── SUB-05 (title--subscription SCSS)
  ├── SUB-06 (form--subscription SCSS)
  │     └── SUB-07 (Twig + messages)
  ├── SUB-08 (footer spacing)
  └── SUB-09 (verification) ← depends on all above
```

## Execution Order

1. `SUB-01` — Figma value extraction (manual, blocking)
2. `SUB-03` — Markup cleanup (no design dependency)
3. `SUB-02` — Create SCSS/index.ts files
4. `SUB-05` — Title modifier update
5. `SUB-04` — Subscription-info SCSS update
6. `SUB-06` — Form modifier update
7. `SUB-07` — Twig + message refactor
8. `SUB-08` — Footer spacing (if needed)
9. `SUB-09` — Build + visual QA
