---
name: frontend-components
description: Spryker ShopUi component system — atomic design, creating/overriding/extending components, include vs embed, vendor lookup. Use when creating new components or overriding existing ones.
---

# ShopUi Component System

## Atomic design levels

| Level     | Folder                  | Examples                            |
| --------- | ----------------------- | ----------------------------------- |
| Atoms     | `components/atoms/`     | icon, button, badge, tag            |
| Molecules | `components/molecules/` | banner, card, lazy-image, jumbotron |
| Organisms | `components/organisms/` | navigation, product-list            |

## File structure (required for every component)

```
{component-name}/
├── {component-name}.twig   ← template
├── {component-name}.scss   ← styles
└── index.ts                ← webpack entry (can be empty)
```

All names **kebab-case**.

## Component locations

```
# Project level (your code)
src/Pyz/Yves/ShopUi/Theme/default/components/{atoms|molecules|organisms}/

# Vendor / core (SprykerShop)
vendor/spryker-shop/shop-ui/src/SprykerShop/Yves/ShopUi/Theme/default/components/

# Per-module vendor (e.g. ProductWidget)
vendor/spryker-shop/product-widget/src/SprykerShop/Yves/ProductWidget/Theme/default/components/
```

**Always check vendor before building from scratch.**

## Override: replace a vendor component

Mirror the vendor path under `src/Pyz/` with identical file names:

```
vendor: vendor/spryker-shop/shop-ui/.../molecules/banner/banner.twig
override: src/Pyz/Yves/ShopUi/Theme/default/components/molecules/banner/banner.twig
```

Project-level files automatically take precedence — no registration needed.

## Extend: inherit vendor blocks

Preferred over full override — reduces upgrade risk.

```twig
{% extends molecule('banner', '@SprykerShop:ShopUi') %}

{% define config = { name: 'banner', tag: 'article' } %}

{# Only override the blocks you need to change #}
{% block body %}
    ...custom content...
{% endblock %}
```

Override only SCSS (keep vendor Twig):

```scss
@include shop-ui-banner {
    &__title {
        color: red;
    } // extend via @content hook
}
```

## include vs embed

**include** — use as-is, pass data only:

```twig
{% include molecule('lazy-image') with {
    class: component.renderClass(config.name ~ '__image', modifiers),
    data: {
        imageSrc: data.imageUrl,
        imageTitle: data.imageAlt,
        isBackground: true,
    },
} only %}
```

- `only` keyword isolates the context (required)
- `class` adds extra CSS classes to root element
- Cannot modify internal blocks

**embed** — include + override blocks:

```twig
{% embed molecule('lazy-image') with { data: { ... } } %}
    {% block content %}
        {{ parent() }}
        <div class="overlay">...</div>
    {% endblock %}
{% endembed %}
```

- Can inject content into component's blocks
- Use when include is not flexible enough

## Key vendor components

### lazy-image (molecule)

Lazy loads via `viewport-intersection-observer` JS + `<noscript>` fallback.

```twig
{% include molecule('lazy-image') with {
    data: {
        imageSrc: data.imageUrl,
        imageTitle: data.imageAlt,
        isBackground: true,   // → <div class="lazy-image__background" data-background-image="url(...)">
                              // false → <img data-src="...">
        imagePlaceholder: '',  // base64 or URL shown before load
    },
} only %}
```

`isBackground: true` needs CSS on the background div:

```scss
&__image-wrapper .lazy-image__background {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
}
```

### icon (atom) — SVG sprite

```twig
{% include atom('icon') with {
    class: 'my-icon',
    data: { name: 'chevron-right' },
} only %}
```

### font-icon (atom) — Material Design

```twig
{% include atom('font-icon') with {
    data: { name: 'arrow_forward' },
} only %}
```

## Custom element tag — required for TS components

When a component has TypeScript behavior (i.e., `index.ts` calls `register('my-component', ...)`), **`config.tag` must match `config.name`** in the Twig config. Otherwise the component renders as `<div>` and the custom element never initializes.

