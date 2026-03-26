---
name: frontend-build
description: Spryker Yves frontend build system — webpack entry points, build commands, namespace/theme configuration, code splitting, adding JS to components. Use when working with the build pipeline or adding TypeScript/JS behavior.
---

# Yves Frontend Build System

## Build commands

```bash
npm run yves              # development build
npm run yves:watch        # watch mode — auto-rebuild on file changes
npm run yves:production   # production build (minified, optimized)
```

With parameters:
```bash
npm run yves -- -t default -t b2b-theme   # build specific themes
npm run yves -- -n DE                      # build specific namespace
npm run yves -- -c path/to/config.json    # custom config file
```

## Webpack entry points (project level)

```
src/Pyz/Yves/ShopUi/Theme/default/
├── vendor.ts   ← global external libraries (jQuery, polyfills, third-party)
├── app.ts      ← bootstrap, initializes ShopUi frontend
└── components/
    └── {name}/
        └── index.ts   ← per-component entry point (can be empty)
```

### vendor.ts — global libs
```typescript
import 'jquery';
import 'some-global-polyfill';
```

### app.ts — bootstrap
```typescript
import { bootstrap } from 'ShopUi/app';
bootstrap();
```

### index.ts — component JS registration

**Empty** (styles/template only, no JS):
```typescript
// intentionally empty
```

**With JS behavior:**
```typescript
// eager — loaded immediately on every page
export default register(
    'my-component',
    () => import(/* webpackMode: "eager" */'./my-component')
);

// lazy — code-split, loaded on demand
export default register(
    'my-component',
    () => import(/* webpackMode: "lazy" */'./my-component')
);
```

Import `register` from ShopUi app:
```typescript
import register from 'ShopUi/app/registry';
```

## Component TypeScript file

```typescript
// my-component.ts
import Component from 'ShopUi/models/component';

export default class MyComponent extends Component {
    protected readyCallback(): void {
        // runs when component DOM is ready
    }
}
```

## Output structure

```
public/Yves/assets/{namespace}/{theme}/
├── default/   ← default theme assets (fallback)
└── current/   ← active theme assets
```

Example: `public/Yves/assets/DE/default/`

## Code splitting strategy

| Chunk | Content | Load |
|-------|---------|------|
| runtime | webpack runtime | every page |
| vendor | vendor.ts (jQuery, polyfills) | every page |
| app | app.ts bootstrap | every page |
| `{component-name}` | component index.ts | per-component (lazy/eager) |

Use `lazy` for most components, `eager` only for critical-path UI.

## Namespace / theme config

File: `/config/Yves/frontend-build-config.json`

Defines:
- Namespace → available themes mapping
- Default theme per namespace
- Asset output paths
- Entry point locations

Custom namespaces must be registered here for their assets to compile.

## Adding a new dependency

```bash
npm install --save package-name        # runtime dependency
npm install --save-dev package-name    # build/dev only
```

Then import in `vendor.ts` (if global) or directly in the component file.

## Namespace-specific overrides

Higher-priority path — append namespace suffix to module folder:

```
src/Pyz/Yves/ShopUiDE/Theme/default/components/   ← DE namespace only
src/Pyz/Yves/ShopUi/Theme/default/components/      ← all namespaces (fallback)
```

Priority order (highest → lowest):
1. `src/Pyz/Yves/**ShopUi{NS}/Theme/` — project + namespace
2. `src/Pyz/Yves/**/ShopUi/Theme/` — project generic
3. `vendor/spryker-shop/**ShopUi{NS}/` — vendor + namespace
4. `vendor/spryker-shop/**/ShopUi/` — vendor generic
