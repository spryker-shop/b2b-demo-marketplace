# Spryker Demo Data Updates - Decision Flow

## Visual Workflow

```dot
digraph demo_data_flow {
    "Need to update CSV?" [shape=diamond];
    "Source is ODS/Excel?" [shape=diamond];
    "Split ODS to CSV" [shape=box];
    "File in data/import/?" [shape=diamond];
    "Analyze CSV structure" [shape=box];
    "Transform CSV" [shape=box];
    "Verify changes" [shape=box];
    "Import data" [shape=box];
    "Clean up backups" [shape=box];
    "STOP - module data" [shape=box, style=filled, fillcolor=red];
    "Use schema migration" [shape=box];
    "Done" [shape=doublecircle];

    "Need to update CSV?" -> "Source is ODS/Excel?" [label="yes"];
    "Need to update CSV?" -> "Use schema migration" [label="no, schema change"];
    "Source is ODS/Excel?" -> "Split ODS to CSV" [label="yes"];
    "Source is ODS/Excel?" -> "File in data/import/?" [label="no, already CSV"];
    "Split ODS to CSV" -> "File in data/import/?" [label="CSVs created"];
    "File in data/import/?" -> "Analyze CSV structure" [label="yes"];
    "File in data/import/?" -> "STOP - module data" [label="no, in src/Spryker/"];
    "Analyze CSV structure" -> "Transform CSV";
    "Transform CSV" -> "Verify changes";
    "Verify changes" -> "Import data";
    "Import data" -> "Clean up backups";
    "Clean up backups" -> "Done";
}
```

## Decision Points

### Is this a CSV update?
- **YES** → Proceed to next check
- **NO (schema change)** → Use migration files, not CSV tools

### Is source file ODS/Excel format?
- **YES** → Split to CSV first using `splitOdsToCsv`
- **NO (already CSV)** → Proceed directly to file location check

### Is file in data/import/?
- **YES** → Safe to proceed with analysis
- **NO (in src/Spryker/)** → STOP - this is module example data, do not modify

### Do structures match?
- **YES** → Proceed with transformation
- **NO** → ASK user about structure changes before proceeding

### Do row counts suggest data loss?
- **Source >= Target** → Safe to proceed (APPEND or REPLACE as instructed)
- **Source < Target** → ASK user about data deletion before REPLACE mode

### After transformation complete?
- **Always proceed automatically:** Verify → Import → Cleanup
- **Never ask:** "Should I import?" or "Should I cleanup?"
