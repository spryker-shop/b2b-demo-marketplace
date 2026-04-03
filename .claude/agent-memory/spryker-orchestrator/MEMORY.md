# Orchestrator Memory

- [Browser testing](browser-testing.md) — MCP hover limitations, cache-busting pattern
- [Translation keys](translation-keys.md) — Known glossary keys for common UI patterns
- [Search form](search-form.md) — Search component architecture, suggest-search integration

## Build Notes
- Pre-existing build error in `account-menu.scss` (undefined mixin `shop-ui-spr-tabs`). Not related to search-form work.
- Pre-existing stylelint error in `spr-tabs.scss` (duplicate `font-weight`).
- Glossary CSV: append new keys at end of file (Edit tool may reject inline edits on large CSV).
