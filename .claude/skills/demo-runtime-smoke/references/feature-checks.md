# Per-feature runtime recipes

Detail for `@.claude/skills/demo-runtime-smoke/SKILL.md`. Each section: where to go, what a real PASS looks like, the concrete anomaly signature, and the gotchas that look like breakage but aren't. Selectors and endpoint paths are pulled from the authoritative Cypress page objects in `tests/cypress-tests/cypress/support/pages/backoffice/` and `.../yves/` — cite those files if a selector here seems to have drifted.

## 1 — AI Configuration (BO config screens + tokens)

**Drive it:** BO `/configuration/manage?feature=ai_vendor&tab=openai` (also `tab=anthropic`, `tab=aws`), and `/configuration/manage?feature=ai_commerce&tab=backoffice_assistant`.

**PASS:**
- Each vendor tab returns 200 and shows: the "Configuration Management" card title, the `ai_vendor` feature nav, and OpenAI/Anthropic/AWS as sibling tabs.
- `ai_vendor:<provider>:general:api_token` renders as `<input type="password">` (masked) — never plaintext.
- `ai_vendor:aws:general:region` pre-fills `eu-central-1`.
- Each provider tab shows a model-prices JSON editor.
- On the `backoffice_assistant` tab, the `ai_configuration` provider radio offers exactly 3 options (OpenAI/AWS/Anthropic) and OpenAI is checked by default; switching to AWS reveals the AWS model field (default `eu.anthropic.claude-sonnet-4-5-20250929-v1:0`) and hides the OpenAI one.
- Editing a value shows the unsaved-changes save bar with a change count; Save persists across a reload.

**Anomaly:** 500/Whoops on any tab; a token field rendered `type=text` (real token exposed); a vendor tab missing from the nav; the radio has fewer than 3 options; Save doesn't persist.

**This feature is also the fix mechanism for the other 9** — if a feature's provider is misconfigured or a real-provider check needs a token, this is where you set it. Reading whether a real token is present: open the tab, check whether the masked field has a non-empty `.val()` — do not try to read the value itself (it's masked for a reason).

## 2 — Search by Image (Yves search bar)

**Toggle:** `ai_commerce:search_by_image:*` enable switch, set in AI Configuration. **A `0×0` or hidden search-by-image control with the surrounding search box otherwise intact means the toggle is OFF — this is expected on a fresh env, not a bug.** Enable it first if you want to test the control itself.

**Drive it:** any Yves page header search bar, e.g. after visiting a search-results page.

**PASS:**
- The search-by-image wrapper has at least one child instance; each instance carries its own file input (`type=file`) and a hidden CSRF token.
- The camera/photo trigger is present but hidden on desktop (`is-hidden` class, `not.be.visible`) — that's correct desktop behavior, not a bug; the file-search trigger is what's visible on desktop.
- Clicking the desktop file-search trigger opens an upload popup with an enabled "Upload image" button.
- Attaching an image POSTs multipart form-data to `/search-by-image` (fields `search_by_image[image]`, `search_by_image[_token]`) and gets a 200.
- A GET to `/search-by-image` returns 405; a bodyless/imageless POST returns `{isSuccessful:false, errors:[...]}` with 200 status (not a redirect).
- **Geometry probe** (the historical regression class): input, submit icon, and search-by-image icon must sit inline on one row (`dy < 12px` between input and submit `getBoundingClientRect().y`), not stacked. **Measure only VISIBLE controls** — see the filtered probe in SKILL.md's gotcha #2. On the results page a naive `.search-form__submit` match can hit a *hidden* popup submit ~40px below the input and report a false `stacked` anomaly; the visible desktop file-trigger/clear/submit icons are inline (dy ≈ 0–6px). Filter `width > 0 && display !== 'none'` before measuring, and cross-check a screenshot if the number still looks off.

**Anomaly:** icons stacked (dy≥12px) with the toggle ON — this is the known search-form Twig/SCSS shadowing regression, see "Resolve or escalate" in SKILL.md; endpoint 5xx; CSRF/mime validation not enforced (a bad token or a `text/plain` upload should be rejected with a structured error, not accepted).

