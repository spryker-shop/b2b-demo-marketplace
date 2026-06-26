---
name: cypress-e2e-test
description: Use the skill to create, update, fix, edit or run Cypress E2E tests in "tests/cypress-tests/**" ensures tests follow Spryker standards and best practices
---

**Testing rule**
Cypress tests MUST follow Spryker conventions: mission-critical user journeys, Page Object Model with repository pattern, Inversify DI, fixture-based data, and tag-based filtering.

## Critical Instructions

**Scope**: Test end-to-end user flows across Storefront (Yves), Backoffice (Zed), Merchant Portal (MP), and API layers.
**Focus**: Happy-path journeys, representative error cases, and cross-interface effects — NOT UI styling, backend logic, or edge cases.
**Structure**: One test per distinct outcome. Group checks only when they represent a single user goal.
**Data**: Use static fixtures for smoke tests; use dynamic fixtures (via API) for feature tests.
**Page Objects**: All UI interactions go in page classes. Tests never use raw `cy.get()` selectors directly.
**Repositories**: Selector logic lives in a dedicated interface file (`<feature>-repository.ts`) plus at least one implementation file in a `repositories/` subfolder (e.g. `repositories/suite-<feature>-repository.ts`). Never collapse these into a single concrete class — even when only suite is supported. See the Repositories section for the exact file layout.
**DI**: Use `container.get(PageClass)` for all page and scenario instances — never `new`.
**Naming**: camelCase for variables/functions, PascalCase for classes, kebab-case for files. No abbreviations — write `authentication`, not `auth`.
**Reuse**: Search existing pages, scenarios, commands, and fixtures before creating anything new. Priority: Reuse > Update > Create.

---

## Discovery — Run this FIRST

Before writing any test, discover the feature's UI surface and confirm the journey list with the user. Skipping this step leads to tests that miss critical flows or duplicate existing coverage.

**Step 1 — Locate feature sources:**
- Module code: grep `src/Spryker/<FeatureName>`, `src/SprykerFeature/<FeatureName>`, `src/SprykerShop/<FeatureName>`
- Yves templates: `src/**/Yves/**/Theme/**/*.twig`
- Backoffice templates: `src/**/Zed/**/Presentation/**/*.twig`
- Controllers + routes: `src/**/{Yves,Zed}/**/Controller/*Controller.php`
- Existing cypress coverage: `tests/cypress-tests/cypress/e2e/**/*<feature>*`, `tests/cypress-tests/cypress/support/pages/**/<feature>*`

**Step 2 — Read the PRD if one exists:**
- Check `resources/plan/PRD/Features/<FeatureName>/` for a `*.prd.md` file
- Extract user stories and acceptance-criteria Gherkin scenarios — these map almost 1:1 to `it(...)` blocks

**Step 3 — Present a journey list to the user for confirmation.** Format:
```
Feature: <name>
Layer(s) detected: Yves / Backoffice / MP
Proposed test journeys:
  1. <Main success flow from AC 1.1>
  2. <Validation or error path from AC 1.2>
  3. <Role-specific variation from AC X>
Demoshops to cover: [suite] (default) — add b2b / b2b-mp?
Existing cypress coverage found: <list or "none">
```

Wait for user sign-off (or adjustments) before generating files. If a PRD has 10+ user stories, pick the 3–5 most critical happy-path + error journeys — don't try to cover every Gherkin scenario.

---

## What to Cover

- Mission-critical happy paths: login, checkout, registration, order management
- Key error/validation cases (1–2 per feature): missing field, invalid input, duplicate entry
- Cross-interface flows: MP configuration → storefront display, Zed update → MP status
- Role-specific journeys: guest vs. authenticated customer, merchant on MP, admin on Zed
- API endpoints: at least one positive and one negative user journey with schema validation

## What NOT to Cover

- Visual styles, colors, fonts, layout, pixel-perfect checks
- Exhaustive validation-rule combinations (that belongs in unit/functional tests)
- Complex data relationships or database state validation
- Backend business logic in isolation
- Rarely used features or internal tools (unless mission-critical)
- Highly dynamic or randomized content
- Third-party sites or services you do not control
- Flows already covered at the unit or functional test level