```twig
{% define config = {
    name: 'mega-menu',
    tag: 'mega-menu',
} %}
```

The vendor `component.twig` defaults `tag` to `'div'`. The `register()` call uses `customElements.define()`, which only fires lifecycle callbacks on elements matching the registered tag name. If the rendered HTML tag is `<div>` instead of `<mega-menu>`, the TS class never attaches.

The `custom-element` CSS class is also only added when `config.name == config.tag` (line 43 of `component.twig`).

## Modifiers

Passed as array from the caller:

```twig
{% include molecule('banner') with {
    modifiers: ['small'],
    data: { ... },
} only %}
```

The base `model('component')` adds `{name}--{modifier}` classes to the **root** element automatically.

Use `component.renderClass()` to propagate modifiers to child elements:

```twig
class="{{ component.renderClass(config.name ~ '__body', modifiers) }}"
{# → "banner__body  banner__body--small" #}
```

## Preset-based component architecture

When multiple UI variants share the same trigger+dropdown structure, encode them as **presets** in a single molecule rather than creating separate components:

```twig
{% define data = {
    preset: required,
    isDrawer: false,
    drillDownTarget: '',
    panelOnly: false,
} %}
```

**`isDrawer: true`** — mobile mode: renders only the trigger with `js-side-drawer__drill-down-trigger` class and `data-target-panel` attribute; TS finds no `js-{name}__trigger` → exits early, no hover events wired.

**`panelOnly: true`** — skips trigger + wrapper entirely; renders only the inner content block. Used when the same content needs to appear both in a desktop dropdown and a mobile drawer panel body.

This pattern lets one component serve three contexts:

- Desktop: trigger + dropdown (hover/aria-expanded driven)
- Drawer trigger: drill-down button only (no dropdown)
- Drawer panel body: content only (no trigger, no wrapper)

## Server-rendered runtime updates for Twig surfaces

In this project, "rerendering a Twig component at runtime" means rendering Twig again on the server and replacing part of the DOM from ShopUi JS. Twig itself is not a browser-side runtime here.

Prefer the existing ShopUi async fragment pattern when the server should stay the source of truth for the HTML:

```twig
{% include molecule('ajax-provider') with {
    class: ajaxProviderClass,
    attributes: {
        url: path('cart/async/mini-cart-view', {
            view: data.view,
        }),
        method: 'POST',
        'event-host': 'CART_UPDATE_EVENT',
        'element-visibility-checker-class-name': ajaxContentClass,
    },
} only %}

{% include molecule('ajax-renderer') with {
    attributes: {
        'provider-class-name': ajaxProviderClass,
        'target-class-name': ajaxContentClass,
        'mount-after-render': true,
    },
} only %}
```

Keep the initial render and async response on the same inner Twig block when possible:

```twig
{% block template %}
    {{ block('contentInner') }}
{% endblock %}
```

Use local component TS only when the trigger logic is interactive and component-owned, as in `autocomplete-form` or `quick-order-row`. Even there, the response is still server-driven and the DOM still needs remount or listener rebinding after replacement.

## Widget deep block override

Override blocks inside nested widgets using successive `{% block %}` overrides:

```twig
{% widget 'SspListMenuItemWidget' args [false] only %}
    {% block menu %}
        {% widget widgetData only %}
            {% block content %}
                <a href="{{ data.url }}" class="account-menu__row">
                    <span class="account-menu__row-label">{{ data.label }}</span>
                </a>
            {% endblock %}
        {% endwidget %}
    {% endblock %}
{% endwidget %}
```

The inner `{% widget widgetData only %}` re-renders each item widget with your custom `content` block. `widgetData` is the item passed down from the parent widget's `menu` block.

## `account-menu` molecule data contract

```twig
{% include molecule('account-menu') with {
    data: {
        tabs: [{
            id: 'user-account',
            label: 'translation.key',
            items: [
                { label: 'key', icon: 'material_icon_name', url: url('route'), isDanger: true },
            ],
        }],
    },
} only %}
```

