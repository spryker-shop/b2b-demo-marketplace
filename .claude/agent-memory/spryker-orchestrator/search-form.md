# Search Form Component Architecture

## Component Relationship
- `search-form` (Twig + SCSS) — the visible search UI in the header
- `suggest-search` (Twig + TS + SCSS) — the autocomplete/suggestions behavior, embedded inside search-form
- The TS lives in `suggest-search` but controls both the input behavior and overlay toggling
- Clear button logic also lives in `suggest-search.ts` since it has access to `searchInput`

## How suggest-search finds the input
- Via `input-class-name` attribute, which maps to a CSS class on the `<input>` element
- Convention: `js-search-form__input--desktop` (built from `config.jsName ~ '__input--' ~ attributes['data-search-id']`)

## Key integration points
- `wrapper-class-name` / `wrapper-toggle-class-name` — used by suggest-search TS to toggle the search panel
- `open-class-name` / `close-class-name` — triggers for mobile open/close
- `parent-class-name` — used to derive the JS class for the clear button (`js-{parent}__clear`)
- The `searchInput` is found by class name (not selector), set via `input-class-name` attr

## Design tokens used
- Container: `--input-background-default`, `--input-border-default`, `--radius-md`
- Focus: `--input-border-focus`, `--shadows-focus-*`, `--shadows-focus-color`
- Text: `--body-md-*` for typography, `--input-text-value`, `--input-text-placeholder`
- Buttons: `--background-subtle`, `--icon-secondary`, `--radius-sm` (submit), `--radius-full` (clear)
