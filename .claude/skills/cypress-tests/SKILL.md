---
name: cypress-tests
description: >
  Use this skill whenever the user wants to create, run, review, or validate Cypress end-to-end
  tests in this project. Trigger on phrases like "add a cypress test", "write an e2e test",
  "run the cypress tests", "run e2e tests", "check my cypress test", "review this cypress spec",
  "does this test pass the quality gate", "cypress smoke test", or any request to test the
  storefront, backoffice, or merchant portal UI end-to-end. This project has its own
  project-owned Cypress baseline under `tests/cypress-boilerplate/` (vendored from
  spryker-projects/cypress-boilerplate, not a Composer dependency) — do not suggest reinstalling
  `spryker/cypress-tests` or `spryker/robotframework-suite-tests`, which have been removed.
---

# Cypress E2E Tests

This project's Cypress suite lives entirely under `tests/cypress-boilerplate/` as a
self-contained Node project — its own `package.json`, not a workspace of the root repo and
not a Composer dependency. It replaces Spryker's internal demo-shop Cypress/Robot Framework
packages, which are for Spryker's own core-feature testing and aren't guaranteed to work on a
customized project.

## Step 1 — Know the conventions before writing anything

- **Directory layout**: specs under `tests/cypress-boilerplate/cypress/e2e/<app>/<feature>/`,
  where `<app>` is `storefront`, `backoffice`, `merchant-portal`, or `glue`. Fixtures under
  `tests/cypress-boilerplate/cypress/fixtures/`. Reusable logic under
  `tests/cypress-boilerplate/cypress/support/` (`page-objects/`, `scenarios/`, `cy-commands/`,
  `glue-endpoints/`, `api-helper/`).
- **Naming**: spec files `<app>-<feature>.cy.ts` (e.g. `storefront-checkout.cy.ts`,
  `storefront-cart-smoke.cy.ts`); fixtures `<data-type>-data.json` (e.g.
  `checkout-data.json`); page objects `<app>-<section>-<page>.ts` (e.g.
  `storefront-checkout-address-page.ts`). Kebab-case throughout.
- **Locators**: use the `data-qa="..."` attribute convention already present in this
  project's Twig templates (this matches the vendored boilerplate's own specs). Don't
  introduce `data-testid`, `data-cy`, or other attribute conventions. Where no `data-qa`
  hook exists yet for an element a test needs, prefer stable semantic selectors already in
  use in this suite (`[itemprop="..."]`, custom element tag names like `cart-counter`,
  `product-item`) before adding a brand-new attribute; if you do add a `data-qa` attribute
  to a template, keep the name consistent with sibling attributes nearby.
- **Page Object pattern**: specs never call `cy.get()`/`cy.visit()` directly — they only
  call methods on page-object classes (extend `AbstractPage`, one class per page/section).
  Selectors live exclusively inside page objects. Reuse an existing page object/scenario
  before writing a new one — check `tests/cypress-boilerplate/cypress/support/page-objects/`
  and `.../scenarios/` first.
- **TypeScript path aliases**: import fixtures via `@fixtures/*` and support code via
  `@support/*` (see `tests/cypress-boilerplate/tsconfig.json`), not relative `../../..` paths.
- **Deterministic setup/cleanup**: reset any state a test mutates in a `before`/`beforeEach`
  hook (e.g. `GlueCartsScenarios.deleteAllShoppingCarts`,
  `GlueAddressesScenarios.deleteAllCustomerAddresses`) rather than assuming a clean starting
  state or relying on spec execution order. Generate unique data at runtime (e.g. cart names
  from `new Date().toISOString()`) instead of hardcoding values that collide across runs.

## Step 2 — Create a test

1. Identify the existing page objects/scenarios that already cover the flow (search
   `tests/cypress-boilerplate/cypress/support/page-objects/` and `.../scenarios/`) — extend
   them rather than duplicating selector logic.
2. Add any missing page-object methods needed, following the pattern in neighboring files
   (plain class extending `AbstractPage`, arrow-function methods returning
   `Cypress.Chainable` or `void`).
3. Write the spec importing fixtures/page-objects via the `@fixtures`/`@support` aliases,
   with a `before`/`beforeEach` reset step, then the scenario steps, then assertions that
   check meaningful content (product name/price, order status, form values) — not just
   `.should('exist')` or `.should('be.visible')` alone.
4. If new fixture data is needed, add it as a new `<data-type>-data.json` file (or extend an
   existing one) — keep it project-owned (real values that exist in this project's own
   demodata under `data/import/common/`, not invented or copied from elsewhere).

## Step 3 — Run tests

All commands run from `tests/cypress-boilerplate/`:

```bash
cd tests/cypress-boilerplate
npm ci                                              # first time / after package.json changes
npx cypress open                                   # interactive, local environment
npm run cy:run                                     # headless, local environment
npx cypress run --env environment=ci --spec "cypress/e2e/storefront/cart/*.cy.ts"   # headless, targeted spec, ci environment
```

No `.env` setup step is needed to get started — per-environment values already live in
`tests/cypress-boilerplate/.envs/.env.<environment>` (`local`, `ci`, ...) and are loaded
automatically by `cypress.config.ts` based on the `--env environment=...` flag. Only add a
project-root `.env` file (loaded automatically if present, on top of the `.envs/` file) if you
need a secret that shouldn't be committed to `.envs/`.

Environment is selected with `--env environment=<local|ci|testing|staging|production>`
(default `local`); each maps to a file in `tests/cypress-boilerplate/.envs/`. CI runs the same
`npx cypress run --env environment=ci ...` command against the Docker/SDK-booted acceptance
stack (see `.github/workflows/ci.yml`, job `cypress-e2e`) — running it the same way locally
before pushing catches most CI failures early.

## Step 4 — Review and validate (quality gate)

Before considering a Cypress test done, check every item — this mirrors what CI enforces
(`.github/workflows/ci.yml` jobs `cypress-quality-gate` and `cypress-e2e`, which block the PR
on failure):

- [ ] **Tests pass**: `npm run cy:run` (or a targeted `--spec` run) is green.
- [ ] **Lint clean**: `npm run lint:check` passes.
- [ ] **Formatting clean**: `npm run prettier:check` passes (or run `npm run code:fix` to
      auto-fix both lint and formatting, then re-check).
- [ ] **No brittle selectors**: no raw `cy.get()` calls inside spec (`.cy.ts`) files — only
      inside page objects. No selectors based on nth-child/positional CSS, generated
      class-hash names, or XPath.
- [ ] **Deterministic setup/cleanup**: any mutated state (carts, addresses, orders) is reset
      in a `before`/`beforeEach` hook; no dependency on other specs having run first or on a
      specific database state that isn't reset by the test itself.
- [ ] **Clear assertions**: each test asserts on specific, meaningful values (not just
      presence/visibility) and failure messages would tell you what actually broke.

If any item fails, fix it before reporting the test as complete — don't ask the user to
confirm success without having actually run these checks.

## Reference

- `tests/cypress-boilerplate/README.md` — this project's own setup notes.
- Upstream boilerplate wiki (background/rationale, not a live dependency):
  [Best Practices](https://github.com/spryker-projects/cypress-boilerplate/wiki/Best-Practices),
  [Naming Conventions](https://github.com/spryker-projects/cypress-boilerplate/wiki/Naming-Conventions),
  [Configuration & Environment Variables](https://github.com/spryker-projects/cypress-boilerplate/wiki/Configuration-&-Environment-Variables).
