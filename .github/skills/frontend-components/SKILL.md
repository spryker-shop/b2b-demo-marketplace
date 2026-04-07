---
name: frontend-components
description: Spryker ShopUi component system — atomic design, creating/overriding/extending components, include vs embed, vendor lookup. Use when creating new components or overriding existing ones.
---

# ShopUi Component System

## Atomic design levels

| Level | Folder | Examples |
|-------|--------|---------|
| Atoms | `components/atoms/` | icon, button, badge, tag |
| Molecules | `components/molecules/` | banner, card, lazy-image, jumbotron |
| Organisms | `components/organisms/` | navigation, product-list |

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
    &__title { color: red; }   // extend via @content hook
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