### Scenario count guidance

- 1 test — main success flow
- 1–2 tests — key validation or error paths
- +1 test per distinct role/interface variation (only when behavior differs)

---

## Test File Location & Naming

```
tests/cypress-tests/cypress/e2e/
├── smoke/          # Static fixtures only, no CLI commands
├── demo/           # Isolated demo-only group (this repo) — runs ONLY via `npm run cy:demo`
├── yves/           # Storefront feature tests
├── backoffice/     # Backoffice feature tests
├── mp/             # Merchant Portal feature tests
└── api/            # API endpoint tests
```

**File name**: `{feature-name}.cy.ts` — e.g. `basic-checkout.cy.ts`, `product-attachment-management.cy.ts`

### The `demo` group (this repository, master-demo)

`cypress/e2e/demo/` is an **isolated** group for demo-shop-only feature coverage (e.g. the AI Commerce
features that ship only in `master-demo`). It must be runnable on its own and must NOT be swept up by
any other run:

- **Command**: `npm run cy:demo` → `cypress run --spec "cypress/e2e/demo/**/*.ts" --headless --browser chrome`
- **Excluded everywhere else**: the other globs use `!(smoke|demo)` so `cy:ci` and `cy:run` skip it;
  `cy:smoke` only matches `smoke/**`. Do NOT touch `cy:ci:ssp` for this — its filename filter `(ssp)*`
  already excludes demo specs (use plain feature filenames, never an `ssp`-prefixed one, under `demo/`).
- **CI**: a dedicated `Run Tests (Demo)` step (id `run_demo_tests`, `if: always()`) runs `cy:demo` as its
  own step in the Cypress/UI job, mirroring the SSP step — its failures are reported independently.
- **Tags**: use `@demo` as the layer tag (plus a `@<feature>` tag and a real module tag). Static fixtures
  only, same as smoke — no dynamic fixtures, no CLI commands, no AI-provider calls.
- **Fixtures**: `fixtures/{repositoryId}/demo/<feature>/static-<feature>.json`.
- **Types**: `support/types/demo/index.ts` (re-export per-feature interface files), aliased as `@interfaces/demo`.
- **Guard** non-b2b-mp repos at the top of the `describe` with `it.skip(...)` + `return`, since the demo
  features only ship in `b2b-mp`.

---

## Test Structure

```typescript
import { container } from '@utils';
import { FeatureStaticFixtures, FeatureDynamicFixtures } from '@interfaces/yves';
import { SomePage, AnotherPage } from '@pages/yves';
import { SomeScenario } from '@scenarios/yves';

describe('feature name', { tags: ['@yves', '@feature-name', 'feature-name', 'spryker-core'] }, (): void => {
  const somePage = container.get(SomePage);
  const someScenario = container.get(SomeScenario);

  let staticFixtures: FeatureStaticFixtures;
  let dynamicFixtures: FeatureDynamicFixtures;

  before((): void => {
    ({ staticFixtures, dynamicFixtures } = Cypress.env());
  });

  beforeEach((): void => {
    // Login or session setup if required per test
  });

  it('user should be able to complete the main success flow', (): void => {
    somePage.visit();
    someScenario.execute({ param: dynamicFixtures.entity });

    cy.contains(somePage.getSuccessMessage()).should('be.visible');
  });

  it('user should see validation error when required field is missing', (): void => {
    somePage.visit();
    somePage.submitEmptyForm();

    cy.contains('Field is required').should('be.visible');
  });
});
```

**Key rules**:
- Always destructure both `staticFixtures` and `dynamicFixtures` from `Cypress.env()` in `before()`
- Use `beforeEach()` for per-test login via scenario — never manually repeat login steps in each test
- Use helper functions inside `describe` for repeated interaction sequences
- Never use `cy.get()` directly in test files — always go through a page object

---

## Tags

Every `describe()` block MUST include tags:

```typescript
{ tags: ['@smoke', '@checkout', 'checkout', 'spryker-core'] }
```

