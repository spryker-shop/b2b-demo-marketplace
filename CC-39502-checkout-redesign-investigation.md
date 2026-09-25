# CC-39502 — Checkout Flow Redesign — Investigation & Discovery

Discovery for the Checkout redesign epic (Jira: **CC-39502**, project **Core Commerce**, status **Grooming**). Timeline target: ~1 month.

---

## Executive summary

- **Build in core.** The redesign ships to all customers, so the target is the suite's first-party modules `src/SprykerShop/CheckoutPage` + `CartPage` (with the `Pyz` layer reserved for demo-specific tweaks). See §6.
- **~90% is re-skin, not new backend.** Every major capability the prototype shows — guarded multi-step flow, split delivery, shipment types (incl. pickup & services), per-shipment methods, delivery date, vouchers, comments, order reference, quick-add, request-a-quote, recurring order, and budget/cost-centre — already exists and functions in the shop today. The work is re-composition + re-skin + widget embedding.
- **One genuinely net-new item:** two-level shipping (speed → carrier). Spryker's shipment methods are flat under carriers and have no "speed/service-level" attribute, so this needs a small data-modelling step. It has a clean MVP fallback (re-skin the existing flat method list).
- **Out of scope (prototype dressing, no Spryker support):** freight/oversized; saved/on-file payment cards.
- **Timeline is comfortable.** A single Day-0 spike (shipping speed model) is the only real unknown.

---

## 1. What the epic asks for

Replace the dated B2B checkout with a **modern, guarded multi-step flow**:

```
Cart → Shipping → Payment → Summary → Confirmation
```

Progressive disclosure — complexity revealed only when needed. Goal: reduce completion time, remove clutter from the buyer's view, establish a scalable design-system foundation.

- **Prototype:** https://v0-checkout-cart-summary.vercel.app/
- **Features & behaviours:** captured in §11.

Two structural ideas from the prototype:
1. **Cart items move *into* the checkout flow** (shown on the Address/Shipping steps), enabling per-item split-delivery organisation.
2. The **persistent right sidebar reuses CartPage features** (voucher, quick-add, order reference, comments, totals).

---

## 2. Prototype UI walkthrough (target design)

Step rail: `1 Address · 2 Shipping · 3 Payment · 4 Summary` (guarded — cannot skip ahead).

### Address step
- **Left panel = the cart, inline:** line items with image, name, SKU, supplier, stock badge, ETA, per-item comments, qty × price, remove, "Show details".
- **Split Delivery toggle** ("All items ship to one location" ↔ multi-address).
- Address selector (+ Change).
- **On-site Services** section: service line items with scheduled date/time + location + Change.
- **Right sidebar (persistent):** Quick add by SKU · Order Summary (Products / Services / Shipping / Tax / Total) · Proceed to shipment · Request a Quote · Add voucher code · Add order reference · Comments.

### Shipping step
Spryker's multi-shipment model surfaced visually:
- **Delivery Addresses** header (multiple warehouses/facilities).
- Per address: **One shipment ↔ Split (N)** toggle.
- Per shipment: **Deliver ↔ Pickup** tabs; shipping methods (Ground/Express/Overnight) with price + ETA; delivery date.
- Freight flag for oversized items ("Oversized – requires freight").
- **Preferred delivery date** applying to all shipments.
- Sidebar: Order Summary with "Delivery N/N configured" progress, PO reference, comments.

### Payment / Summary
Payment-method selection; order review. Guarded — cannot deep-link without completing prior steps.

---

## 3. Current/legacy UI walkthrough (what exists today)

Verified against `http://yves.eu.spryker.local/DE/en/` (DE store, logged-in B2B user). Today's breadcrumb flow: `Cart » Address » Shipment » Payment » Summary` — 5 steps, with Cart as a **separate page**, not part of the step rail.

