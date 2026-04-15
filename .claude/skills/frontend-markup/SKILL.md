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
&__body--small {
    padding: var(--scale-16);
}
&__body--medium {
    padding: var(--scale-20);
}

// ⚠️ ancestor selector — only for elements inside data.content | raw
&--small #{$name}__title {
    font-size: var(--heading-sm-size);
}
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
    &__container--custom {
        color: red;
    }
}
```

## Badge-derived chips

When a component is only a semantic variant of a badge-like chip, reuse `shop-ui-badge($name)` instead of copying the shell.

Confirmed project patterns:

```scss
@include shop-ui-badge('.badge') {
    @include helper-badge-chip;
    font-weight: var(--font-weight-medium);

    // badge-specific modifiers only
}

@include shop-ui-status {
    $name: &;

    @include helper-badge-chip;
    font-weight: var(--font-weight-medium);

    // status-specific state mappings only
}

@mixin quote-request-agent-widget-request-status($name: '.request-status') {
    @include shop-ui-badge($name) {
        // request-status-specific modifiers only
    }
}
```

Rule of thumb:

- `badge` owns the shared chip shell plus badge typography and semantic modifiers.
- The project `status` override reuses the shared chip shell through `@include helper-badge-chip;` inside `@include shop-ui-status { ... }` and owns its own font-weight plus status-specific state mappings.
- A molecule like `label-group` should **not** inherit the full badge contract at the root when it also owns layout, hover, positioning, or product-label-specific semantics.
- For `label-group`, reuse only the inner chip element styles such as `&__text` or `&__counter`, and keep molecule layout/modifiers local.

If the shared chip shell must be reused across multiple components without depending on component import order, extract only the shell into a global ShopUi helper partial and wire it through `styles/shared.scss`:

```scss
// src/Pyz/Yves/ShopUi/Theme/default/styles/helpers/_badge-chip.scss
@mixin helper-badge-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: rem(var(--scale-2)) rem(var(--scale-8));
    gap: rem(var(--scale-4));
    font-size: rem(var(--body-sm-size));
    line-height: rem(var(--body-sm-line-height));
    border-radius: rem(var(--radius-sm));
    background: var(--background-muted);
    color: var(--text-primary);
    border: none;
}

// src/Pyz/Yves/ShopUi/Theme/default/styles/shared.scss
@import 'helpers/badge-chip';