**Layer tags**: `@smoke`, `@yves`, `@backoffice`, `@mp`, `@api`
**Feature tags**: `@feature-name` (with `@`) + `feature-name` (without `@`) — include both
**Module tags**: `spryker-core`, `spryker-core-back-office`, `marketplace-merchantportal-core`

**Critical**: Tags without `@` prefix MUST match actual feature repository names from `github.com/spryker-feature`. Do NOT invent or guess these tags — they are used for contextual test execution in the Release App.

---

## Repository-Aware Test Skipping

```typescript
// Skip for specific demoshops at describe level
if (['b2c', 'b2b'].includes(Cypress.env('repositoryId'))) {
  it.skip('skipped because tests run only for suite, b2b-mp and b2c-mp', () => {});
  return;
}

// Skip a single test using a helper function
function skipB2BIt(description: string, testFn: () => void): void {
  (['b2b', 'b2b-mp'].includes(Cypress.env('repositoryId')) ? it.skip : it)(description, testFn);
}

// Select value based on demoshop
function getPaymentMethod(): string {
  return ['b2c-mp', 'b2b-mp'].includes(Cypress.env('repositoryId'))
    ? 'dummyMarketplacePaymentInvoice'
    : 'dummyPaymentInvoice';
}
```

---

## Page Objects

**Location**: `tests/cypress-tests/cypress/support/pages/{yves|backoffice|mp}/`

```typescript
import { autoWired, REPOSITORIES } from '@utils';
import { inject, injectable } from 'inversify';
import { YvesPage } from '@pages/yves';
import { FeatureRepository } from './feature-repository';

@injectable()
@autoWired
export class FeaturePage extends YvesPage {
  @inject(REPOSITORIES.FeatureRepository) private repository: FeatureRepository;

  protected PAGE_URL = '/feature-path';

  submitForm = (params: { name: string; email: string }): void => {
    this.repository.getNameInput().type(params.name);
    this.repository.getEmailInput().type(params.email);
    this.repository.getSubmitButton().click();
  };

  getSuccessMessage = (): string => 'Successfully saved';
}
```

- Extend `YvesPage`, `BackofficePage`, or `AbstractPage` depending on layer
- Methods return `void` or `Cypress.Chainable` — never raw DOM values
- No `cy.get()` calls inside page classes — delegate to the repository

---

## Repositories (Selector Logic)

**This pattern is the single most-skipped rule. Pay extra attention.** Selector code MUST NOT live in the page class; it MUST be split into an **interface file** and a **per-demoshop implementation file** injected via DI. Why: selectors drift between demoshop variants (suite / b2b / b2b-mp render slightly different markup), and the interface is the contract that lets us swap a variant without rewriting the page or the test. A single concrete repository class collapses that flexibility and breaks the pattern.

### Required file layout

```
cypress/support/pages/<layer>/<feature>/
├── <feature>-page.ts                       ← the Page class (injects the repository)
├── <feature>-repository.ts                 ← the INTERFACE — selector contract
└── repositories/
    └── suite-<feature>-repository.ts       ← the suite IMPLEMENTATION
    └── b2b-<feature>-repository.ts         ← (optional, only if user confirmed in Discovery)
    └── b2b-mp-<feature>-repository.ts      ← (optional, only if user confirmed in Discovery)
```

### Interface file — `<feature>-repository.ts`

Must declare `export interface`, one getter per selector, no implementations.

```typescript
export interface FeatureRepository {
  getNameInput(): Cypress.Chainable;
  getEmailInput(): Cypress.Chainable;
  getSubmitButton(): Cypress.Chainable;
  getSuccessMessageSelector(): string;
}
```

### Suite implementation file — `repositories/suite-<feature>-repository.ts`

Must live inside the `repositories/` subfolder, must `implements FeatureRepository`, must be `@injectable`, must use `[data-qa="..."]` selectors.

