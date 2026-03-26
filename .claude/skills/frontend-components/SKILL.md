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
