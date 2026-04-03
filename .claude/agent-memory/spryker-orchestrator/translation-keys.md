---
name: Translation keys reference
description: Known Spryker glossary keys for common UI patterns — back buttons, account menu items
type: reference
---

Back button: `general.back.button` -> "Back" (en_US)
Source: `data/import/b2b_common/common/glossary.csv`

Glossary files location:
- `data/import/b2b_common/common/glossary.csv` — B2B-specific keys
- `data/import/common/common/glossary.csv` — common keys

**How to apply:** When adding UI text, always check glossary CSVs for existing translation keys before inventing new ones. Non-existent keys render as the raw key string (e.g., "global.back" displayed literally).