```typescript
import { injectable } from 'inversify';
import { FeatureRepository } from '../<feature>-repository';

@injectable()
export class SuiteFeatureRepository implements FeatureRepository {
  getNameInput = (): Cypress.Chainable => cy.get('[data-qa="feature-name-input"]');
  getEmailInput = (): Cypress.Chainable => cy.get('[data-qa="feature-email-input"]');
  getSubmitButton = (): Cypress.Chainable => cy.get('[data-qa="feature-submit-button"]');
  getSuccessMessageSelector = (): string => '[data-qa="feature-success-message"]';
}
```

### ❌ Anti-pattern — DO NOT DO THIS

A single concrete class with selectors inlined:
```typescript
// BAD: violates the interface+variant pattern
@injectable()
export class FeatureRepository {
  getNameInput = (): Cypress.Chainable => cy.get('[data-qa="feature-name-input"]');
  // …
}
```

This is the most common failure mode — don't collapse the interface and impl into one class, even when only suite is supported. The interface still matters because:
- DI uses `REPOSITORIES.FeatureRepository` as a `Symbol.for(...)` token — the interface is what's bound.
- Future demoshop variants become a 30-line addition rather than a refactor of every consumer.

### Selector priority

1. `[data-qa="..."]` — always preferred
2. `id` attribute — `[id="setting-key"]` if no `data-qa`
3. `name` attribute — `[name="field_name"]` for form fields without `data-qa`
4. Never use CSS classes, nth-child, or text matching

### DI binding

After creating the two files, register in `cypress/support/utils/inversify/inversify.config.ts`:

```typescript
container.bind(REPOSITORIES.FeatureRepository).to(SuiteFeatureRepository).inSingletonScope();
```

And add the token to `types.ts`:
```typescript
export const REPOSITORIES = {
  // …existing
  FeatureRepository: Symbol.for('FeatureRepository'),
};
```

---

## Scenarios (Reusable Flows)

**Location**: `tests/cypress-tests/cypress/support/scenarios/{yves|backoffice|mp}/`

```typescript
import { inject, injectable } from 'inversify';
import { autoWired } from '@utils';
import { LoginPage } from '@pages/yves';

@injectable()
@autoWired
export class CustomerLoginScenario {
  @inject(LoginPage) private loginPage: LoginPage;

  execute = (params: ExecuteParams): void => {
    cy.session([params.email, params.password], () => {
      this.loginPage.visit();
      this.loginPage.login(params);
    });
  };
}

interface ExecuteParams {
  email: string;
  password: string;
  withoutSession?: boolean;
}
```

- Use `cy.session()` for login scenarios to avoid repeating auth across tests
- Encapsulate multi-page flows (e.g. full checkout) in a single scenario
- Scenarios inject pages via DI — never instantiate pages with `new`

---

## Fixtures

### Static Fixtures (pre-seeded data, always present)

**Location**: `tests/cypress-tests/cypress/fixtures/{repositoryId}/{layer}/{feature}/static-{test-name}.json`

```json
{
  "defaultPassword": "change123",
  "product": { "sku": "PRODUCT_SKU_001" }
}
```

### Dynamic Fixtures (entities created via API before the test)

**Location**: `tests/cypress-tests/cypress/fixtures/{repositoryId}/{layer}/{feature}/dynamic-{test-name}.json`

```json
{
  "data": {
    "type": "dynamic-fixtures",
    "attributes": {
      "synchronize": true,
      "operations": [
        {
          "type": "helper",
          "name": "haveConfirmedCustomer",
          "key": "customer",
          "arguments": [{ "password": "change123" }]
        },
        {
          "type": "helper",
          "name": "haveFullProduct",
          "key": "product",
          "arguments": [{}, { "idTaxSet": 1 }]
        }
      ]
    }
  }
}
```

**Auto-discovery rule**: fixtures are loaded automatically based on the test file path. A test at `cypress/e2e/yves/checkout/basic-checkout.cy.ts` loads:
- `fixtures/{repositoryId}/yves/checkout/static-basic-checkout.json`
- `fixtures/{repositoryId}/yves/checkout/dynamic-basic-checkout.json`

