# Reactive layer for ShopUi (POC, iteration 1)

A lightweight, opt-in client-side reactive layer on top of the ShopUi component model.
Server data rendered from Twig lands in a shared signal store; any bound piece of UI
updates live when a value changes — no page reload, no per-widget imperative JS.

## Architecture

```
Twig (server)                          Browser
─────────────                          ───────
setGlobalStore({...})  ──┐
setGlobalStoreByName()  ──┼─► reactive-bind ─► <script type="application/json">
                         │   (one payload      │
component-level state  ──┘    per page)        ▼
                                        Store (signal per dot-path)
                                          ▲            │
                    store.set(path, v) ───┘            ▼ effect()
                                        data-bind-* directives
                                        (textContent / attributes / classList)
```

### `store.ts`

Singleton store (`createStore()`, window-scoped so every webpack chunk shares it).
One `@preact/signals-core` signal per dot-path (`app.currency.code`).

- `ensureSignal(path, initial?)` — get-or-create the signal for a path
- `resolveSignal(path)` — get without creating
- `get(path)` / `set(path, value)` — value access, `set` notifies all subscribers
- `subscribe(path, callback)` — effect-based subscription, returns an unsubscribe
- `hydrate(object, prefix?)` — flattens a nested object into dot-path signals

### `directives.ts`

Declarative `data-*` bindings, applied to a DOM subtree via `bindTree(root)`.
Output writes only `textContent`, attributes and `classList` — never `innerHTML`.

| Directive | Value | Effect |
| --- | --- | --- |
| `data-bind-text="path"` | one path | `element.textContent = value` |
| `data-bind-attr="name:path, name2:path2"` | pairs | `setAttribute` / `removeAttribute` (null/false removes, true sets empty) |
| `data-bind-class="class:path"` | pairs | `classList.toggle(class, Boolean(value))` |

Elements are guarded by a `WeakSet` so overlapping scans (nested reactive components)
bind once.

### `reactive-component.ts`

`ReactiveComponent extends Component` — base for components that participate in the
layer. On `init()` it binds directives inside its own subtree; `disconnectedCallback()`
(native on registered custom elements) disposes every subscription.

### `molecules/reactive-bind`

Generic declarative binder element, the only markup-level entry point:

```twig
{% embed molecule('reactive-bind') with {
    data: { state: { catalog: { cmsBlock: { idCategory: data.categoryId } } } },
} only %}
    {% block content %}
        ...any markup with data-bind-* directives...
    {% endblock %}
{% endembed %}
```

- `data.state` (optional) renders one `<script type="application/json">` payload
  (JSON_HEX_* encoded) that hydrates the store on mount — server data straight from
  Twig, no JS literals.
- The whole subtree is scanned for `data-bind-*` directives, so plain includes
  (e.g. `catalog-cms-block`) become reactive without owning any JS.
- `data.prefix` namespaces the hydrated payload.

## Global storefront state (backend)

The Twig-side global store (`GlobalStoreTwigPlugin`) is the single backend
mechanism — no extra PHP provider, no extra HTTP round-trip:

- `setGlobalStore({ ... })` — merge values into the request-scoped store
- `setGlobalStoreByName('propName', value)` — set a single value
- `getGlobalStore()` / `getGlobalStoreByName('propName')` — read back

Any template can contribute during rendering:

```twig
{% do setGlobalStoreByName('pdp.selectedOfferRef', data.offerRef) %}
```

`page-layout-main.twig` (`globalComponents` block, end of body — after every
template had a chance to call `setGlobalStore()`) seeds the request basics from
existing Twig functions (`app.locale`, `currencyIsoCode()`, `moneySymbol()`,
`getPriceMode()`, `is_granted('ROLE_USER')`) and renders ONE `reactive-bind`
payload per page via `getGlobalStore()`. The frontend store hydrates `app.*`
(and anything else contributed) from it.

## Driving an include's data/attributes from the store

Attributes of any rendered component can be bound by passing directives through the
regular `attributes` contract — no change to the component itself:

```twig
{% include molecule('catalog-cms-block', 'CmsBlockWidget') ignore missing with {
    class: 'box',
    data: { idCategory: data.categoryId, position: 'middle' },
    attributes: {
        'data-bind-attr': 'data-id-category:catalog.cmsBlock.idCategory,data-position:catalog.cmsBlock.position',
        'data-bind-class': 'is-hidden:demo.cmsBlockHidden',
    },
} only %}
```

`store.set('catalog.cmsBlock.idCategory', 123)` now updates the rendered element
live. Note the honest scope: Twig `data:` is consumed server-side at render time —
what reacts client-side is the rendered DOM (attributes/classes/text). For content
that must re-render (e.g. a different CMS block), iteration 2 would let the owning
component observe its bound attribute (`observedAttributes` / `MutationObserver`)
and re-fetch its body — the binding mechanism above stays unchanged.

## Demo (iteration 1)

`CatalogPage` view `catalog-with-cms-slot` (any category page with CMS slots, e.g.
a top-level category): a `reactive-demo` panel shows `app.*` values hydrated from
the global payload, a counter with two independent subscribers, a toggle that
hides/shows the `catalog-cms-block` include via `data-bind-class`, and an input
that live-drives its `data-id-category` via `data-bind-attr`.

## Known POC limits

- Arrays hydrate as atomic values (no per-index signals) — fine for iteration 1.
- Disposal happens via `disconnectedCallback`; plain elements bound by a
  `reactive-bind` wrapper are disposed when the wrapper disconnects.
- No computed/derived signals exposed yet (`@preact/signals-core` `computed` is
  available when needed).
