# Spryker Demo Data Updates - Examples & Reference

## Quick Reference

| Task | MCP Tool | Mode | When to Use |
|------|----------|------|-------------|
| Preview CSV structure | `analyzeCsvFile` | N/A | ALWAYS do this first, check row counts |
| Update specific rows | `transformCsv` | UPDATE | Change values in existing rows without adding/removing |
| Add new rows | `transformCsv` | APPEND | Add new products/attributes (DEFAULT, safe) |
| Replace all data | `transformCsv` | REPLACE | ONLY after user confirms data deletion |
| Delete specific rows | `deleteCsvRows` | N/A | Remove specific rows matching criteria |
| Split ODS to CSVs | `splitOdsToCsv` | N/A | Convert spreadsheet imports |

**CRITICAL: Default to APPEND mode. Only use REPLACE after explicit user confirmation.**

## Implementation Pattern: Complete Workflow

**Execute all steps in one autonomous flow. Do not stop between steps to ask for permission.**

**IMPORTANT: All file paths must be relative to project root.**

### Pattern: Split ODS to CSV (If Source is ODS/Excel)

```
Step 0: Split ODS file (OPTIONAL)
  mcp__spryker-project__splitOdsToCsv
    odsFilePath: "data/import/output/import_data.ods"
    outputDirectory: "data/import/output/split_files"

  → Creates one CSV per sheet in output directory
  → Example: sheet "products" becomes "import_data.ods_products.csv"

  After splitting, use the generated CSV files in subsequent steps.
```

### Pattern: Update CSV (Complete Cycle)

```
Step 1: Analyze and Compare Structures AND Row Counts
  mcp__spryker-project__analyzeCsvFile
    filePath: "data/import/common/common/content_banner.csv"
    sampleRows: 5
  → Target: 50 rows, columns: key, title.en_US, title.de_DE

  mcp__spryker-project__analyzeCsvFile
    filePath: "data/import/output/split_files/source_file.csv"
    sampleRows: 5
  → Source: 10 rows, columns: key, title.en_US, title.de_DE, title.es_ES

  COMPARE:
  - Structures differ: source has title.es_ES → STOP and ASK about structure
  - Row counts: source (10) < target (50) → STOP and ASK about APPEND vs REPLACE

Step 2: Transform (only after user confirms approach)
  mcp__spryker-project__transformCsv
    targetPath: "data/import/common/common/content_banner.csv"
    mode: "append"  # DEFAULT to APPEND unless user explicitly says REPLACE
    rowFilters: [{"column": "key", "operator": "equals", "value": "br-3"}]
    defaultValues: {"title.default": "New Title"}
    createBackup: true

Step 3: Verify (Read updated CSV to confirm changes)

Step 4: Import
  docker/sdk cli console data:import content-banner

Step 5: Cleanup Backups
  rm data/import/common/common/*.csv.backup_*

Step 6: Cleanup Split Files (if ODS was used)
  Ask user if split CSV files should be deleted
```

**Steps 3-7 are MANDATORY and must be executed by YOU automatically, not suggested to the user.**

## Row Filter Operators

- `equals`, `not_equals`
- `contains`, `not_contains`
- `starts_with`, `ends_with`
- `in`, `not_in`
- `empty`, `not_empty`
- `greater_than`, `less_than`

## Value Transformations

### String Replacements

```json
valueTransformations: [
  {
    "column": "image_url",
    "find": "http://old-cdn.com",
    "replace": "https://new-cdn.com"
  }
]
```

### Math Operations

```json
valueTransformations: [
  {
    "column": "price",
    "operation": "multiply",
    "value": 1.1
  }
]
```

**Operations:** `add`, `subtract`, `multiply`, `divide`

## Common Patterns

### Update Multiple Locale Columns

```json
defaultValues: {
  "title.default": "English Title",
  "title.en_US": "English Title",
  "title.de_DE": "German Title"
}
```

### Update All Rows Matching Pattern

```json
rowFilters: [
  {"column": "sku", "operator": "starts_with", "value": "ABC-"}
]
```

### Remove Columns Entirely

```json
mode: "update"
columnsToRemove: ["old_column", "deprecated_field"]
```

## Data Import Entity Names

**Finding entity names:** Check `data/import/common/common/*.yml` config files for exact import command names.

**Common entity names:**
- `docker/sdk cli console data:import cms-block`
- `docker/sdk cli console data:import product-abstract`
- `docker/sdk cli console data:import content-banner`
- `docker/sdk cli console data:import product-price`
- `docker/sdk cli console data:import product-stock`
- `docker/sdk cli console data:import category`