> ⚠️ **The fixture path mirrors the SPEC's path, exactly — `{spec-dir-relative-to-e2e}/static-{spec-filename}.json`. There is NO extra `{feature}` subfolder beyond what the spec's own directory already is.** `support/e2e.ts` computes it as `directoryPart = dirname(specPathRelativeToE2e)` and `filePart = specName.replace('.cy','')`. So a spec placed directly in a group dir, e.g. `cypress/e2e/demo/quicksight-analytics.cy.ts`, loads `fixtures/{repositoryId}/demo/static-quicksight-analytics.json` — **NOT** `…/demo/quicksight-analytics/static-quicksight-analytics.json`. Nesting the fixture one level too deep makes `Cypress.env('staticFixtures')` resolve to `undefined`, and the failure surfaces only at runtime as `TypeError: Cannot read properties of undefined (reading '<field>')` in `before`/`beforeEach` — typecheck, lint, and prettier all pass. **Always run the spec live (`cy:demo` / `cy:smoke` / the relevant `cy:ci:*`) before considering it done — never trust the static gates alone for fixture wiring.** The earlier "Directory Structure" diagrams show a `{feature}/` fixture subfolder ONLY because those example specs are themselves nested under a `{feature}/` directory; match the spec, not the diagram.

**Smoke tests**: use static fixtures only — no dynamic fixtures, no CLI commands.

---

## TypeScript Interfaces for Fixtures

**Location**: `tests/cypress-tests/cypress/support/types/{layer}/`

```typescript
// cypress/support/types/yves/index.ts
export interface FeatureStaticFixtures {
  defaultPassword: string;
  product: { sku: string };
}

export interface FeatureDynamicFixtures {
  customer: { email: string; id_customer: number };
  product: { abstract_sku: string };
}
```

Always define typed interfaces — never use `any` for fixture data.

---

## Inversify DI Registration

After adding a new page, repository, or scenario, register it in:

**File**: `tests/cypress-tests/cypress/support/utils/inversify/inversify.config.ts`

```typescript
import { SuiteFeatureRepository } from '../../pages/yves/feature/repositories/suite-feature-repository';
import { FeaturePage } from '../../pages/yves/feature/feature-page';
import { FeatureScenario } from '../../scenarios/yves/feature-scenario';

container.bind(REPOSITORIES.FeatureRepository).to(SuiteFeatureRepository).inSingletonScope();
container.bind(FeaturePage).to(FeaturePage).inSingletonScope();
container.bind(FeatureScenario).to(FeatureScenario).inSingletonScope();
```

Add the repository token to `types.ts`:

```typescript
export const REPOSITORIES = {
  // existing...
  FeatureRepository: Symbol.for('FeatureRepository'),
};
```

---

## Wait Strategies

**Good** — wait for observable state, not arbitrary time:
```typescript
cy.wait('@apiCall');                                          // wait for intercepted request
cy.get('[data-qa="spinner"]').should('not.exist');           // wait for loader to disappear
cy.get('[data-qa="data-table"]').should('contain', 'Item');  // wait for content to appear
```

**Bad** — never use arbitrary timeouts:
```typescript
cy.wait(5000); // arbitrary timeout — flaky, slow, and masks real issues
```

---

## Anti-Patterns

- `cy.wait(timeout)` without waiting for a specific condition
- Assertions inside page objects — assertions belong only in test specs
- Creating duplicate page objects — always search before creating
- Tests that depend on execution order of other tests
- Multiple unrelated scenarios in one test
- Hardcoded test data in test specs
- Inventing tags that don't exist in the Release App
- Over-testing — only write tests that are explicitly required

---

## Updating Existing Tests

When fixing or extending an existing test:

1. **Read first** — read the test spec, its page objects, scenarios, and fixtures before changing anything
2. **Identify root cause** — is the selector broken? did application behavior change? is it a timing issue?
3. **Search for patterns** — check if a similar fix or helper already exists elsewhere
4. **Plan minimal change** — what is the smallest change that resolves the issue?
5. **Maintain style** — follow the existing naming, structure, and patterns in the file
6. **Verify isolation** — confirm the change does not break other tests

---

## Debugging

