# Spryker Demo Data Updates - Red Flags & Anti-Patterns

## Common Mistakes

| Mistake | Why Bad | Fix |
|---------|---------|-----|
| **Changing file structure without asking** | **BREAKS system, wrong assumptions** | **ALWAYS ask before adding/removing columns** |
| **Using REPLACE when source has fewer rows** | **DELETES existing data silently** | **Compare row counts, default to APPEND** |
| **Not comparing row counts** | **Miss data deletion warnings** | **Always check row counts in both files** |
| Updating `src/Spryker/*/data/import/*.csv` | Module code, not project data | Only update `data/import/` |
| Using Edit on large CSVs | No backup, easy to corrupt | Use `transformCsv` with backup |
| Forgetting locale columns | Incomplete translations | Update all locale variants |
| Not analyzing first | Don't know column names/structure | Always `analyzeCsvFile` first |
| Skipping backups | Can't recover from mistakes | Use `createBackup: true` |
| Committing backup files to git | Pollutes repository, not needed | Remove backups after verifying changes |
| Stopping after CSV update | Changes not applied to system | Run full workflow: verify, import, cleanup |
| Asking permission between steps | Fragments workflow, causes errors | Execute all steps automatically |
| Listing "next steps" for user | Incomplete workflow execution | YOU execute all steps, not the user |
| Using absolute paths | Tool failures, files in wrong locations | Always use relative paths from project root |
| Leaving split ODS CSV files | Pollutes repository with temporary files | Ask user to delete split files after processing |

## Red Flags: Data Deletion Risk

These thoughts mean you're about to violate the Data Volume Safety Rule:

- "I'll use REPLACE mode to update the data"
- "Source has 10 rows, target has 116, REPLACE is fine"
- "User said 'update' so REPLACE makes sense"
- "REPLACE is cleaner than APPEND"
- "Row counts differ but data looks correct"
- "I'll standardize to the source data"
- "Deleting old data won't matter"
- "REPLACE mode is simpler"

**All of these mean:** STOP. Compare row counts and ASK the user before using REPLACE.

## Red Flags: Structure Changes

These thoughts mean you're about to violate the Structure Change Safety Rule:

- "Source has extra columns, I'll add them to target"
- "Source has es_ES locale, so I'll add it"
- "Target is missing columns, I'll create them"
- "Structures differ but both seem fine"
- "I'll just match the source structure"
- "They probably want the new columns"
- "Adding columns won't hurt"
- "I'll standardize the structure"

**All of these mean:** STOP. Compare structures and ASK the user before proceeding.

## Red Flags: Bypassing MCP Tools

These thoughts mean you're about to violate the Iron Law:

- "I'll just Edit this CSV line"
- "Let me Read the file and use sed/awk"
- "I'll Write a new version"
- "Both files have the data so update both"
- "Edit is simpler for this small change"
- "It's only one field to update"
- "I know the CSV structure already"
- "MCP is overkill for this"

**All of these mean:** STOP. Use MCP CSV tools. No exceptions.

## Red Flags: Fragmenting Workflow

These thoughts mean you're about to break the autonomous workflow:

- "Should I ask if they want me to verify?"
- "Let me check if they want me to import"
- "I'll ask before cleaning up backups"
- "Breaking it into steps is safer"
- "I'll list next steps for the user"
- "Import is a manual step"
- "Let me just update the CSV"

**All of these mean:** STOP. Execute the complete workflow automatically. No permission requests between steps.

## Red Flags: Path Handling

These thoughts mean you're about to use incorrect paths:

- "I'll use the full absolute path to be clear"
- "Let me put the output in /tmp for now"
- "This path outside the project is fine"
- "/Users/username/project is more specific"

**All of these mean:** STOP. Use relative paths from project root only.

## Rationalization Table

| Excuse | Reality |
|--------|---------|
| "I'll use REPLACE to update the data" | FORBIDDEN - REPLACE deletes data, default to APPEND |
| "Source has 10 rows, target 116, REPLACE is fine" | DELETES 106 rows - ALWAYS ask before data deletion |
| "User said 'update' so REPLACE makes sense" | Update is ambiguous - ASK if they want APPEND or REPLACE |
| "Row counts differ but data looks correct" | Different row counts = potential data loss - ASK first |
| "Source has extra columns, I'll add them" | FORBIDDEN - ASK first, structure changes break systems |
| "They probably want the new locales" | NEVER assume - structure changes require explicit approval |
| "Adding columns won't hurt" | Can break imports, frontend, integrations - ALWAYS ask |
| "I'll standardize to source structure" | Don't assume source is correct - ASK about differences |
| "Edit is simpler" | MCP creates backups, validates data, safer |
| "Just a small change" | Small changes corrupt CSVs too |
| "Both files contain it" | Module data is not equal to project data |
| "Read/Edit gives more control" | MCP has filters, transformations, validation |
| "MCP is overkill" | Backup alone makes it worth it |
| "I'll list next steps for the user" | Complete workflow means YOU run all steps, not suggest them |
| "Should I ask before importing?" | Import is mandatory - execute automatically |
| "Let me verify first then ask" | Verification and import are both mandatory, no asking |
| "Import is the user's responsibility" | Import is YOUR responsibility in the workflow |
| "Breaking workflow into steps is safer" | Stopping mid-workflow is MORE error-prone |

## Real-World Impact

### Without This Skill

- Edit CSV → corrupt data, no backup
- REPLACE mode → silently delete 106 rows of data
- Update module data → merge conflicts
- Manual string replacement → miss edge cases
- No validation → broken imports
- Structure changes → break frontend/integrations

### With This Skill

- Automatic backups before every change
- Compare structures and row counts before proceeding
- ASK before structure changes or data deletion
- Default to APPEND (safe) over REPLACE (destructive)
- Filter-based updates (no manual line editing)
- Project data only (no module corruption)
- Preview with `analyzeCsvFile` before commit