**Not a bug:** the storefront **always** uses OpenAI for this feature regardless of the BO vendor switch — `ai_commerce:search_by_image:ai_vendor:ai_configuration` is `storefront: false` in `data/configuration/ai_commerce.configuration.yml`, so the Redis-published config never carries the AWS switch to Yves. Don't flag "still logs OpenAI after switching to AWS in BO" as an anomaly here.

## 3 — Quick Add by Image (Yves Quick Order image-to-cart)

**Toggle:** `ai_commerce:quick_order:*` enable switch. Same "0×0/absent = toggle off" rule applies.

**Drive it:** Yves `/DE/en/quick-order` as a logged-in customer.

**PASS:**
- "Add to cart from image" section exists with title "Add to cart from image".
- File input: `type=file`, `name="image_order_form[uploadImageOrder]"`, `accept="image/jpeg,image/jpg,image/png"`.
- "Browse file" label wired via `for=image_order_form_uploadImageOrder`; attaching a file shows the file name and an enabled Upload button.
- Oversized files are rejected client-side (reads the real `max-file-size` attribute off the component wrapper, don't hardcode a number) and clear the input.
- Submitting with no file shows a "no image" validation error; submitting a non-image file server-side shows the error dropzone, not a recognized SKU row.
- A real multipart POST (`name="uploadImage"`) returns 200 even without a configured provider (graceful, not a 500).

**Anomaly:** section absent while toggle is ON; wrong `accept` attribute (would let users pick non-image files); a non-image upload accepted as if recognized; endpoint 5xx.

**Not a bug:** same storefront-always-OpenAI caveat as feature 2 — `ai_commerce:quick_order:ai_vendor:ai_configuration` is also `storefront: false`.

## 4 — Back Office Assistant (global BO chat widget)

**Toggle:** assistant enable switch (persists across the session; disabling makes every assistant endpoint 403 and removes the widget).

**Drive it:** any BO page, e.g. Dashboard (`/dashboard`) and a second page like Sales to confirm it's global. Product-management edit page (`/product-management/edit?id-product-abstract=300`) is the "context page" used to test form-context/form-fill.

**PASS:**
- Launcher button renders (text "Assistant") on every BO page, not just Dashboard.
- Clicking it opens a `role="dialog"` panel: greeting "How can I help you today?", agent `<select>` (first option "Auto", plus config-enabled agents like "Order Management"), a text input (`placeholder="Ask me anything..."`), Send, History, New chat, and Attach controls.
- Typing accepts text; Send POSTs a prompt payload (`prompt`, `selected_agent`, `_token`) to the prompt endpoint.
- A streamed SSE `ai_response` renders as an AI bubble; a streamed `error` event renders an error bubble with Retry, distinct from an HTTP transport failure; `tool_call`/`tool_call_result` render as tool-call blocks; `form_fill` writes directly into the current page's form field with no chat bubble.
- Attaching a file shows a chip carrying it in the POST as `attachments`/`mediaType`; removing the chip before Send drops it from the payload.
- History panel lists past conversations and supports delete; deleting removes the row.
- CSRF/validation contract: prompt POST without a valid token → 403; empty prompt → a resolved (non-raw-glossary-key) validation error in the SSE stream, not a 500; unsupported attachment media type or attachment-count-exceeded → resolved validation error, not a 500.

**Anomaly:** launcher missing on a non-Dashboard page (widget not actually global); panel overlapping the left nav/top bar; a validation error path leaking a raw glossary key (e.g. `backoffice_assistant.validation.something` shown verbatim instead of the human message) instead of a real 500 — the endpoint returning a hard 500 for a validation case is the bug, not the message text itself.

**Gotcha — panel state carries over across pages.** The widget persists open/closed state in localStorage (`backoffice_assistant_state`), so a panel left open on one page reopens open on the next page you visit in the same session and can visually cover a Save button elsewhere (e.g. the AI Configuration page). If a click on an unrelated control seems to silently fail, check whether the assistant panel is covering it — clear the key (`localStorage.removeItem('backoffice_assistant_state')`) rather than treating it as an anomaly in the other feature.

## 5 — Smart PIM (BO product AI-assist controls)

**Drive it:** BO `/product-management/edit?id-product-abstract=<id>` (e.g. `300`).

**PASS:**
- Page title contains the product SKU; the Smart PIM + request-builder scripts are present; at least one request-builder trigger is visible.
- Category-suggestion trigger: `data-url="/ai-commerce/category-suggestion"`, opens `popovertarget="ai-category-modal"`.
- Alt-text triggers exist per image; clicking one opens a modal that, for an image with no URL yet, shows the empty state ("Please fill in the product image url") **without** firing a provider request — only a populated field fires the real POST.
- The "all actions" popover offers Translate (opens the locale-selector popover) and Improve Content (POSTs to `/ai-commerce/content-improver`) — opening/closing the popover fires no request by itself.
- On a real provider call: category-suggestion POST carries `product_name`/`product_description`; image-alt-text POST carries `imageUrl`/`locale`; a provider 5xx surfaces its error text in the popover, not a blank/crashed modal; "try again" re-issues the same request; "apply" writes the suggestion back into the source field and closes the popovers.
- Each of the four AI endpoints (`content-improver`, `image-alt-text`, `translate`, `category-suggestion`) rejects a param-less GET/POST with 400 and (for the first three) a specific message — this is a structural contract check, needs no provider token.
- A dynamically-injected image wrapper (added to the DOM after load) still gets an alt-text trigger — confirms the trigger-injection observer is running, not just a one-time page-load scan.

**Anomaly:** triggers absent on a populated product; a provider request fires on page load or on opening an empty-state modal (privacy/cost concern — it should only fire on user action with real content); a provider failure crashing the modal instead of showing the error text; the 400 contract check returning something other than 400 for a malformed request.

## 6 — Smart CMS (BO CMS content assistant panel)

**Note:** Smart CMS has its own BO Configuration Management toggle — enable it (`ai_commerce:smart_cms:*`) before judging the panel absent as a bug.

**Drive it — BOTH surfaces, not just one:**
- CMS **Page** editor: `/cms-gui/create-glossary?id-cms-page=<id>` (e.g. `6`).
- CMS **Block** editor — `/cms-block-gui/edit-glossary?id-cms-block=<id>` (e.g. `1`). Note it's `edit-glossary`, **not** `create-glossary`/`edit` — those 404 on the block-gui route.

**PASS:**
- Panel renders on both surfaces; toggle button text "Smart CMS Content Assistant"; `window.SmartCmsContentConfig` is an object scoped to the current entity (`entityType`/`idEntity` differ between Page and Block).
- Expanding the panel (toggle `aria-expanded` flips `false→true`, panel loses its collapsed class) reveals prompt input (placeholder "Ask AI to generate or edit the title and content…"), Ask AI button, attach control.
- Typing + Ask AI POSTs `{userPrompt, entityType, idEntity, _token, placeholders, attachments?}` — entity fields must match whichever editor (Page vs Block) is open.
- Attaching a file lists its name; removing it clears the list; an unsupported media type is rejected client-side with an inline error and never listed.
- Endpoint contract: GET or token-less POST to the generate endpoint → 403; invalid CSRF token → 403 with the `invalid-csrf` error key; feature disabled → 403 with the disabled error key **checked ahead of** the CSRF check; unsupported attachment media type → 422 with a structured error array.
- A real generate call returns a success message in the panel and (on Page editors) writes into the glossary editor field with non-empty content.

**Anomaly:** panel on Page only or Block only (not both); the panel's entity context not updating when navigating Page→Block in the same session (stale `SmartCmsContentConfig`); a generate call not scoped to the right entity; disabled-feature check happening after (not before) the CSRF check, meaning a disabled feature still leaks CSRF-validity information.

**Gotcha — panel expand/collapse state carries over globally.** Like the BO Assistant, the panel persists its expanded/collapsed state in one global localStorage key (`smart_cms_panel_state`, not per-entity), so a panel expanded on the Page editor stays expanded when you navigate to the Block editor. A plain "click the toggle" can therefore collapse it instead of expanding it — check `aria-expanded` first and only click if it's `false`.

## 7 — Cost Price & Gross Margin (BO + MP + Yves agent)

Three independent surfaces — check all three; a merge or config regression can hit any one alone.

### 7a — Back Office: admin edits/views the cost price

**Drive it:** `/product-management/edit?id-product-abstract=<id>` → **Price & Tax** tab; and `/product-management/view?id-product-abstract=<id>`.

**PASS:** the price table on the edit form shows Cost price / Gross price / Net price columns with enabled, non-readonly cost-amount inputs; editing + saving persists the new cost value across a reload; the view page's Price & Taxes widget shows a non-empty Cost price row alongside Gross/Net.

### 7b — Merchant Portal: merchant manages the Cost Default column

**Login:** `harald@spryker.com` / `change123` at `http://mp.eu.spryker.local/security-merchant-portal-gui/login`, then Products.

**PASS:** the product price grid shows a "Cost Default" column beside Gross Default/Net Default; editing a cost cell and saving the drawer persists the new value after reopening the product.

**Env gotchas (both observed live, not regressions):**
- `curl http://mp.eu.spryker.local` from a shell can return `000` — that's a shell-only DNS quirk. Confirm the container is up (`docker ps | grep mportal`) and judge reachability from the **browser**, not curl.
- MP login can fail silently **intermittently**: sometimes the Angular form just resets to empty and stays on the login URL with no visible error, even though the password verifies in the DB — but other runs log `harald@spryker.com` straight in and the Cost Default column renders fine. So try the login first; only if it resets on you, report MP cost-price coverage as `NOT-COVERED (MP login silently fails this run — intermittent env issue)` rather than FAIL. It's an intermittent environment flake to recheck next run, not a standing blocker to skip by default and not a code regression to chase.

### 7c — Yves: agent-only cost-price + gross-margin molecule

**This is the trickiest of the three — get the route right.**

1. Log in as **agent**, not customer: `http://yves.eu.spryker.local/en/agent/login`, `agent123@spryker.com` / `change123`. (`spy_user.status = 0` shown in mariadb is *active*, not disabled — same convention as admin.) No customer impersonation needed.
   - **Prereq — log the customer out first.** If a Yves *customer* session is still active (e.g. from features 2/3), `GET /en/agent/login` **silently redirects to `/customer/overview`** and the agent login form isn't on the page — a `set(...)` fill then throws `Illegal invocation` because the input doesn't exist. Visit `/DE/en/logout` (or use a fresh browser session) before hitting the agent login. This is why the run order in SKILL.md does the agent check in its **own** session.
2. Open an **existing** quote request on the **agent edit** route: `/DE/en/agent/quote-request/edit/<REF>` — e.g. list candidates with `mariadb -h database -u spryker -psecret -D eu-docker -e "SELECT quote_request_reference,status FROM spy_quote_request ORDER BY id_quote_request DESC LIMIT 10;"`.
3. **PASS:** every line item shows a Cost Price row + Gross margin badge + "Use default price" checkbox, aligned in the item box.

**Route gotchas:**
- The customer **read-only** view `/DE/en/quote-request/details/<REF>` renders **zero** cost-price nodes by design — the molecule is agent-edit-only. Don't test cost-price on that route; a plain customer there seeing no molecule is the correct PASS for that surface, not an anomaly.
- `?quoteRequestReference=` and `?_switch_user=` query-param variants both error/404 — always use the path-segment `.../edit/<REF>` form.
- **Logging into the Back Office in the same browser clobbers the Yves agent session.** Do the agent check in its own session, separate from the BO scan; if "Agent Mode" has disappeared, re-login as agent before continuing.

**Data-state gotcha — `Cost Price: —` / `⚠` is a state, not a bug.** A quote-request version whose cost price hasn't been recalculated yet shows every item as `Cost Price: —` with an amber warning and `Gross margin: —`; the molecule is still correctly rendered and laid out. **Edit → Save** recalculates and applies real values. So the PASS bar here is *does the molecule render and lay out correctly* — the dash-vs-value distinction is cost-price application state, not something this check should flag.

## 8 — QuickSight Analytics (BO embedded analytics)

**Drive it:** `/analytics-gui/analytics`.

**PASS:** 200 status; page renders an "Analytics" section title. (Without an Analytics permission granted, you'll instead see a "No Analytics permission has been granted..." message plus an enabled "Synchronize Users" button wired to `POST /amazon-quicksight/user/synchronize-quicksight-users` with a CSRF token in the form — that's still a PASS, it's the documented no-permission state, not an error.)

**Anomaly — and the specific fix for each variant:**
- `Could not find config key "AMAZON_QUICKSIGHT:..."` → a demo-only `config_default.php` block was dropped (commonly during an upmerge). Needs restoring the config block — this is a real regression, escalate/fix at the config level.
- `Class "...\DependencyProvider" not found` (a `\Pyz\...` or `\Demo\...` provider) → **usually a stale local class-resolver cache, not a real drop.** Run `docker/sdk cli console cache:class-resolver:build` (not just `cache:empty-all`) and recheck before treating it as a regression.

## 9 — Audit Logs (BO AI interaction log)

**Drive it:** `/ai-foundation/ai-interaction-log`.

**PASS:**
- 200; "Audit Logs" section title.
- Table renders exactly 10 column headers: Prompt, Conversation, Provider, Model, Total Tokens, Estimated cost, Configuration, Status, Inference (ms), Created At.
- 5 stats cards render: Total Interactions, Total Tokens, Total estimated cost, Success Rate, Avg Inference Time.
- The DataTable's AJAX data endpoint (`/ai-foundation/ai-interaction-log/table`) returns 200 JSON shaped `{draw, recordsTotal, recordsFiltered, data:[...]}`.
- Configuration / Status / Created At columns are sortable; the rest are not (clicking a sortable header or changing page-length re-issues a 200 AJAX call).
- If a real AI interaction just ran (e.g. from Smart PIM's improve-content), the newest row after that action has a strictly higher `recordsTotal` than the pre-action baseline, with populated provider/model/tokens>0/estimated-cost/status="Success"/inference-time≥0 — confirms logging is actually wired, not just that the table renders.

**Anomaly:** any of the 10 headers or 5 stat cards missing; AJAX endpoint 500 (note: the real endpoint 500s if you pass `search[value]` — that's a documented quirk of the endpoint itself, not a smoke-test bug: fetch without a search term); a real interaction not producing a new row.

## 10 — AI Workflows (BO workflow runs list)

**Drive it:** `/ai-foundation/ai-workflow`.

**PASS:**
- 200; "Workflows" section title; "Workflow Items" widget title.
- Table renders exactly 6 column headers: ID, Process Name, State, Created At, Updated At, Actions.
- The DataTable's AJAX endpoint (`/ai-foundation/ai-workflow/table`) returns the same `{draw, recordsTotal, recordsFiltered, data}` shape as Audit Logs.
- The 5 data columns are sortable; Actions is not; page-length change and Created-At sort click both re-issue a 200 AJAX call.

**Anomaly:** heading/widget title missing; fewer/more than 6 headers; AJAX 500.

## Session hygiene across features

Because features 4/6 persist UI state in localStorage and feature 7c needs a separate agent session from Back Office, run the features in an order that minimizes session churn: do all BO-only features (1, 4, 5, 6, 8, 9, 10) in one BO session, then the Yves customer-only checks (2, 3, 7c-customer-view), then the Yves agent check (7c) in its own fresh session, then MP (7b) last since its login is the most likely to need a retry.
