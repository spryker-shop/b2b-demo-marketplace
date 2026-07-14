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
**Repositories**: Selector logic lives in a dedicated `<feature>-repository.ts`, never inline in the page. Choose the shape by how many demoshop variants the markup actually has — **one concrete `@injectable @autoWired` class injected by its class token** when a single implementation covers every shop (the common case), OR an **interface + per-shop impls in `repositories/`** bound via a `REPOSITORIES` token only when the markup genuinely differs per demoshop. Do NOT default to the interface split — it was rejected on review (PR #330) as needless indirection for single-impl features. See the Repositories section for the decision rule and both layouts.
**DI**: Use `container.get(PageClass)` for all page and scenario instances — never `new`.
**Naming**: camelCase for variables/functions, PascalCase for classes, kebab-case for files. No abbreviations — write `authentication`, not `auth`.
**Reuse**: Search existing pages, scenarios, commands, and fixtures before creating anything new. Priority: Reuse > Update > Create.
**No comments**: NEVER write comments in test code — no `//`, no `/* */`, no `/** */`, on specs, pages, or repositories. This was flagged on PR #330 and made an absolute rule on PR #356. Intent goes in the `it(...)` description and in method/getter names, never a comment. The ONLY exception is a functional `// eslint-disable-*` / `@ts-*` pragma. Before finishing, grep your diff for added comment lines and delete every one. See "Comments in test code — DO NOT ADD COMMENTS".

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

- **Command**: `npm run cy:demo`. In this repo the script **self-pins** the demoshop:
  `ENV_REPOSITORY_ID=b2b-mp cypress run --spec "cypress/e2e/demo/**/*.ts" --headless --browser chrome`.
  So a bare `npm run cy:demo` Just Works from any shell regardless of what `.env` defaults to — no env
  prefix needed. (CI injects `ENV_REPOSITORY_ID` via `docker/sdk exec --env`, which agrees with the
  self-pin, so CI is unaffected.)
- **Excluded everywhere else**: the other globs use `!(smoke|demo)` so `cy:ci` and `cy:run` skip it;
  `cy:smoke` only matches `smoke/**`. Do NOT touch `cy:ci:ssp` for this — its filename filter `(ssp)*`
  already excludes demo specs (use plain feature filenames, never an `ssp`-prefixed one, under `demo/`).
- **CI**: a dedicated `Run Tests (Demo)` step (id `run_demo_tests`, `if: always()`) runs `cy:demo` as its
  own step in the Cypress/UI job, mirroring the SSP step — its failures are reported independently.
- **Tags**: use `@demo` as the layer tag (plus a `@<feature>` tag and a real module tag). Static fixtures
  only, same as smoke — no dynamic fixtures, no CLI commands, no AI-provider calls.
- **Fixtures**: `fixtures/b2b-mp/demo/static-<feature>.json` (the spec sits directly in `demo/`, so the
  fixture dir is `demo/` — NO extra `<feature>/` subfolder; see the fixture auto-discovery warning).
- **Types**: `support/types/demo/index.ts` (re-export per-feature interface files), aliased as `@interfaces/demo`.
- **No `repositoryId` guard.** Earlier demo specs guarded the `describe` with
  `if (!['b2b-mp'].includes(Cypress.env('repositoryId'))) { it.skip(...); return; }`. That guard was
  REMOVED (PR #330) — the demo command self-pins b2b-mp, fixtures resolve under `fixtures/b2b-mp/demo/`,
  and the `demo/` glob is excluded from every other run, so nothing else ever loads these specs. Adding
  the guard back is dead code. (Only re-introduce a guard if a demo spec is ever expected to be swept up
  by a multi-repo run — which the current isolation makes impossible.)

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
import { autoWired } from '@utils';
import { inject, injectable } from 'inversify';
import { YvesPage } from '@pages/yves';
import { FeatureRepository } from './feature-repository';

@injectable()
@autoWired
export class FeaturePage extends YvesPage {
  // Single-impl (common case): inject the concrete repository by its CLASS token.
  // Multi-variant only: `import { ..., REPOSITORIES } from '@utils'` and
  // `@inject(REPOSITORIES.FeatureRepository) private repository: FeatureRepository;`
  @inject(FeatureRepository) private repository: FeatureRepository;

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

Selector code MUST NOT live in the page class — it lives in a `<feature>-repository.ts` injected via DI. **Which of the two shapes you use depends on whether the markup genuinely differs per demoshop.** Picking the wrong shape is reviewable both ways: inlining selectors in the page is wrong, and so is wrapping a single implementation in an interface + `REPOSITORIES` token + multi-map binding (rejected on PR #330 as dead indirection — `Suite`-prefixing a class that is the *only* impl reads as "there are other variants" when there aren't).

### Decision rule — read this before creating files

- **One implementation covers every demoshop** (the common case — most features render identical markup everywhere): a **single concrete class** named `<Feature>Repository`, `@injectable @autoWired`, injected into the page by its **class token** (`@inject(FeatureRepository)`). No interface, no `repositories/` subfolder, no `REPOSITORIES` entry, no binding map. This is exactly how the existing `ConfigurationRepository` works — mirror it.
- **The markup genuinely differs per demoshop** (suite vs b2b vs b2b-mp render different DOM, e.g. `MultiFactorAuth`): an **interface** `<Feature>Repository` + one impl per shop under `repositories/` (`suite-…`, `b2b-…`, `b2b-mp-…`), bound through a `REPOSITORIES.<X>` token. The interface is the contract that lets DI swap the variant.

When unsure, start with the single concrete class. Promoting it to an interface later — *if* a second real variant appears — is a small, mechanical change; you are not locked in.

### Common case — single concrete class (DEFAULT)

```
cypress/support/pages/<layer>/<feature>/
├── <feature>-page.ts                       ← Page class; `@inject(FeatureRepository)`
└── <feature>-repository.ts                 ← the concrete repository (selectors live here)
```

```typescript
// <feature>-repository.ts
import { autoWired } from '@utils';
import { injectable } from 'inversify';

@injectable()
@autoWired
export class FeatureRepository {
  getNameInput = (): Cypress.Chainable => cy.get('[data-qa="feature-name-input"]');
  getEmailInput = (): Cypress.Chainable => cy.get('[data-qa="feature-email-input"]');
  getSubmitButton = (): Cypress.Chainable => cy.get('[data-qa="feature-submit-button"]');
  getSuccessMessageSelector = (): string => '[data-qa="feature-success-message"]';
}
```

`@autoWired` self-binds the class (`container.bind(FeatureRepository).toSelf()`), so `@inject(FeatureRepository)` in the page resolves with **no** entry in `types.ts` or `inversify.config.ts`. Don't add one.

### Variant case — interface + per-shop impls (ONLY when markup differs)

```
cypress/support/pages/<layer>/<feature>/
├── <feature>-page.ts                       ← `@inject(REPOSITORIES.FeatureRepository)`
├── <feature>-repository.ts                 ← the INTERFACE (selector contract)
└── repositories/
    ├── suite-<feature>-repository.ts       ← `implements FeatureRepository`, @injectable
    ├── b2b-<feature>-repository.ts
    └── b2b-mp-<feature>-repository.ts
```

```typescript
// <feature>-repository.ts  (interface — no implementations)
export interface FeatureRepository {
  getNameInput(): Cypress.Chainable;
  getSubmitButton(): Cypress.Chainable;
}
// repositories/suite-<feature>-repository.ts
import { injectable } from 'inversify';
import { FeatureRepository } from '../<feature>-repository';

@injectable()
export class SuiteFeatureRepository implements FeatureRepository {
  getNameInput = (): Cypress.Chainable => cy.get('[data-qa="feature-name-input"]');
  getSubmitButton = (): Cypress.Chainable => cy.get('[data-qa="feature-submit-button"]');
}
```

Then add the token to `types.ts` and bind the right impl in EACH demoshop map in `inversify.config.ts`:
```typescript
// types.ts
export const REPOSITORIES = { /* …existing */ FeatureRepository: Symbol.for('FeatureRepository') };
// inversify.config.ts — per demoshop map, the variant that matches that shop's markup
[REPOSITORIES.FeatureRepository]: SuiteFeatureRepository,   // suite map
[REPOSITORIES.FeatureRepository]: B2bMpFeatureRepository,   // b2b-mp map
```

### Selector priority (both shapes)

1. `[data-qa="..."]` — always preferred
2. `id` attribute — `[id="setting-key"]` if no `data-qa`
3. `name` attribute — `[name="field_name"]` for form fields without `data-qa`
4. Last resort, when a core/partial exposes none of the above: stable layout structure (`.page-title-head h2`, `[data-qa="title-action"]`) or the partial's translated text. Keep these selectors self-evident from their getter name — do NOT add explanatory doc-block comments (reviewers asked for them removed on PR #330; the getter name is the documentation).

### Comments in test code — DO NOT ADD COMMENTS

**Do not add comments to test code. None.** No `/** … */` headers, no inline `//` notes, no file banners, no "NOTE — …" or "why" explanations — on specs, pages, or repositories. Reviewers (PR #330, PR #356) rejected them repeatedly ("I doubt somebody will read it"), and on PR #356 the instruction was made absolute: **do not add comments to code.**

The code must document itself instead:
- **Name it, don't comment it.** A `recalculate(reference)` method, an `it('…')` description, a `getPanelInputSelector()` getter — the name carries the meaning a comment would.
- **A "why" that can't be named goes in the `it(...)` description or the method name**, not a comment. If you feel you *need* a comment to explain a quirk, that's a signal to rename the method/helper so the quirk is in the name (e.g. `submitImageThroughFilePopupUntilSuccessful`, `expandPanel` that is idempotent).
- **When editing existing code, remove comments you encounter** in the lines you touch — don't preserve or add to them.

---

## Reducing Duplication (PR #356 review rules)

Reviewers reject copy-paste in specs and page objects. Apply these before considering any spec done:

### 1. No inline magic strings — move them to the repository

Any hardcoded value in a spec or a page method — CSS selector, URL/endpoint path, label text, error message, placeholder, CSS class name, data-attribute name, window key, localStorage key — goes into the `<feature>-repository.ts` as a named getter, exposed through the page object when the spec needs it. The spec asserts against `page.getSaveLabel()`, never the literal `'Save'`. This is the selector rule generalized to *all* static text. Extract a value once it is **reused, or a domain constant, or plausibly changeable**; a genuinely one-off substring that reads clearly inline may stay.

### 2. Loop over blocks that differ only by a value

When two-or-more `it()`s or assertion blocks differ only by a vendor name, feature key, endpoint, or locale, drive them from a typed array and a `forEach` — do not copy the block per value.

```typescript
const VENDORS = ['openai', 'anthropic', 'aws'];
VENDORS.forEach((vendor) => {
  it(`${vendor} tab shows a masked token field`, { tags: ['@demo-smoke'] }, () => {
    page.getApiTokenInput(vendor).should('have.attr', 'type', 'password');
  });
});
```

Parameterize a selector/key by argument in the repository (`getApiTokenInputSelector(vendor)`) rather than writing one getter per value.

### 3. Extract shared logic used across specs into a helper

Logic repeated across specs belongs in one place, not copy-pasted:
- Repeated **skip-guards** → a util. This repo has `skipUnlessAiProviderEnabled(this)` in `@utils` for every `@demo-full` case — use it, never inline `if (!Cypress.env('DEMO_AI_PROVIDER_ENABLED')) this.skip()`.
- Repeated **URL builders** → a method on the shared base page (`BackofficePage.getBackofficeAbsoluteUrl(path)`), not one per feature page.
- Repeated **assertion blocks** → a page method (e.g. `auditLogsPage.assertNewestRowConfigurationIsFilterable('AWS')`).
- **One request/fetch helper, parameterized** — merge sibling methods that differ by one query param into a single method with an options object (PR #356 merged `fetchRecentTableData` + `fetchTableDataFilteredByConfiguration` → `fetchTableData({ length?, configurationName? })`).

### 4. Merge closely-related cases to save runtime

Every `it()` re-runs `beforeEach` (login, visit). Multiple `it()`s that visit the **same page and assert closely-related facets of one user goal** should be one `it()`. For endpoint-contract / validation matrices, use a single parameterized `it()` iterating a `contract` array rather than one `it()` per row:

```typescript
const contract = [
  { description: 'GET is rejected', request: { method: 'GET' }, expectedStatus: 405 },
  { description: 'token-less POST is rejected', request: { method: 'POST' }, expectedStatus: 403 },
];
it('the endpoint enforces its status contract', { tags: ['@demo-smoke'] }, () => {
  contract.forEach(({ description, request, expectedStatus }) => {
    page.requestEndpoint(request.method).its('status').should('eq', expectedStatus);
  });
});
```

Do **not** over-merge: keep cases separate when merging hurts readability or debuggability (e.g. one-SSE-event-per-test), and **never drop an assertion** when merging — preserve full coverage.

### 5. Remove dead code

Delete exported types, consts, page getters, repository selectors, and fixture fields that nothing references. Re-grep to confirm zero usages before deleting (`grep -rn "getOriginalField" cypress/`). An unused fixture field is dead data — remove it from both the `.json` and its TypeScript interface.

### 6. Const consistency

If you extract one value in a group to a `const`, extract its siblings too. Don't leave `'ai_commerce'` inline next to a `SETTING_KEY` const, or an inline `15000` timeout next to a named `AWS_TIMEOUT`. Either all related values are named or none are.

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

`@autoWired` self-binds any class decorated with it (`container.bind(Class).toSelf()`), so classes injected by their **own class token** need NO manual registration. This covers:
- Pages (`@inject(...)` consumers) and scenarios — already `@autoWired`, just `container.get(Page)` them.
- **Single-impl repositories** (the default shape) — `@injectable @autoWired class FeatureRepository`, injected as `@inject(FeatureRepository)`. Do **not** add it to `types.ts` or `inversify.config.ts`.

You only touch the central DI files for the **variant repository shape** — register the `REPOSITORIES.<X>` token in `types.ts` and bind the per-shop impl in each demoshop map in `inversify.config.ts` (see the Repositories "Variant case" above). If you find yourself binding the same single class into every map, you're using the wrong shape — collapse it to a self-bound concrete class.

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
- Assertions inside page objects — assertions belong only in test specs (a reusable page-level *assertion helper* that specs call is fine; ad-hoc assertions mixed into interaction methods are not)
- Creating duplicate page objects — always search before creating
- Tests that depend on execution order of other tests
- Multiple unrelated scenarios in one test
- Hardcoded test data in test specs
- Inventing tags that don't exist in the Release App
- Over-testing — only write tests that are explicitly required
- **Inline magic strings** (labels, error text, endpoint paths, CSS classes, selectors) in specs or page methods — move them to the repository (see Reducing Duplication #1)
- **Copy-pasted blocks that differ only by a value** — loop over a typed array instead (#2)
- **Copy-pasted skip-guards / URL builders / assertion blocks / near-identical fetch methods** — extract one shared helper (#3)
- **Any comments** — do not add comments to test code at all (see Comments); the name carries the intent
- **Dead code** — unused getters, types, consts, or fixture fields (#5)
- **Inconsistent extraction** — half the sibling values as consts, half inline (#6)

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
npm run typecheck
npm run lint
npm run prettier:write
```

All three must pass before the task is complete.

**Also verify you left NO comments** (the code must carry its own meaning — see Critical Instructions). Grep your changes and delete any comment that is not a functional `eslint-disable`/`@ts-` pragma:

```bash
git diff --name-only | grep -E '\.ts$' | while IFS= read -r f; do
  grep -nE '^\s*(//|/\*|\*)|\S+\s+//' "$f" | grep -vE 'eslint-disable|eslint-enable|@ts-|https?://'
done
```

This must return nothing (besides intercept-glob strings like `'**/path/**'`, which are not comments).

**Then run the spec live — the static gates do NOT prove the test works.** Fixture path mistakes, selector drift, login/CSRF issues, and timing all pass typecheck/lint/prettier and fail only at runtime. Run the actual spec against the running app with the correct `ENV_REPOSITORY_ID` (e.g. `b2b-mp` for this repo) and confirm it goes green:

```bash
ENV_REPOSITORY_ID=b2b-mp npx cypress run --spec "cypress/e2e/<group>/<feature>.cy.ts" --headless --browser chrome
```

If the spec uses a `repositoryId` guard (the variant pattern, not the demo group — which is unguarded), also run it under a different `ENV_REPOSITORY_ID` to confirm it **skips cleanly** (pending, not erroring) rather than throwing during DI/`container.get` resolution.

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

> ⚠️ **`tests/cypress-tests/.env` must exist, or every run dies before a single test with `Expected e2e.baseUrl to be a fully qualified URL … the value was "undefined://"`.** The config builds host/protocol from env vars loaded via `dotenv` from `.env`; with no `.env` they're `undefined`. `.env` is git-ignored, so it is NOT in a fresh checkout — and it gets **wiped whenever composer reinstalls the package** (`composer update spryker/cypress-tests` prints "The .git directory is missing… reinstalling", then removes + re-clones `tests/cypress-tests/`, taking `.env` and any other uncommitted file with it). After any such reinstall, or on a clean checkout, recreate it from the example that matches your stack — for this repo (b2b-mp on `.eu.spryker.local`) that's the dynamic-store example, which is also what CI uses:
> ```bash
> cd tests/cypress-tests && cp .env.dynamic-store.example .env
> ```
> Note the example sets `ENV_REPOSITORY_ID=suite`. For the **layer** runs (`cy:ci:*`, `cy:smoke`) that default decides which demoshop's fixtures/markup are exercised — prefix `ENV_REPOSITORY_ID=<shop>` to target another. The **`cy:demo`** script self-pins `ENV_REPOSITORY_ID=b2b-mp`, so it ignores the `.env` default and runs the demo specs against b2b-mp with no prefix. (CI passes env via `docker/sdk exec --env`, unaffected either way.)

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

Default shape (single-impl repository — most features):
```
cypress/
├── e2e/yves/feature-name/
│   └── feature-name.cy.ts
├── fixtures/suite/yves/feature-name/
│   ├── static-feature-name.json
│   └── dynamic-feature-name.json
└── support/
    ├── pages/yves/feature-name/
    │   ├── feature-name-page.ts          (@inject(FeatureNameRepository))
    │   └── feature-name-repository.ts    (concrete @injectable @autoWired class)
    ├── scenarios/yves/
    │   └── feature-name-scenario.ts
    └── types/yves/
        └── index.ts  (add interface exports here)
```
Only when markup differs per demoshop, add the interface + `repositories/<shop>-…` impls + `REPOSITORIES` token (see Repositories "Variant case").

## Checklist for a New Feature Test

0. **Discovery** (see top of file): grep feature sources, read PRD if present, confirm journey list with the user
1. Create static fixture JSON (always required) — start with `suite` under `fixtures/suite/<layer>/<feature>/`
2. Create dynamic fixture JSON (if entities need to be created) — same suite-first rule
3. Define TypeScript interfaces for both fixtures
4. Create the repository. **Default: one concrete `@injectable @autoWired class <Feature>Repository`** with the selectors. Only split into an interface + per-shop impls under `repositories/` if you confirmed during discovery that the markup genuinely differs across demoshops — otherwise the single class is correct and complete
5. Create the page class extending the correct base page; inject the repository by its class token (`@inject(<Feature>Repository)`) for the default shape
6. Create a scenario class if the flow spans multiple pages
7. DI: nothing to register for the default shape (`@autoWired` self-binds). Only the variant shape adds a `REPOSITORIES` token to `types.ts` + per-map bindings in `inversify.config.ts`
8. Write the test file with `describe` tags and `before()` fixture loading. No file-header doc-blocks — intent goes in the `it(...)` names
9. Run the test live to verify it passes — static gates (typecheck/lint/prettier) do NOT prove it works