- Single tab → renders flat list, no tab switcher
- Multiple tabs → renders tabbed interface with tab switcher
- `isDanger: true` → applies `--danger` modifier (red color) to the row

## Project-specific header widget AJAX wrappers

When restyling a widget-driven header dropdown, do not use a `{% widget %}` `body` override only to pull `_widget` data into custom markup if the vendor widget view owns the async contract.

In this project, `ShoppingListNavigationMenuWidget` gets its header refresh behavior from the vendor `shopping-list-shop-list.twig` body, which contains the `ajax-provider` and `ajax-renderer` for:

- route: `shopping-list/async/navigation-widget/view`
- event host: `SHOPPING_LIST_UPDATE_EVENT`

This project-specific pitfall breaks the header if you replace the whole body:

```twig
{% widget 'ShoppingListNavigationMenuWidget' only %}
    {% block body %}
        {% include molecule('shopping-list-panel', 'ShoppingListWidget') with {
            data: {
                shoppingLists: _widget.shoppingListCollection.shoppingLists,
            },
        } only %}
    {% endblock %}
{% endwidget %}
```

Prefer reusing the shared widget view in AJAX mode and keep the async partial aligned with the same header preset or class contract instead of hardcoding old consumers like `user-navigation`.

When the same ShoppingListWidget override serves multiple consumers, carry the consumer identifier through the existing async route and read it back from `app.request` so the initial render and async refresh resolve to the same markup:

```twig
{% set parentName = app.request.get('parentName') | default(data.parentName | default('shopping-list-shop-list')) %}

{% include molecule('ajax-provider') with {
    class: ajaxProviderClass,
    attributes: {
        url: path('shopping-list/async/navigation-widget/view', {
            parentName: parentName,
        }),
        method: 'POST',
        'event-host': 'SHOPPING_LIST_UPDATE_EVENT',
    },
} only %}

{% set data = {
    isAjaxMode: true,
    parentName: app.request.get('parentName') | default('shopping-list-shop-list'),
} %}
```

Use this to switch between legacy `user-navigation` markup and header-specific markup like `header-shopping-list-pill` without introducing a second route, event, or header-only widget body override.

When the same widget override is rendered by more than one live consumer on the same page, never reuse the vendor's single-consumer AJAX classes across those consumers.

In the current shopping-list quick-link flow, the shared project view can exist in both the desktop header and the side-drawer panel at once. `ajax-renderer.ts` resolves both `provider-class-name` and `target-class-name` with the first `getElementsByClassName(...)[0]` match, and `ajax-provider.ts` resolves `element-visibility-checker-class-name` with the first `document.querySelector(...)` match. If both consumers render `js-shopping-list-provider` and `js-shopping-list-content`, one surface can steal the other's renderer binding or visibility gating.

Bad:

```twig
{% include molecule('ajax-provider') with {
    class: 'js-shopping-list-provider',
    attributes: {
        'element-visibility-checker-class-name': 'js-shopping-list-content',
    },
} only %}

{% include molecule('ajax-renderer') with {
    attributes: {
        'provider-class-name': 'js-shopping-list-provider',
        'target-class-name': 'js-shopping-list-content',
    },
} only %}
```

Good: derive the classes from the same consumer key already used by the widget flow and use that key consistently at the DOM and AJAX consumption sites. For the current header plus side-drawer setup, prefer the existing `view` key:

```twig
{% set viewMode = app.request.get('view') | default(data.view | default('header')) %}

<span class="{{ viewMode == 'side-drawer' ? 'js-shopping-list-content-side-drawer' : 'js-shopping-list-content-header' }}">
    ...
</span>

{% include molecule('ajax-provider') with {
    class: viewMode == 'side-drawer' ? 'js-shopping-list-provider-side-drawer' : 'js-shopping-list-provider-header',
    attributes: {
        url: path('shopping-list/async/navigation-widget/view', {
            view: viewMode,
        }),
        method: 'POST',
        'event-host': 'SHOPPING_LIST_UPDATE_EVENT',
        'element-visibility-checker-class-name': viewMode == 'side-drawer' ? 'js-shopping-list-content-side-drawer' : 'js-shopping-list-content-header',
    },
} only %}

{% include molecule('ajax-renderer') with {
    attributes: {
        'provider-class-name': viewMode == 'side-drawer' ? 'js-shopping-list-provider-side-drawer' : 'js-shopping-list-provider-header',
        'target-class-name': viewMode == 'side-drawer' ? 'js-shopping-list-content-side-drawer' : 'js-shopping-list-content-header',
        'mount-after-render': true,
    },
} only %}
```

