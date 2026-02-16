---
name: spryker-demo-data-updates
description: Use when updating Spryker demo data CSV files, modifying import data rows, or changing product/content/category demo content
allowed-tools: mcp__spryker-project__analyzeCsvFile, mcp__spryker-project__transformCsv, mcp__spryker-project__deleteCsvRows, mcp__spryker-project__splitOdsToCsv, Bash, Read, Write, Edit
---

# Spryker Demo Data Updates

## ⚠️ CRITICAL PREREQUISITE

**THIS SKILL REQUIRES SPRYKER MCP TOOLS TO BE ENABLED.**

Before using this skill, verify that the following MCP tools are available:
- `mcp__spryker-project__analyzeCsvFile`
- `mcp__spryker-project__transformCsv`
- `mcp__spryker-project__deleteCsvRows`
- `mcp__spryker-project__splitOdsToCsv`

**If these tools are not available, this skill cannot be used.** Check your MCP server configuration or notify the user that Spryker MCP tools are required.

---

## Overview

**ALWAYS use Spryker MCP CSV tools for demo data changes.** NEVER use Read/Edit/Write on CSV files.

**Core principle:** MCP tools are the ONLY way to update CSV files in Spryker projects.

**If MCP tools are not available:** Inform the user immediately that this skill requires Spryker MCP tools to be enabled in their environment. Do not proceed with manual CSV editing as a fallback.

## The Iron Law

```
For complex CSV files (3+ columns, product data, content banners, categories):
  NEVER use Edit or Write tools.
  ALWAYS use MCP transformCsv, analyzeCsvFile, or deleteCsvRows.

For simple CSV files (1-2 columns, glossaries, simple key-value pairs):
  Edit or Write are acceptable.
```

**Complex CSV examples:**
- Product data (abstract, concrete, images, prices)
- Content banners (multiple locale columns)
- Categories, CMS pages, navigation
- Any file with 3+ columns or locale variants

**Simple CSV examples:**
- Glossary files (key, translation)
- Simple configuration files
- Basic key-value mappings

**If unsure, use MCP tools.** They work for both simple and complex files, create backups, and validate data.

## File Path Requirements

**CRITICAL:** All paths must be relative to project root (e.g., `data/import/common/common/file.csv`).

Never use absolute paths or paths outside the project directory.

## Safety Checkpoints (ASK BEFORE PROCEEDING)

**You MUST compare structures AND row counts before transformation. STOP and ASK if differences detected.**

### Checkpoint 1: Structure Comparison

**BEFORE transforming, compare file structures:**

1. Analyze both source and target files with `analyzeCsvFile`
2. Compare columns (names, count, order)
3. Identify ANY structural differences
4. **If structures differ → STOP and ASK the user**

**Structure changes include:**
- Adding/removing columns (e.g., new locale columns like es_ES, fr_FR)
- Changing column order or names
- Changing data types

**How to ask:**
```
I've analyzed both files and found structural differences:

TARGET file: columns A, B, C
SOURCE file: columns A, B, C, D, E

The source has 2 additional columns that target doesn't have.

Would you like me to:
1. Add the new columns to target file
2. Keep only existing columns (drop extras from import)
3. Something else?
```

**NEVER assume structure changes are desired.** They affect database schema, frontend rendering, and integrations.

### Checkpoint 2: Row Count Comparison

**BEFORE choosing mode, compare row counts:**

1. Check row count in target file (current data)
2. Check row count in source file (import data)
3. Compare the counts
4. **If source has FEWER rows than target → STOP and ASK (data deletion risk)**

**APPEND vs REPLACE decision:**

- **APPEND mode (default):** Adds new rows, non-destructive, safe
  - Use when: Adding new products, attributes, content

- **REPLACE mode (destructive):** Deletes ALL existing rows, replaces with source
  - Use ONLY when: User explicitly confirms data deletion

**How to ask:**
```
I've analyzed both files:

TARGET file (current): 116 rows
SOURCE file (import): 10 rows

Using REPLACE mode would DELETE 106 existing rows.

Would you like me to:
1. APPEND the 10 new rows (total: 126 rows)
2. REPLACE all 116 rows with these 10 (DELETE 106 rows)
3. Something else?
```

**Default to APPEND mode when in doubt.** NEVER delete data without explicit approval.

## The Autonomous Workflow Rule

**Once you begin a CSV update, execute ALL steps automatically without asking permission between each step.**

The complete workflow is:
0. Split ODS to CSV (if source is ODS/Excel format) - OPTIONAL
1. Analyze CSV structure (both source and target)
2. Compare structures and row counts (ASK if differences found)
3. Transform CSV with MCP tools (after approval)
4. Verify changes (Read updated CSV)
5. Import data (run data:import command)
6. Clean up backups (remove backup files)
7. Clean up split files (if ODS was split, ask user about deletion)