- Use `cy.log('message')` to track test progression
- Add meaningful assertion messages: `cy.get(...).should('be.visible', 'Submit button should appear after form is filled')`
- Use `cy.intercept()` to observe network requests when asserting on API-driven content
- Use specific assertions — prefer `.should('contain', 'Success')` over `.should('exist')`

---

## Code Quality Checks

After any change to test files, always run:

```bash
cd tests/cypress-tests
npm run typecheck      # verify TypeScript types
npm run lint           # check code style
npm run prettier:write # format code
```

All three must pass before the task is complete.

**Then run the spec live — the static gates do NOT prove the test works.** Fixture path mistakes, selector drift, login/CSRF issues, and timing all pass typecheck/lint/prettier and fail only at runtime. Run the actual spec against the running app with the correct `ENV_REPOSITORY_ID` (e.g. `b2b-mp` for this repo) and confirm it goes green:

```bash
ENV_REPOSITORY_ID=b2b-mp npx cypress run --spec "cypress/e2e/<group>/<feature>.cy.ts" --headless --browser chrome
```

If the spec is guarded to a single demoshop, also run it under a different `ENV_REPOSITORY_ID` to confirm it **skips cleanly** (pending, not erroring) rather than throwing during DI/`container.get` resolution.

---

## Custom Commands Reference

```typescript
cy.visitBackoffice('/url')           // navigate to backoffice URL
cy.visitMerchantPortal('/url')       // navigate to merchant portal URL
cy.resetYvesCookies()                // clear storefront session
cy.resetBackofficeCookies()          // clear backoffice session
cy.runCliCommands(['console oms:check-condition'])  // trigger backend CLI commands
cy.runQueueWorker()                  // process message queues
cy.loadDynamicFixturesByPayload(filePath)  // load dynamic fixtures manually
cy.reloadUntilFound(url, selector, parentSelector, retries, wait)
```

---

## Running Tests

**Open interactive runner:**
```bash
cd tests/cypress-tests && npx cypress open
```

**Run all feature tests (non-smoke):**
```bash
cd tests/cypress-tests && npm run cy:run
```

**Run by layer:**
```bash
npm run cy:ci:yves        # storefront tests
npm run cy:ci:backoffice  # backoffice tests
npm run cy:ci:mp          # merchant portal tests
npm run cy:ci:api         # API tests
npm run cy:smoke          # smoke tests only
npm run cy:demo           # isolated demo-only group (this repo) — see "The demo group"
```

**Run a single file:**
```bash
cd tests/cypress-tests && npx cypress run --spec "cypress/e2e/yves/checkout/basic-checkout.cy.ts" --headless --browser chrome
```

**Run by tag:**
```bash
cd tests/cypress-tests && npx cypress run --env grepTags="@checkout" --headless --browser chrome
```

---

## Directory Structure for a New Feature

```
cypress/
├── e2e/yves/feature-name/
│   └── feature-name.cy.ts
├── fixtures/suite/yves/feature-name/
│   ├── static-feature-name.json
│   └── dynamic-feature-name.json
└── support/
    ├── pages/yves/feature-name/
    │   ├── feature-name-page.ts
    │   ├── feature-name-repository.ts
    │   └── repositories/
    │       └── suite-feature-name-repository.ts
    ├── scenarios/yves/
    │   └── feature-name-scenario.ts
    └── types/yves/
        └── index.ts  (add interface exports here)
```

## Checklist for a New Feature Test

0. **Discovery** (see top of file): grep feature sources, read PRD if present, confirm journey list with the user
1. Create static fixture JSON (always required) — start with `suite` under `fixtures/suite/<layer>/<feature>/`
2. Create dynamic fixture JSON (if entities need to be created) — same suite-first rule
3. Define TypeScript interfaces for both fixtures
4. Create the repository interface and a **`suite`** implementation first. Only add `b2b` / `b2b-mp` implementations if the user confirmed the feature ships in those demoshops during discovery — otherwise, flag it as a follow-up in your final summary
5. Create the page class extending the correct base page
6. Create a scenario class if the flow spans multiple pages
7. Register all bindings in `inversify.config.ts` and `types.ts`
8. Write the test file with `describe` tags and `before()` fixture loading
9. Run the test to verify it passes