The current runtime call path for this flow uses `molecule('header-shopping-list-pill', 'ShoppingListWidget')`, not the same-named molecule under `ShopUi`, so target the `ShoppingListWidget` component when fixing this contract.

Do not assume matching Twig structure means a second widget has inherited the same AJAX safety as `MiniCartWidget`.

`MultiCartWidget` works in the current header plus side-drawer layout because its vendor view already derives consumer-specific classes from `ajaxClassSuffix`:

```twig
{% set ajaxClassSuffix = data.ajaxClassSuffix ?: 'main' %}
{% set ajaxContentClass = 'js-mini-cart-content-' ~ ajaxClassSuffix %}
{% set ajaxCartProvider = data.isAjaxMode ? 'js-mini-cart-provider-' ~ ajaxClassSuffix : null %}
```

`ShoppingListWidget` does not have an equivalent vendor suffix hook; its vendor view hardcodes `js-shopping-list-content` and `js-shopping-list-provider`. In this project, a shared shopping-list override that serves more than one live consumer must derive `js-shopping-list-content-{view}` and `js-shopping-list-provider-{view}` itself inside the project view override. Reusing the vendor shopping-list class names is not equivalent to the working mini-cart flow.

## Project-specific shopping-list header dropdown content

In the current header quick-link setup, keep `ShoppingListNavigationMenuWidget` rendered from `header.twig` and keep the project `shopping-list-shop-list` view override limited to `contentInner`.

Fix the desktop dropdown inside `header-shopping-list-pill` by reusing the existing styled `shopping-list-panel` molecule:

```twig
{% include molecule('shopping-list-panel', 'ShoppingListWidget') with {
    data: {
        shoppingLists: data.shoppingListCollection.shoppingLists,
    },
} only %}
```

This swap is safe for async refresh because the vendor `shopping-list-shop-list-async.twig` still renders `contentInner` from the shared `shopping-list-shop-list` view override, so the project keeps owning the inner markup without needing its own async override file:

```twig
{% block template %}
    {{ block('contentInner') }}
{% endblock %}
```

That means changing only `header-shopping-list-pill.twig` updates both the initial header render and the async response without editing `header.twig`, the project `shopping-list-shop-list` view wrapper, or the vendor widget body.

Do not render the legacy `shopping-list-shop-list` molecule inside `header-shopping-list-pill__dropdown`. That molecule still outputs old sub-nav markup (`__link`, `__sub-nav`, close button, `menu` classes) and has no project-local SCSS, so it is the wrong inner surface for the new header dropdown shell.

## Header nav pill interaction model

`header-shopping-list-pill` inherits `HeaderNavPill`, so its desktop dropdown opens on hover or keyboard focus and closes on mouse leave, focus loss, or `Escape`.

The trigger still renders as an anchor to the full shopping-list page:

```twig
<a
    href="{{ url('shopping-list') }}"
    class="link--no-decoration {{ config.name }}__trigger {{ config.jsName }}__trigger"
>
```

That means a direct click is navigation, not a JS-only toggle. When testing or extending this pill, verify the dropdown through `mouseenter` or `focusin` behavior instead of assuming click-to-open.

## Twig helper functions

```twig
{# resolve route URL #}
{{ functionExists('generatePath') ? generatePath('route-name') : '/fallback' }}

{# translate key #}
{{ 'my.translation.key' | trans }}

{# render class with modifiers #}
{{ component.renderClass(config.name ~ '__element', modifiers) }}

{# render HTML attributes object #}
{{ component.renderAttributes(attributesObject) }}
```