**WRONG Approach:**
- Update CSV
- "Would you like me to verify the changes?"
- "Shall I clean up backups?"

**CORRECT Approach:**
- Update CSV, verify changes, import data, clean up backups (all automatically)
- Report final status to user

**ONLY interrupt the workflow if:**
- Structures differ (Checkpoint 1)
- Row counts suggest data loss (Checkpoint 2)
- Import fails (error in CSV format)
- Verification shows unexpected results

**DO NOT interrupt for:**
- Asking permission for next mandatory step
- Confirming successful operations
- Standard workflow progression

## MANDATORY: Complete Import Workflow

**DO NOT consider the task complete until ALL these steps are executed:**

### Step 0: Split ODS to CSV (OPTIONAL - If Source is ODS/Excel)

If the source data is in ODS or Excel format, split it into CSV files first:

```
mcp__spryker-project__splitOdsToCsv
  odsFilePath: "data/import/output/import_data.ods"
  outputDirectory: "data/import/output/split_files"
```

**IMPORTANT:** Use relative paths from project root. The tool will create one CSV file per sheet in the output directory.

After splitting, proceed with the remaining steps using the generated CSV files.

### Step 1: Analyze and Compare

Analyze BOTH source and target files:

```
mcp__spryker-project__analyzeCsvFile
  filePath: "data/import/common/common/content_banner.csv"
  sampleRows: 5

mcp__spryker-project__analyzeCsvFile
  filePath: "data/import/output/split_files/source_file.csv"
  sampleRows: 5
```

**Required checks:**
- File is in `data/import/` (NOT `src/Spryker/`)
- Understand column structure
- **COMPARE source and target structures** → If different, STOP and ASK
- **COMPARE source and target row counts** → If source < target, STOP and ASK

### Step 2: Transform

Update CSV with MCP tools (only after checkpoints pass):

```
mcp__spryker-project__transformCsv
  targetPath: "data/import/common/common/content_banner.csv"
  mode: "append"  # DEFAULT to APPEND unless user confirms REPLACE
  rowFilters: [{"column": "key", "operator": "equals", "value": "br-3"}]
  defaultValues: {"title.default": "New Title"}
  createBackup: true
```

### Step 3: Verify

Read the updated CSV to confirm changes are correct.

### Step 4: Import Data

Run data import to apply changes:

```bash
docker/sdk cli console data:import <entity-name>
```

**Finding entity names:** Check `data/import/common/common/*.yml` config files.

### Step 5: Clean Up Backups

```bash
rm data/import/common/common/*.csv.backup_*
```

### Step 6: Clean Up Split Files (If ODS Was Used)

If you used `splitOdsToCsv`, ask the user if they want to delete the split CSV files after import completes.

**CRITICAL:** Steps 3-7 are NOT optional "next steps" for the user - YOU must execute them automatically.

## When to Use

**PREREQUISITE:** Verify that Spryker MCP tools are available before using this skill.

Use this skill when:
- Spryker MCP CSV tools are enabled and available
- Updating values in CSV files under `data/import/`
- Changing product data, content banners, categories, prices
- Modifying demo data for development or testing
- Need to analyze CSV structure before changes

**Don't use for:**
- Environments where MCP tools are not available (inform user of requirement)
- Schema changes (use migration files)
- Data import configuration (use YAML configs)
- Module example data in `src/Spryker/*/data/import/` (don't touch)

## File Path Rules

**ONLY update files in these locations:**
- ✅ ALLOWED: `data/import/common/common/*.csv` - Project demo data
- ✅ ALLOWED: `data/import/common/{store}/*.csv` - Store-specific data
- ✅ ALLOWED: `data/import/{environment}/*.csv` - Environment-specific data

**NEVER update these:**
- ❌ FORBIDDEN: `src/Spryker/*/data/import/*.csv` - Module example data
- ❌ FORBIDDEN: `src/SprykerShop/*/data/import/*.csv` - Module example data
- ❌ FORBIDDEN: `vendor/spryker*/data/import/*.csv` - Vendor code

**Why:** Files in `src/Spryker/` are module code, not project configuration. Updating them creates merge conflicts and breaks module integrity.

---

## Additional Resources

**For detailed examples and patterns:** See `examples.md`
- Quick reference table for all MCP tools
- Complete workflow implementation patterns
- Row filter operators
- Value transformation examples
- Common usage patterns

**To avoid common mistakes:** See `red-flags.md`
- Common mistakes table
- Red flags for each safety checkpoint
- Rationalization table (excuses vs reality)
- Real-world impact examples

**For visual workflow:** See `decision-flow.md`
- Graphviz flowchart showing complete decision tree
- Decision points and their outcomes
