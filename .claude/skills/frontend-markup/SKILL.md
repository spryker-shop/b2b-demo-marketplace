---
name: frontend-markup
description: Spryker ShopUi Twig/SCSS markup patterns — BEM, renderClass modifiers, design tokens, grid, line-clamp. Use when creating or editing ShopUi component templates and styles.
---

# ShopUi Markup Patterns

## Twig component anatomy

```twig
{% extends model('component') %}   {# standalone — no vendor base #}
{% import _self as component %}

{% define config = {
    name: 'my-component',          {# kebab-case, matches folder/file name #}
    tag: 'article',                {# root HTML tag, default: div #}
} %}

{% define data = {
    field: '',                     {# all fields must have defaults #}
} %}

{% block body %}
    <div class="{{ component.renderClass(config.name ~ '__body', modifiers) }}">
        ...
    </div>
{% endblock %}
```

**Extending vendor:**
```twig
{% extends molecule('banner', '@SprykerShop:ShopUi') %}
{# then override only the blocks you need #}
```

## Data contract rules
- **Never rename or remove** existing `data` fields — breaks all callers
- Adding new optional fields (with defaults) is safe
- `data.content | raw` — legacy pattern for passing pre-rendered HTML blocks

## renderClass — modifier propagation

`component.renderClass(elementName, modifiers)` puts modifier classes **directly on child elements**.

```twig
{# modifiers: ['small'] → renders: "banner__body  banner__body--small" #}
<div class="{{ component.renderClass(config.name ~ '__body', modifiers) }}">
```

**SCSS consequence — write flat BEM, no nesting:**
```scss
// ✅ flat — when renderClass is on the element
&__body--small  { padding: var(--scale-16); }
&__body--medium { padding: var(--scale-20); }

// ⚠️ ancestor selector — only for elements inside data.content | raw
&--small #{$name}__title { font-size: var(--heading-sm-size); }
```

## SCSS component mixin pattern

```scss
@mixin shop-ui-{name}($name: '.{name}') {
    #{$name} {
        // base styles
        &__element { ... }
        &__element--modifier { ... }           // flat — preferred
        &--modifier #{$name}__element { ... }  // only for raw HTML content
        @content;                              // hook for project-level extension
    }
}

@include shop-ui-{name};
```

Extension from outside (project-level):
```scss
@include shop-ui-card {
    &__container--custom { color: red; }
}
```

## Design tokens  (`design-tokens.css`)

```scss
// Typography — scales: {sm, md, lg}
font-size:      var(--heading-lg-size);
line-height:    var(--heading-lg-line-height);
font-weight:    var(--heading-lg-weight);
letter-spacing: var(--heading-lg-letter-spacing);
// also: --body-{sm,md}-*, --button-{sm,md}-*

// Spacing: --scale-{0,4,6,8,10,12,16,20,24,32,40,48}
padding: var(--scale-24);
gap:     var(--scale-16);

// Colors
color:            var(--text-primary);           // #111827
color:            var(--text-secondary);         // #6b7280
color:            var(--text-inverse);           // #fff
background-color: var(--background-page);        // #fff
background-color: var(--background-brand-primary);
background-color: var(--background-brand-hover);

// Shape
border:        var(--stroke-sm) solid var(--border-default);
border-radius: var(--radius-lg);   // 12px cards
border-radius: var(--radius-md);   // 8px  buttons/badges
border-radius: var(--radius-sm);   // 4px  tags

// Shadow
box-shadow: var(--shadows-sm-x) var(--shadows-sm-y) var(--shadows-sm-blur) var(--shadows-sm-spread) var(--shadows-sm-color);
box-shadow: var(--shadows-md-x) var(--shadows-md-y) var(--shadows-md-blur) var(--shadows-md-spread) var(--shadows-md-color);
```

## Line clamp (truncation)

```scss
display: -webkit-box;
-webkit-line-clamp: 2;
line-clamp: 2;
-webkit-box-orient: vertical;
overflow: hidden;
max-height: calc(2 * var(--heading-sm-line-height));  // calc(), never rem()
```

## Grid (`_grid.scss`)

Modern flexbox — no floats, no negative-margin gutter hack.

```scss
// Gap via CSS custom property — col widths auto-adjust
&--gap { --grid-gap: #{$setting-grid-space * 2}; gap: var(--grid-gap); }

// Col width formula: K/N * 100% + (K/N - 1) * gap
// var(--grid-gap, 0px) → 0 when no gap modifier = backward compat
width: calc(#{percentage($ratio)} + #{$ratio - 1} * var(--grid-gap, 0px));
```

Sass division: always `math.div()`, never `/`:
```scss
@use 'sass:math';
$ratio: math.div($column, $setting-grid-columns);
```

## Vendor atoms to reuse

```twig
{# SVG sprite icon #}
{% include atom('icon') with { data: { name: 'icon_name' } } only %}

{# Material Design font icon #}
{% include atom('font-icon') with { data: { name: 'arrow_forward' } } only %}
```