// component SCSS
@include shop-ui-badge('.badge') {
    @include helper-badge-chip;
    font-weight: var(--font-weight-medium);

    &--md {
        padding: rem(var(--scale-4)) rem(var(--scale-8));
        font-size: rem(var(--font-size-14));
        line-height: rem(var(--font-line-height-20));
    }

    &--solid {
        color: var(--text-inverse);
        background: var(--background-inverse);
    }
}
```

Keep only the reusable base chip shell in the helper. Font weight stays with each semantic consumer.

Do not move badge-specific selectors like `&--md`, base `&--solid`, or molecule-specific state/color/layout mappings into `helper-badge-chip`.

If badge and status still duplicate the same semantic declaration bodies after the shell extraction, use a separate declaration-only helper partial instead of adding selectors to `helper-badge-chip`:

```scss
// src/Pyz/Yves/ShopUi/Theme/default/styles/helpers/_badge-semantic.scss
@mixin helper-badge-semantic-subtle($state) {
    color: var(--text-state-#{$state});
    background: var(--background-state-#{$state}-subtle);
}

@mixin helper-badge-semantic-bold-background($state) {
    background: var(--background-state-#{$state}-bold);
}

// badge.scss keeps badge selectors local
&--info {
    @include helper-badge-semantic-subtle(info);
}

&--info#{&}--solid {
    @include helper-badge-semantic-bold-background(info);
}

// status.scss keeps business-status mapping local
&--shipped,
&--in-stock {
    @include helper-badge-semantic-subtle(success);

    #{$name}--sold {
        @include helper-badge-semantic-bold-background(success);
    }
}
```

Project rule: the reusable helper may own semantic declaration recipes, but badge still owns badge API selectors and status still owns business-status-to-semantic mapping. Do not extract badge-only `&--md` or the base `&--solid` inverse declarations into the helper.

Inside `badge.scss`, keep the base `&--solid` block after the subtle semantic modifier blocks and before the `&--state#{&}--solid` background overrides. That source order is required so `.badge--info.badge--solid` keeps `color: var(--text-inverse)` from the base solid modifier while the semantic solid selector replaces only the background.

If a non-badge consumer like `label-group__text` or `label-group__counter` reuses the helper, restore only the local element semantics the helper would otherwise override. In this project that explicitly includes `display`:

```scss
&__text {
    @include helper-badge-chip;
    display: block;
}

&__counter {
    @include helper-badge-chip;
    display: inline-block;
}
```

## Design tokens (`design-tokens.css`)

```scss
// Typography — scales: {sm, md, lg}
font-size: var(--heading-lg-size);
line-height: var(--heading-lg-line-height);
font-weight: var(--heading-lg-weight);
letter-spacing: var(--heading-lg-letter-spacing);
// also: --body-{sm,md}-*, --button-{sm,md}-*

// Spacing: --scale-{0,4,6,8,10,12,16,20,24,32,40,48}
padding: var(--scale-24);
gap: var(--scale-16);

// Colors
color: var(--text-primary); // #111827
color: var(--text-secondary); // #6b7280
color: var(--text-inverse); // #fff
background-color: var(--background-page); // #fff
background-color: var(--background-brand-primary);
background-color: var(--background-brand-hover);

// Shape
border: var(--stroke-sm) solid var(--border-default);
border-radius: var(--radius-lg); // 12px cards
border-radius: var(--radius-md); // 8px  buttons/badges
border-radius: var(--radius-sm); // 4px  tags

// Shadow
box-shadow: var(--shadows-sm-x) var(--shadows-sm-y) var(--shadows-sm-blur) var(--shadows-sm-spread)
    var(--shadows-sm-color);
box-shadow: var(--shadows-md-x) var(--shadows-md-y) var(--shadows-md-blur) var(--shadows-md-spread)
    var(--shadows-md-color);
```

## Line clamp (truncation)

```scss
display: -webkit-box;
-webkit-line-clamp: 2;
line-clamp: 2;
-webkit-box-orient: vertical;
overflow: hidden;
max-height: calc(2 * var(--heading-sm-line-height)); // calc(), never rem()
```

## Grid (`_grid.scss`)

Modern flexbox — no floats, no negative-margin gutter hack.

```scss
// Gap via CSS custom property — col widths auto-adjust
&--gap {
    --grid-gap: #{$setting-grid-space * 2};
    gap: var(--grid-gap);
}

// Col width formula: K/N * 100% + (K/N - 1) * gap
// var(--grid-gap, 0px) → 0 when no gap modifier = backward compat
width: calc(#{percentage($ratio)} + #{$ratio - 1} * var(--grid-gap, 0px));
```

Sass division: always `math.div()`, never `/`:

```scss
@use 'sass:math';
$ratio: math.div($column, $setting-grid-columns);
```

## Twig content capture (`{% set %}...{% endset %}`)

Capture rendered HTML into a variable to avoid repeating logic in multiple branches:

```twig
{% set presetContent %}
    {% if data.preset == 'user-account' %}
        {% include molecule('account-menu') with { data: { tabs: accountTabs } } only %}
    {% elseif data.preset == 'self-service' %}
        <div class="account-menu__list">...</div>
    {% endif %}
{% endset %}

{% if data.panelOnly %}
    {{ presetContent | raw }}
{% else %}
    <div class="{{ config.name }}__dropdown">
        {{ presetContent | raw }}
    </div>
{% endif %}
```

Use when the same rendered block needs to appear in two different wrapper contexts.

## CSS: `aria-expanded` as open/close state source

**Do NOT use `:focus-within`** for dropdown open/close — it stays active after click because the button keeps focus, so hover-out doesn't close the dropdown.

Instead, make TS set `aria-expanded` on the trigger as the single source of truth, and read it in CSS:

```scss
&__trigger[aria-expanded='true'] ~ &__dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

&__trigger[aria-expanded='true'] &__chevron {
    transform: rotate(180deg);
}
```

Note: `~ &__dropdown` is the **sibling combinator** (trigger and dropdown are siblings).
Note: `&__chevron` uses **descendant** selector (chevron is inside trigger).

CSS hover still works alongside this — both selectors open the dropdown:

```scss
&:hover &__dropdown { ... }               // hover path
&__trigger[aria-expanded='true'] ~ &__dropdown { ... }  // click path (TS-driven)
```

## Hover bridge `::after` (gap between trigger and dropdown)

Prevents dropdown from closing when mouse moves through the gap between trigger and dropdown panel:

```scss
&__trigger {
    position: relative;

    &::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        right: 0;
        height: 8px;
    }
}
```

The `::after` fills the visual gap — the mouse stays within the trigger's hit area while crossing to the dropdown.

## Vendor atoms to reuse

```twig
{# SVG sprite icon #}
{% include atom('icon') with { data: { name: 'icon_name' } } only %}

{# Material Design font icon #}
{% include atom('font-icon') with { data: { name: 'arrow_forward' } } only %}
```

## Footer payment strip

Desktop/tablet payment strip in the footer is rendered inside `footer__logos` and uses existing navigation templates, not a bespoke logo component.

```twig
{% block logos %}
    <div class="{{ config.name }}__logos">
        {% block logosInner %}
            {% cms_slot 'slt-footer-partners' %}
        {% endblock %}
    </div>
{% endblock %}
```

Project-specific styling rule:

```scss
.footer__logos {
    .navigation-item--footer-logo,
    .navigation-footer-item--footer-logo {
        @include helper-breakpoint-media-min($md) {
            display: flex;
            align-items: center;
        }
    }

    .navigation-item__title--footer-logo,
    .title--footer-logo {
        display: block;
        white-space: nowrap;
        color: var(--text-secondary);
        text-transform: none;
    }

    .menu--footer {
        justify-content: flex-start;
        gap: var(--scale-24);
        margin: 0;
        padding: 0;
    }
}
```

Do not hide `title--footer-logo` from `md` upward. The Figma payment strip keeps the copy visible next to the logo list on tablet/desktop.

## Footer navigation section — class name gotcha

The nav columns inside `footer__navigation` are rendered by `NavigationWidget` via `navigation-list` molecule. The actual DOM class names differ from what you might expect from the component name:

| What you might write             | Actual DOM class                           |
| -------------------------------- | ------------------------------------------ |
| `.navigation-list__list--footer` | `.list--footer` (on `<ul>`)                |
| `.navigation-list__link--footer` | `.link--footer` (on `<a>`)                 |
| `.navigation-footer-item`        | `.navigation-item` (on the column wrapper) |

`renderClass('list', ['secondary', 'footer'])` → `list list--secondary list--footer`
`renderClass('link', ['secondary', 'footer'])` → `link link--secondary link--footer`

**Rule:** Always scope these inside `.footer__navigation` to avoid bleeding into other navigation contexts.

```scss
.footer__navigation {
    .navigation-item {
        display: flex;
        flex-direction: column;
        gap: var(--scale-20);
    }

    .list--footer {
        display: flex;
        flex-direction: column;
        gap: var(--scale-8);
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .link--footer {
        font-size: var(--body-md-size);
        font-weight: var(--font-weight-regular);
        line-height: var(--body-md-line-height);
        color: var(--text-primary);
        text-decoration: none;
    }
}
```

## Footer navigation — mobile accordion override

The `NavigationWidget` wraps each nav list in a `<div class="js-*__target is-hidden-sm-only">` which is `display:none` on mobile. When the toggler icon is hidden (per Figma — no chevron), the lists become permanently invisible on mobile.

Fix: force-show the target wrappers inside `__navigation`:

```scss
&__navigation {
    .toggler-accordion__icon {
        display: none;
    }

    [class*='__target'] {
        display: block;
    }
}
```

## Hiding custom elements inside flex containers — use class selector, not element tag

Spryker custom elements (e.g. `toggler-accordion`) extend `Component` which adds the `custom-element` class. That class carries `.custom-element { display: block }` in the global stylesheet.

**Problem:** If you try to hide the element with a bare tag selector inside a BEM block, the browser silently drops the rule from the CSSOM (custom element tag selectors are unreliable in descendant rules). The `.custom-element` class rule wins, so the element stays `display: block` — and as a zero-width flex item it still consumes gap space, pushing other flex children to wrap.

**Fix:** Target the BEM class instead of the tag:

```scss
// ❌ does not work — browser ignores this rule in the CSSOM
&__navigation {
    toggler-accordion {
        display: none;
    }
}

// ✅ correct — class selector is parsed reliably and has higher specificity
&__navigation {
    .toggler-accordion {
        display: none;
    }
}
```

This applies wherever you need to suppress a custom element inside a flex/grid container for desktop layout.

## CMS-rendered sections live in a CSV — no page twig wrappers

Sections on the homepage (Featured Products, Top sellers, Your Operations Are Our Priority, Featured Categories) are **not** hardcoded in `home.twig`. They are CMS blocks defined in:

```
data/import/common/common/cms_block.csv
```

Each row binds a template path (e.g. `@CmsBlock/template/section_block.twig`, `@CmsBlock/template/section_block.twig`, `@CmsBlock/template/banner_block.twig`) and placeholder HTML for `title` / `content` / `imageUrl` / `link`. The section block templates embed `organism('section')` with `modifiers: ['secondary']`, `modifiers: ['secondary', 'bg-dark']`, etc., and the HTML placeholders provide `<h2 class="section__title section__title--secondary">…</h2>` + `<p class="section__sub-title">…</p>` / `<p class="section__text">…</p>`.

**Implication for visual changes:** Never try to wrap CMS-rendered sections in a page-level twig wrapper or scope SCSS under a page class — the section organism is the shared owner of the layout, typography, and spacing. Edit:

- `components/organisms/section/section.scss` for section-level visual rules.
- `data/import/common/common/cms_block.csv` + `docker/sdk console data:import` if the CSV content itself must change (e.g. to add a new modifier class to a title).

## Editing shared SCSS: new rules on top, legacy below

When redesign-aligned rules replace older ones inside a component mixin, put the **new** rules at the top of the mixin body and keep the older rules below with a `// LEGACY` comment. Do not delete legacy rules that are still referenced by non-redesigned pages — tag them so the source of truth is clear to the next maintainer:

```scss
@mixin shop-ui-section($name: '.section') {
    #{$name} {
        // Redesign-aligned base padding / typography goes here at the top.
        &--secondary {
            padding: var(--scale-32) 0;
        }

        &__title--secondary {
            font-family: var(--heading-lg-family);
            font-weight: var(--heading-lg-weight);
            font-size: var(--heading-lg-size);
            line-height: var(--heading-lg-line-height);
            text-align: left;
            color: var(--text-primary);
        }

        // LEGACY — kept for non-redesigned pages; do not reuse for new work.
        &--center { … }

        // LEGACY — kept for non-redesigned pages; do not reuse for new work.
        &--last { padding-bottom: rem(70); }
    }
}
```

## Media queries are mobile-first — always `helper-breakpoint-media-min`

Project convention: styles are **mobile-first**. Base declarations are the mobile values; larger breakpoints extend via `@include helper-breakpoint-media-min($lg)` / `$xl` / `$xxl` / `$xxxl`.

Do **not** use `helper-breakpoint-media-max($lg - 1)` to write "desktop first, then override on mobile." That inverts the cascade and breaks the shared convention.

```scss
// ✅ mobile-first
&__sub-title {
    font-size: var(--body-md-size);
    margin-bottom: var(--scale-24);

    @include helper-breakpoint-media-min($lg) {
        font-size: var(--body-lg-size);
        margin-bottom: var(--scale-40);
    }
}

// ❌ desktop-first with max-width override
&__sub-title {
    font-size: var(--body-lg-size);

    @include helper-breakpoint-media-max($lg - 1) {
        font-size: var(--body-md-size);
    }
}
```

## Slick arrow visibility — inline `display` needs `!important`

Slick writes `style="display: inline-block;"` directly onto the `<button>` when it renders prev/next arrows. A plain `display: none` in CSS loses to the inline style, so on mobile-first setups where arrows should only appear from `$lg` upward, use `!important` on both states:

```scss
.slick-arrow {
    display: none !important; /* stylelint-disable-line declaration-no-important */

    @include helper-breakpoint-media-min($lg) {
        display: block !important; /* stylelint-disable-line declaration-no-important */
        // …sizing, position, border, etc.
    }
}
```

## Slick peek / next-slide reveal on mobile

To show a partial next slide on mobile without touching the slick JS config, pad the `.slick-list` on the right. The padding eats into the list's track width so the right edge of the next slide pokes through. Reset it to `0` on desktop so the full-width track calculation is preserved for the `$lg+` layout:

```scss
&--equal-height {
    .slick-list {
        padding-right: var(--scale-48);

        @include helper-breakpoint-media-min($lg) {
            padding-right: 0;
        }
    }
}
```

## Slick-carousel prev arrow vertical alignment

The `.slick-arrow` base rule centers the arrow with `transform: translateY(-50%)`, but `.slick-prev` then sets `transform: rotate(180deg)`, which replaces the translate and drops the arrow down by half its height. Always re-apply both transforms on prev:

```scss
.slick-arrow {
    top: 50%;
    transform: translateY(-50%);
}

.slick-prev {
    transform: translateY(-50%) rotate(180deg);
}
```

## Design tokens — spacing scale

Available `--scale-*` values: `0, 2, 4, 6, 8, 12, 16, 20, 24, 32, 40, 48, 64`. There is no `--scale-10`, `--scale-28`, or `--scale-56` — don't assume them. Use `calc(…)` if an off-scale value is truly needed:

```scss
left: calc(-1 * var(--scale-48));
```

## Icon modifier sizing — never use `rem(var(--*))` for dimensions

`rem()` is a Sass function expecting a unitless number. Passing a CSS `var()` into it produces garbage output (e.g. a 150px SVG instead of 28px).

```scss
// ❌ broken — rem() cannot resolve a CSS custom property at build time
height: rem(var(--scale-7));

// ✅ correct — use a fixed px value or a raw CSS var without rem()
height: 28px;
// or if the token resolves to a known px value:
height: var(--scale-28);
```

The `&--contact` icon modifier had this bug — it rendered as 150px tall. Fixed to explicit `width/height: 20px` with `flex-shrink: 0`.