### Cart page (`/cart`)
- **Left "Delivery":** per-item image, name, SKU, Sold-by-merchant, availability badge, item price/total, quantity input + update, remove, per-item note, "Assign this item to an asset" (SSP asset).
- **Right sidebar:** note for the cart · voucher/gift-card code + Redeem · Comments to Cart · Custom Order Reference · Save cart to a shopping list · Share Cart via link (external/internal) · Quick add to Cart.
- **Order block:** Discounts, per-shipment shipping lines, Subtotal, Tax, Grand Total, Checkout, Request a Quote.
- Upsell/cross-sell row below.

### Address step (`/checkout/address`)
- **Single address ↔ Multiple addresses** toggle (= the prototype's "Split Delivery").
- **Per item:** quantity, shipment-**type** radios (**Delivery / Pickup / On-Site Service / In-Center Service**), and a per-item delivery-address dropdown.
- Billing address selector.
- Confirms Shipment-Type + Service-Point features are installed and working in this shop.

### Shipment step (`/checkout/shipment`)
- **Shipment groups** (grouped by delivery address + merchant).
- Per group: shipping-method radios (e.g. Dummy Standard/Express; Drone Air Standard/Sonic/Light) + Requested delivery date.
- This is the `ShipmentGroup` rendering the prototype re-skins into per-address/per-shipment cards.

### Payment step (`/checkout/payment`)
- Payment methods grouped by provider (e.g. Dummy Marketplace Payment → Invoice; Dummy Payment → Credit Card / Invoice). Credit Card reveals a card sub-form.

### Summary step (`/checkout/summary`)
- **Per-shipment** review (Shipment n°1, n°2…): products + Delivery Address + Delivery method + Requested delivery date (each editable).
- **"Set up as recurring order"** option.
- Payment (invoice + Date of Birth), Billing Address.
- Comments to Cart.
- **Complete checkout:** voucher/gift-card redemption, Subtotal, Discounts, per-shipment shipment totals, Tax, Grand Total, terms acceptance, Submit your order.

**Takeaway:** every capability the prototype shows already exists and functions today. The redesign is overwhelmingly re-composition + re-skin, not new backend.

---

## 4. Where the code lives — the suite monorepo

The implementation target is **`/home/stan/Sites/suite`**. `b2b-demo-marketplace` only consumes released packages; the suite is the source of truth.

- `/home/stan/Sites/suite/src/SprykerShop/` holds **187 editable first-party module packages** (each with its own `composer.json`, `src/`, `tests/`). There is no `vendor/spryker-shop`; modules load via `spryker/composer-merge-plugin` with `preferred-install: source`, and are **split into the read-only `spryker-shop/*` packages on release** (`extra.splitting.namespaces: Spryker\, SprykerShop\`).
- Target modules:
  - `src/SprykerShop/CheckoutPage/src/SprykerShop/Yves/CheckoutPage/` — `Controller, DataContainer, Dependency, Exception, Expander, Extractor, Form, GiftCard, Plugin, Process, Reader, Theme`.
  - `src/SprykerShop/CartPage/src/SprykerShop/Yves/CartPage/` — `Controller, Dependency, Expander, Form, Handler, Mapper, Model, Plugin, ProductViewExpander, Theme, ViewModel, Widget`.
- A **project (Pyz) layer** exists, module-per-folder (`src/Pyz/<Module>/src/Pyz/Yves/<Module>/`, wired via psr4-wildcard) and is currently thin:
  - `src/Pyz/CheckoutPage/…`: `CheckoutPageDependencyProvider`, `CheckoutPageConfig`, `Theme/default/views/payment/payment.twig`, `views/summary/summary.twig`, `components/molecules/payment-card-information/…`.
  - `src/Pyz/CartPage/…`: `CartPageConfig`, `CartPageDependencyProvider`, `Theme/default/components/molecules/product-cart-item/…`.
- **Theme override chain:** `Pyz/Theme/default` overrides `SprykerShop/<Module>/Theme/default`.

---

## 5. Backend capability analysis

### Checkout step engine (`spryker-shop/checkout-page`)
- `Process/StepFactory.php` builds the ordered step collection (`getSteps()`) and wraps it in a StepEngine with a DataContainer + pre-render plugins; `StepResolver` can swap the step set via `CheckoutStepResolverStrategyPluginInterface`.
- Steps (`Process/Steps/`, extend `AbstractBaseStep`): `EntryStep`, `CustomerStep`, `AddressStep`, `ShipmentStep`, `PaymentStep`, `SummaryStep`, `PlaceOrderStep`, `SuccessStep`, `ErrorStep`. Routes from `Plugin/Router/CheckoutPageRouteProviderPlugin`.

### Split delivery / multi-shipment — fully implemented in core
- **Item → address assignment happens in the ADDRESS step:** `Process/Steps/AddressStep/AddressStepExecutor.php` (`hydrateItemLevelShippingAddresses()`, `getShipmentWithUniqueShippingAddress()` dedupes addresses; `hasQuoteMultiShippingAddresses()` decides single vs multi).
- **Grouping** is delegated to the Shipment service: `ShipmentHandlerPlugin::addToDataClass()` → `expandQuoteWithShipmentGroups()`; `Form/DataProvider/ShipmentFormDataProvider` → `groupItemsByShipment()` + `expandShipmentGroupsWithCartItems()` + per-group methods/labels.
- **Forms:** `ShipmentCollectionForm` (QuoteTransfer) → collection of `ShipmentGroupForm` (ShipmentGroupTransfer) → embeds `MultiShipmentForm` (ShipmentTransfer: `shipmentSelection` + `requestedDeliveryDate`). On submit, the chosen shipment is copied onto every item in the group. (`ShipmentForm` is the deprecated single-shipment legacy.)
- The concrete **multi-address address form** (`multiShippingAddresses`, `isMultipleShipmentEnabled`) lives in `spryker-shop/customer-page`, injected via `ADDRESS_STEP_SUB_FORMS` / `PLUGIN_CHECKOUT_ADDRESS_FORM_DATA_PROVIDER`.
- **`shipment.twig` already renders cart items per shipment group** (`data-qa="multi-shipment-group"`, loops `cartItems`, includes `summary-node`). "List cart items in the shipping step" is largely already met.
- There is no multi-shipment on/off flag in `CheckoutPageConfig` — behaviour is data-driven (per-item `ShipmentTransfer`s + Shipment-service grouping).

### Extension points (customise without overriding core classes)
Wired from the DependencyProvider / consumed in `StepFactory`:
- `PLUGINS_CHECKOUT_PAGE_STEP_ENGINE_PRE_RENDER` — inject/mutate step view data (best seam for extra shipping-step data; the project already uses `MerchantShipment…` + `ShipmentType…` pre-render plugins).
- `PLUGINS_CHECKOUT_ADDRESS_STEP_ENTER_PRE_CHECK`, `…_POST_EXECUTE`, `…_SHIPMENT_STEP_ENTER_PRE_CHECK`, `…_PAYMENT_STEP_ENTER_PRE_CHECK`, `…_SUMMARY_STEP_PRE/POST_CONDITION`.
- `PLUGINS_CHECKOUT_STEP_RESOLVER_STRATEGY` — swap the step collection (used today for QuoteRequest / Agent / PaymentAppExpress).
- `ADDRESS_STEP_SUB_FORMS`, `PAYMENT_SUB_FORMS`, sub-form filters; `PLUGIN_SHIPMENT_FORM_DATA_PROVIDER`, `PLUGIN_CHECKOUT_ADDRESS_FORM_DATA_PROVIDER`; step-handler collections.
- Per-step widget plugin lists: `PLUGIN_{CUSTOMER,ADDRESS,SHIPMENT,PAYMENT,SUMMARY,SUCCESS}_PAGE_WIDGETS` — add UI to a step without editing TWIG.
- `AddressViewDataExpanderInterface`, `AddressTransferExpanderPluginInterface`.

### CartPage widgets & reusability (`spryker-shop/cart-page`)
- CartPage owns only **7 small form/control widgets**, all registered globally (in `ShopApplicationDependencyProvider`): `AddToCartFormWidget`, `AddItemsFormWidget`, `CartChangeQuantityFormWidget`, `RemoveFromCartFormWidget`, `CartAddProductAsSeparateItemWidget`, `ProductAbstractAddToCartButtonWidget`, `CartSummaryHideTaxAmountWidget`.
- The **big "sidebar" features are external global widgets** the cart page references by name: voucher/discount (`CartCodeFormWidget`/`DiscountVoucherFormWidget`), cart notes (`CartNoteFormWidget`/`CartItemNoteFormWidget`), comments (`CommentThreadWidget`), shopping list (`CreateShoppingListFromCartWidget`), upsell (`CartDiscountPromotionProductListWidget`), multi-cart ops, quick-order add. These can be embedded in checkout via `{% widget 'X' %}` with no new wiring.
- Item list rendering: `components/molecules/product-cart-items-list` → `product-cart-item` (qty via `CartChangeQuantityFormWidget`, remove via `RemoveFromCartFormWidget`). Totals: `components/molecules/cart-summary` (holds the proceed-to-checkout button).

### Cart reuse approach (decided)
CartPage and the `/cart/*` routes **stay functional**. The cart UI is **duplicated inside the checkout page** and **reuses the existing `/cart/` actions** (add / remove / change-quantity). No new checkout-scoped cart routes are required.

Residual friction (minor):
1. **Redirect target after a `/cart/` action** must return to the current checkout step, not `/cart`. The cart form widgets already expose `redirectRoute` / `ajaxTriggerAttribute`, so this is a template-level parameter, not new backend.
2. **AJAX item-list load is cart-route bound** (`cart/get-cart-items`); in checkout prefer static render.
3. **Permission / quote-editability flags** (`SeePricePermissionPlugin`, `WriteSharedCartPermissionPlugin`, `isQuoteEditable`, `isQuoteLocked`) must be supplied with the same QuoteTransfer.
4. **Proceed-to-checkout button lives inside `cart-summary.twig`** — suppress/override when reusing the summary in checkout.

---

## 6. Implementation-location decision

The redesign ships to all customers (Core Commerce epic, OOTB storefront), so it is built primarily in the core `SprykerShop` modules — using existing extension seams and re-skinning existing templates — not by inventing new backend.

| Change type | Where | Rationale |
|---|---|---|
| New/rearranged step UI, cart-in-checkout, sidebar re-composition, design-system components | `src/SprykerShop/CheckoutPage/Theme/default` (+ CartPage) | Ships to all customers; first-party editable templates |
| Widget embedding (voucher, comments, quick-add, order reference, totals) into checkout steps | Core theme via `{% widget %}` / per-step `*_PAGE_WIDGETS` plugin lists | Widgets already global; zero backend |
| Extra view data for a step (e.g. richer per-item info in shipping) | `StepEnginePreRender` plugin + data-provider expander | Additive, upgrade-safe seam |
| Demo-only polish / store-specific copy or styling | `Pyz` layer (`src/Pyz/CheckoutPage`, `src/Pyz/CartPage`) | Keeps demo tweaks out of core |
| Changing the grouping/hydration algorithm itself | Only if unavoidable — core `AddressStepExecutor` / `ShipmentFormDataProvider` | The prototype does not require this |

Avoid building the redesign in `b2b-demo-marketplace`'s `Pyz` fork — that repo only consumes released packages, so work there would not ship to core. Keep new backend minimal; prefer plugins / data-providers / widgets over overriding step classes.

---

## 7. Feature-by-feature: effort & scope

Effort: S = ≤2d, M = ~3–5d, L = 1–2wk. Legend: 🟢 re-skin over existing backend · 🟡 light new frontend logic/data wiring · 🔴 net-new capability.

| Feature | Backend today | Primary work | Effort | Scope |
|---|---|---|---|---|
| Guarded multi-step rail (Cart→Shipping→Payment→Summary) | ✅ step engine (server guards) | 🟢 theme + step-rail component | S–M | Keep (foundation) |
| Cart duplicated inside checkout, reusing `/cart/` actions | ✅ CartPage widgets global | 🟢 theme; `redirectRoute` back to step | M | Keep — largest piece |
| Editable line items — trash-remove, immediate update | ✅ `/cart/remove` | 🟢 theme | S | Keep |
| Empty-cart state (dedicated panel + Browse-catalogue CTA, proceed blocked) | n/a | 🟡 new UI + guard | S | Keep |
| Collapsible "Show details" per item + cleaned detail panel (drop Total/Unit/Margin) | ✅ data exists | 🟢 theme | S | Keep |
| Grand total always 2 decimals · primary CTA disabled when empty | ✅ | 🟢 theme | S | Keep |
| Client-side step guards (`sessionStorage`; deep-link → earliest incomplete step; reset on confirm) | ✅ server-side guards exist | 🟡 frontend layer atop server guards | S–M | Keep |
| Split Delivery toggle + item→address assignment | ✅ full (address step) | 🟢 re-skin | S–M | Keep |
| Per-address shipment split + per-shipment groups | ✅ full (ShipmentGroup forms) | 🟢 re-skin into cards | M | Keep |
| Deliver ↔ Pickup per shipment | ✅ shipment types installed | 🟢 theme (tabs) | S–M | Keep |
| **Two-level shipping: speed (Ground/Express/Overnight) primary → carrier (FedEx/UPS/DHL) secondary**, smart cheapest-carrier default, expand/collapse | ⚠️ methods are flat (Carrier→Method); "speed" not first-class | 🔴 method↔speed classification model + data, then frontend | L | **Keep — 1 spike** |
| Always-active "Proceed to Payment" CTA + inline error listing shipments missing a selection | ✅ | 🟡 frontend validation/messaging | S–M | Keep |
| Sidebar: order summary / totals · voucher · order/PO reference · comments · quick-add | ✅ global widgets | 🟢 `{% widget %}` embedding | S–M | Keep |
| Request a Quote | ✅ QuoteRequest resolver wired | 🟢 theme button | S | Keep |
| Preferred / requested delivery date (per-shipment + global) | ✅ `requestedDeliveryDate` | 🟢 theme | S | Keep |
| On-site / In-center Services (date/time + location) | ✅ SprykerFeature/SelfServicePortal | 🟢/🟡 theme + wire SSP scheduling | M | Keep |
| Other payment: Use-new-card (inline form, expiry formatting) · Net 30 Terms · Purchase Order | ✅ payment methods exist | 🟡 theme + method mapping | M | Keep |
| Payment validation timing (errors only after submit, not on blur) + pre-selected editable billing address | ✅ | 🟡 frontend validation | S | Keep |
| Budget & cost-centre selector — usage bar, live "remaining after this order", over-budget warning, opt-in toggle | ✅ `purchasing-control`; `CostCenterSelectorWidget` already rendered in `summary.twig:261` | 🟢 re-skin + placement decision | S–M | Keep |
| Recurring order on Summary | ✅ present live | 🟢 theme | S | Keep |
| Order confirmation screen + `sessionStorage` cleared | ✅ SuccessStep | 🟢 theme + client reset | S | Keep |
| Saved payment methods (on-file cards grouped w/ count) | ❌ no card vault in Spryker | — | — | **Out of scope** |
| Freight / oversized | — | — | — | **Out of scope** |

### Design note — cost-centre placement
The behaviours doc puts budget/cost-centre on the **Payment** step; today the widget sits on **Summary**. Placement is flexible **provided the persistent checkout sidebar shows all totals (incl. discounts) on every step** — then the buyer sees budget impact wherever the selector lives. Recommendation: keep the selector wherever the layout fits, and make the sidebar totals the single source of truth across all steps so the over-budget signal is always visible.

### If the month tightens — phase order
1. **Two-level shipping smart-default UX** → MVP: re-skin the existing flat per-group method list; defer speed→carrier grouping to phase 2.
2. **Budget/cost-centre** → keep as-is (widget already ships); no de-scope needed.

Everything else (guarded flow, cart-in-checkout, split delivery, shipment types, delivery date, services, recurring order, budget/cost-centre, sidebar widgets, request-a-quote) is re-skin over existing backend.

---

## 8. Risks / verify-first

1. **🔴 Two-level shipping (speed→carrier)** — the only real data-modelling task. Methods are flat under carriers; "speed" isn't first-class (only `deliveryTime` int + free-text `name`). Needs a name-convention or new attribute/glossary layer + demo data. Mitigation: MVP = re-skin the existing flat per-group method list; defer speed→carrier grouping.
2. **🟡 Client-side step guards (`sessionStorage`)** — must not conflict with the server-side step engine's own guards; keep server guards authoritative, treat sessionStorage as UX-only.
3. **Two codebases** — implement in `/home/stan/Sites/suite` (`src/SprykerShop`), not `b2b-demo-marketplace`. Confirm the release/split path with the core team.
4. **Design system** — the epic wants a scalable design-system foundation; build reusable Yves components, not one-off SCSS.

---

## 9. Grooming decisions

1. **Core vs demo** → Core (all customers); some adjustments may live in the demoshop (`Pyz`). Primary target = `src/SprykerShop/CheckoutPage` + `CartPage`.
2. **Freight / oversized** → out of scope (prototype dressing).
3. **On-site / In-center service scheduling** → provided by SprykerFeature/SelfServicePortal.
4. **Recurring order** → in scope for the redesigned Summary.
5. **Cart reuse** → CartPage and `/cart/*` routes stay functional; cart is duplicated in checkout and reuses `/cart/` actions.
6. **Saved payment methods** → out of scope; Spryker supports only per-order payment-method selection (no card vault).

---

## 10. Suggested ~1-month breakdown

- **Day 0 — spike:** two-level speed→carrier modelling approach (name-convention vs new attribute/glossary) + demo data. Outcome decides MVP vs full for the shipping step.
- **Wk 1 — Foundation:** step-rail component + guarded flow (server guards + `sessionStorage` UX layer); cart duplicated into the Cart/Address step reusing `/cart/` actions (`redirectRoute` back to step); empty-cart state; collapsible item details.
- **Wk 2 — Shipping step:** re-skin split-delivery + multi-address assignment; shipment-group cards (Deliver/Pickup tabs, delivery date); shipping-method UX (MVP flat list, or speed→carrier if spike is green); always-active CTA + inline error.
- **Wk 3 — Sidebar + Payment:** embed order-summary / voucher / order-PO-reference / comments / quick-add widgets across steps; payment re-skin (per-order method selection, new-card form, Net 30 / PO); budget & cost-centre re-skin + placement; billing address.
- **Wk 4 — Summary + polish:** per-shipment review + recurring-order option; confirmation + progress reset; services (SelfServicePortal) surfaced; design-system alignment; QA across personas; validation / empty / error states.

---

## 11. Acceptance criteria (Features & behaviours)

Source: "Checkout breakdown — page-by-page features and behaviours for estimation." Prototype: v0 vercel; Cart component: Figma.

### Cart & Order Summary
- **Editable line items** — products & services removable individually via trash button; cart state updates immediately.
- **Empty-cart state** — removing the last item shows a dedicated empty-cart panel with a "Browse catalogue" CTA; proceeding is blocked.
- **Collapsible details** — each item has a muted "Show details" toggle (chevron), collapsed by default, expands inline.
- **Cleaned-up detail panel** — the redundant pricing block (Total / Unit price / Margin) is removed from the expanded detail view.
- **Grand total** — always displays to exactly two decimal places.
- **Primary CTA** — reads "Proceed to shipment"; disabled when cart is empty.

### Step guards & progress tracking
- **Sequential step protection** — progress stored in `sessionStorage`; accessing a later step directly redirects to the earliest incomplete step.
- **Progress reset on completion** — on successful confirmation, `sessionStorage` is cleared; flow cannot be re-entered or re-submitted via Back.

### Shipping & fulfillment
- **Method-primary layout** — shipping speed (Ground / Express / Overnight) is the primary choice, shown with indicative "from $X" pricing.
- **Secondary courier panel** — carriers (FedEx / UPS / DHL) with exact prices appear only after a speed is selected.
- **Smart courier default** — selecting a speed auto-selects the cheapest carrier for that speed; remains editable.
- **Stay-open on speed select** — choosing a speed keeps the card expanded so couriers/prices stay visible.
- **Auto-collapse on courier select** — once speed + courier chosen, the card collapses to a confirmed summary with an Edit button.
- **Compact carrier layout** — carriers inline: logo + name + exact price.
- **Always-active CTA** — "Proceed to Payment" never disabled; clicking while incomplete surfaces an inline error listing which shipments still need a selection.

### Payment
- **Other payment options** — labelled section: Use a new card · Net 30 Terms · Purchase Order.
- **New card form** — revealed inline when "Use a new card" is selected; card number + expiry formatting.
- **Validation timing** — field errors appear only after a submit attempt (not on blur).
- **Pre-selected billing address** — defaults to the account default; editable before submitting.
- **Budget & cost centre** — department budget selector with a usage bar and a live "remaining after this order" figure; turns red with a warning if the order exceeds budget.
- **Optional toggle** — a switch makes cost-centre opt-in; off shows "No budget assigned"; toggling back on restores the prior selection.
- *Out of scope:* saved/on-file payment cards (Spryker has no card vault).

### Summary & confirmation
- **Order confirmation** — on success, a confirmation screen is shown and `sessionStorage` progress is cleared.

---

## 12. Feature-module verification (suite)

| Item | Verdict | Evidence |
|---|---|---|
| On-site / in-center **services** scheduling | ✅ EXISTS | `spryker-feature/self-service-portal`. `SspService.scheduledAt`, `ServiceConditions.servicePointIds` (location via ServicePoint domain). ShipmentType keys `on-site-service` / `in-center-service` (extend OOTB `delivery`+`pickup`). `ServiceDateTimePreAddToCartPlugin`, `ItemSchedulerForm`. |
| **Recurring order** (Summary checkbox) | ✅ EXISTS | `spryker-feature/order-experience-management`. Already wired: `summary.twig` renders `{% widget 'RecurringOrderSelectorWidget' %}`. Own Zed state machine `config/Zed/StateMachine/RecurringOrder`. |
| **Budget & cost-centre** | ✅ EXISTS (backend + Yves widget, already placed) | `spryker-feature/purchasing-control`. Tables `spy_cost_center`, `spy_budget`, `spy_budget_consumption`; adds `fk_cost_center`+`fk_budget` to `spy_quote` and `spy_sales_order`. `Budget` transfer has `amount`, `consumedAmount`, `remainingAmount`, `enforcementRule`. Consumption wired via `ConsumeBudgetCheckoutPostSavePlugin`. Yves widget `CostCenterSelectorWidget` (`src/SprykerFeature/.../Yves/PurchasingControl/Widget/CostCenterSelectorWidget.php`) is rendered today at `CheckoutPage/.../views/summary/summary.twig:261`. Remaining work = re-skin + placement decision only. |
| **Two-level shipping (speed→carrier)** | ⚠️ NET-NEW modelling | `spy_shipment_method.fk_shipment_carrier` required → a method belongs to one carrier (FedEx/UPS/DHL come free). But `ShipmentCarrier` = `{id,name,isActive}` only; `ShipmentMethod` has no service-level — only `deliveryTime` (int secs) + free-text `name`. "Ground/Express/Overnight" must be encoded via a name convention or a new attribute/glossary layer. Nothing OOTB groups methods by speed. |
| **Saved / tokenised payment cards** | ❌ NOT FOUND → out of scope | `Payment` transfer has no `token`/`card`/`onFile`; no `CustomerPayment`/card-vault module. Payment selection is stateless per order (PSP auth per checkout). |
| QuoteRequest · QuoteApproval · ShipmentType · ClickAndCollectExample · MerchantShipment | ✅ all present | `spryker/quote-request`, `quote-approval`, `shipment-type`, `click-and-collect-example`, `merchant-shipment` (+ their Yves/RestApi companions). |

**Net-new work:** only the two-level shipping speed model (with a flat-list MVP fallback).
**Out of scope:** stored/tokenised payment cards; freight/oversized.

Note: `quote-approval` also provides per-user approval spend *limits* via `PermissionLimitCalculator` — distinct from the budget allowance above; useful if the budget UX ever needs an approval threshold.
