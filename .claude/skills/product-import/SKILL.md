---
name: product-import
description: >
  Manage demo product data in the Spryker B2B marketplace demoshop import CSVs.
  Use when the user asks to "add a product", "import a product", "update a product",
  "change the price of a product", "edit product data", "how does product import work",
  "explain the import files", "what files do I need to add a product", or any variation
  of creating, updating, or learning about demo catalog import data. Handles simple
  products, products with measurement units, and marketplace-assigned products across
  all stores (DE, AT, US) and both the `common` and `b2b_common` import datasets.
---

# Product Import Skill

This skill manages demo product data across all Spryker import CSV files. It supports
three modes: **creating** a new product, **updating** an existing product, and
**answering questions** about how the import system works.

---

## Step 0 — Identify Mode

Before doing anything, determine what the user wants. Ask if not clear from context.

| What the user says | Mode |
|---|---|
| "add a product", "import a new product", "create product data" | **CREATE** |
| "update a product", "change the price", "fix the description", "add measurement units to an existing product" | **UPDATE** |
| "how does product import work", "what files do I need", "explain the CSV structure", "where is X stored" | **INFO** |

- **INFO mode** → answer directly from the reference sections at the bottom of this skill. No file changes.
- **CREATE mode** → follow Steps 1–8.
- **UPDATE mode** → follow Steps U1–U5 (below), then run Step 7 (validation) and Step 8 (summary).

---

## Dataset Architecture

The demoshop has **two** product-data directories, consumed by **three** import configs.

| Import config | product abstract/concrete from | measurement / special-type files from | Stores |
|---------------|-------------------------------|----------------------------------------|--------|
| `local/full_EU.yml` | `common/` | `common/` | DE, AT |
| `local/full_US.yml` | `common/` | `common/` | US |
| `local/b2b_full_EU.yml` | `b2b_common/` | `b2b_common/` | DE, AT |

**Default: add the product to `common`** (covers full_EU + full_US). Then decide the others:

`b2b_*` configs are self-consistent (abstract + measurement from the same dataset), so they only
need changes if you explicitly target the B2B demo.

---

# CREATE MODE — Steps 1–8

---

## Step 1 — Gather Required Information

Check the conversation for answers already provided. Ask only for what is still missing.

### Core product info (always required)

| Field | Key | Example |
|-------|-----|---------|
| Abstract SKU | `abstract_sku` | `MY-PRODUCT-001` |
| Concrete SKU(s) | `concrete_sku` | `MY-PRODUCT-001-1` (append `-1` to abstract if single variant) |
| Category key | `category_key` | `components_accessories` |
| Tax set | `tax_set_name` | `Standard Tax` |
| Name (DE) | `name.de_DE` | `"Mein Produkt 001"` |
| Name (EN) | `name.en_US` | `"My Product 001"` |
| Description (DE) | `description.de_DE` | Long text |
| Description (EN) | `description.en_US` | Long text |
| URL slug (DE) | `url.de_DE` | `/de/mein-produkt-001` |
| URL slug (EN) | `url.en_US` | `/en/my-product-001` |
| Brand | attribute | `my-brand` (or empty) |

### Pricing (always required)

Prices are stored **in cents** as integers (e.g., 1035 = 10.35 EUR).
Prices are always defined **per base unit** (see measurement units section).

| Store | Currencies | Net / Gross |
|-------|-----------|-------------|
| DE | EUR, CHF | Both required |
| AT | EUR, CHF | Both required |
| US | USD | Net only (gross = empty) |

### Stock

- Warehouse name: `Warehouse1`
- Quantity: ask the user, or default to `100`
- `is_never_out_of_stock`: `0` (default) or `1`

### Image

Image URL format used in this project:
```
https://spryker.s3.eu-central-1.amazonaws.com/image/{descriptive-name}.webp
```
If no URL provided, use placeholder: `https://spryker.s3.eu-central-1.amazonaws.com/image/{abstract-sku-lowercase}.webp`

### Measurement units

Ask: **"Does this product need measurement units (e.g. sold in meters but stocked in items)?"**

If yes, collect:

| Field | Example | Notes |
|-------|---------|-------|
| Base unit code | `ITEM` | Inventory unit. ITEM is built-in — do NOT add to measurement_unit.csv |
| Sales unit 1 code | `METR` | Already in measurement_unit.csv |
| Conversion for sales unit 1 | `0.33333333` | 1 m = 1/3 item → conversion = 0.333… |
| Precision for sales unit 1 | `10` | 10=0.1 steps, 1=integer, 100=0.01 steps |
| Is default (shown first on PDP) | `1` | Only one sales unit per concrete SKU can be default |
| Sales unit 2 code | `ITEM` | |
| Conversion for sales unit 2 | `1` | |

**Conversion formula:** If 1 base unit = N sales units → conversion = 1/N.

### Marketplace assignment

Ask: **"Should this product be assigned to a marketplace merchant?"**
Default for industrial/component products: `MER000008`.

### Dataset scope

Ask: **"Should this product be added to `common` only, or also to `b2b_common`?"**
Default: `common` only.

---

## Step 2 — Inspect Existing Files for Context

**Do not read entire large files.** Use targeted commands only.

```bash
# Count columns in abstract CSV header
python3 -c "
import csv
with open('data/import/common/common/product_abstract.csv') as f:
    h = next(csv.reader(f))
    print(len(h), 'columns:', h)
"

# Get last sales_unit_key to avoid collisions
tail -5 data/import/common/common/product_measurement_sales_unit.csv

# Confirm category key exists
grep -m1 "^{category_key}" data/import/common/common/product_abstract.csv
```

---

## Step 3 — Prepare and Write CSV Rows

### CRITICAL: Always use Python csv module, never shell `printf >>` or `echo >>`

Shell appends introduce blank lines and wrong quoting. Use this pattern for every file:

```python
import csv

def append_to_csv(filepath, new_rows):
    with open(filepath, newline='', encoding='utf-8') as f:
        header = next(csv.reader(f))
        ncols = len(header)
    for i, row in enumerate(new_rows):
        if len(row) != ncols:
            raise ValueError(f"Row {i}: expected {ncols} cols, got {len(row)}: {row[:3]}")
    with open(filepath, 'a', newline='', encoding='utf-8') as f:
        csv.writer(f, quoting=csv.QUOTE_MINIMAL).writerows(new_rows)
    print(f"Appended {len(new_rows)} rows → {filepath}")
```

### 3a. Product Abstract (`common/common/product_abstract.csv`)

Key columns (1-indexed):
1. `category_key`, 2. `category_product_order` (0), 3. `abstract_sku`, 4. `tax_set_name`,
5. `name.de_DE`, 6. `name.en_US`, 7. `description.de_DE`, 8. `description.en_US`,
9. `url.de_DE`, 10. `url.en_US`, 11. `meta_title.de_DE`, 12. `meta_title.en_US`,
13. `meta_keywords.de_DE`, 14. `meta_keywords.en_US`, 15. `meta_description.de_DE`,
16. `meta_description.en_US`, then attribute key/value pairs, then `is_active` last.

Brand attributes appear as triplets at the end: `brand,{value},brand,{value},brand,{value}`.
Fill all remaining columns with empty strings to match the exact column count.

### 3b. Product Concrete (`common/common/product_concrete.csv`) — 23 columns

```
abstract_sku, concrete_sku, name.de_DE, name.en_US, description.de_DE, description.en_US,
is_searchable.de_DE(1), is_searchable.en_US(1), bundled(0), is_quantity_splittable,
attribute_key_1..value_2.en_US [12 empty cols], is_active(1)
```

`is_quantity_splittable` = `1` for measurement unit products, `0` for fixed-unit.

### 3c. Approval Status (`common/common/product_abstract_approval_status.csv`)

Header: `sku,approval_status` (the `sku` column holds the **abstract** SKU).
Row format: `{abstract_sku},approved` — always use `approved` for new demo products.

### 3d. Shipment Type (`common/common/product_shipment_type.csv`)

Format: `concrete_sku,shipment_type_key` — always use `delivery` for physical products.

### 3e. Stock (`common/common/product_stock.csv`)

Format: `concrete_sku,name,quantity,is_never_out_of_stock,is_bundle`

### 3f. Product Image (`common/common/product_image.csv`)

Add 4 rows (de_DE + en_US × abstract + concrete):
```
default,{url},{url},de_DE,{abstract_sku},,0,{slug}-abs-image-0,,,,,
default,{url},{url},en_US,{abstract_sku},,1,{slug}-abs-image-1,,,,,
default,{url},{url},de_DE,,{concrete_sku},0,{slug}-image-0,,,,,
default,{url},{url},en_US,,{concrete_sku},1,{slug}-image-1,,,,,
```

### 3g. Abstract Store (`common/{STORE}/product_abstract_store.csv`)

Format: `abstract_sku,store_name` — add one row per store (DE, AT, US).

### 3h. Prices (`common/{STORE}/product_price.csv`)

Header: `abstract_sku,concrete_sku,price_type,store,currency,value_net,value_gross,price_data.volume_prices`

Add both abstract-level AND concrete-level rows. Always use `DEFAULT` price_type.

**DE and AT** (EUR + CHF, both net and gross required):
```
{abstract_sku},,DEFAULT,DE,EUR,{net},{gross},
{abstract_sku},,DEFAULT,DE,CHF,{net_chf},{gross_chf},
,{concrete_sku},DEFAULT,DE,EUR,{net},{gross},
,{concrete_sku},DEFAULT,DE,CHF,{net_chf},{gross_chf},
```

**US** (USD, net only — leave gross empty):
```
{abstract_sku},,DEFAULT,US,USD,{net_usd},,
,{concrete_sku},DEFAULT,US,USD,{net_usd},,
```

---

## Step 4 — Measurement Unit Files (only if needed)

### 4a. Check if unit code already exists

```bash
grep ",{CODE}," data/import/common/common/product_measurement_unit.csv
```

**ITEM is built-in — never add it to measurement_unit.csv.**

### 4b. Base Unit (`common/common/product_measurement_base_unit.csv`)

Format: `code,abstract_sku` — e.g. `ITEM,MY-PRODUCT-001`

### 4c. Sales Units (`common/common/product_measurement_sales_unit.csv`)

Header: `sales_unit_key,concrete_sku,code,conversion,precision,is_displayed,is_default`

Use slug-based keys: `sales_unit_{product-slug}-{unit}` (e.g. `sales_unit_my-product-meter`).
Do NOT use numeric sequential keys — they cause conflicts.

Only ONE sales unit per concrete SKU may have `is_default=1`.

Example (product sold in 3m segments, ordered by meter):
```
sales_unit_my-product-meter,MY-PRODUCT-001-1,METR,0.33333333,10,1,1
sales_unit_my-product-item,MY-PRODUCT-001-1,ITEM,1,1,1,0
```

### 4d. Sales Unit Store (`common/{STORE}/product_measurement_sales_unit_store.csv`)

Format: `sales_unit_key,store_name` — add one row per sales unit per store.

---

## Step 5 — Marketplace Files (only if merchant-assigned)

### 5a. Merchant Product (`common/common/marketplace/merchant_product.csv`)

Format: `sku,merchant_reference,is_shared` — e.g. `MY-PRODUCT-001,MER000008,1`

Common merchants: `MER000001` (Spryker Systems), `MER000008` (industrial/components).

### 5b. Marketplace Stock (`common/common/marketplace/product_stock.csv`)

Format: `concrete_sku,name,quantity,is_never_out_of_stock,is_bundle`

Warehouse name pattern: `Spryker {merchant_reference} Warehouse 1`

---

# UPDATE MODE — Steps U1–U5

Use when the user wants to change data for a product that already exists in the CSVs.

---

## Step U1 — Identify What to Update

Ask (if not already clear from context):
1. Which product? (abstract SKU or concrete SKU)
2. What field(s) need changing? (price, stock, description, image, measurement units, etc.)
3. Which dataset? (`common`, `b2b_common`, or both)

Map the requested change to the relevant file(s) using the Reference table at the bottom.

---

## Step U2 — Find the Existing Rows

**Never read a full large file.** Locate only the rows for that SKU:

```bash
# Find the product in a specific file
grep "MY-PRODUCT-001" data/import/common/common/product_abstract.csv

# Find price rows for a SKU
grep "MY-PRODUCT-001" data/import/common/DE/product_price.csv

# Find all files that reference this SKU
grep -rl "MY-PRODUCT-001" data/import/common/ 2>/dev/null
```

Show the user the current values before making any changes.

---

## Step U3 — Apply the Update

Use the Python `csv` module to update rows in place. This pattern works for any file:

```python
import csv

def update_csv_rows(filepath, match_col, match_val, updates):
    """Update fields in all rows where row[match_col] == match_val."""
    rows = []
    updated = 0
    with open(filepath, newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        fieldnames = reader.fieldnames
        for row in reader:
            if row.get(match_col) == match_val:
                row.update(updates)
                updated += 1
            rows.append(row)

    with open(filepath, 'w', newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, quoting=csv.QUOTE_MINIMAL)
        writer.writeheader()
        writer.writerows(rows)

    print(f"Updated {updated} row(s) in {filepath}")
    return updated
```

**Example — update price for MY-PRODUCT-001 in DE EUR:**
```python
update_csv_rows(
    'data/import/common/DE/product_price.csv',
    match_col='abstract_sku',
    match_val='MY-PRODUCT-001',
    updates={'value_net': '1200', 'value_gross': '1428'}
)
```

**Example — update stock quantity:**
```python
update_csv_rows(
    'data/import/common/common/product_stock.csv',
    match_col='concrete_sku',
    match_val='MY-PRODUCT-001-1',
    updates={'quantity': '500'}
)
```

**Example — update product name in abstract:**
```python
update_csv_rows(
    'data/import/common/common/product_abstract.csv',
    match_col='abstract_sku',
    match_val='MY-PRODUCT-001',
    updates={'name.en_US': 'New English Name', 'name.de_DE': 'Neuer Deutscher Name'}
)
```

### Updating measurement units on an existing product

If a product has no measurement units and you need to add them:
- Follow Step 4 (measurement unit files) as if creating new — those files only require appending.

If a product already has measurement units and you need to change conversions or precision:
- Use `update_csv_rows` on `product_measurement_sales_unit.csv`, matching on `sales_unit_key`.

```python
update_csv_rows(
    'data/import/common/common/product_measurement_sales_unit.csv',
    match_col='sales_unit_key',
    match_val='sales_unit_my-product-meter',
    updates={'conversion': '0.5', 'precision': '1'}
)
```

### Adding a new concrete variant to an existing abstract

If the abstract already exists but you're adding a new variant (new concrete SKU):
- Append to `product_concrete.csv`, `product_stock.csv`, `product_shipment_type.csv`, `product_price.csv` (concrete-level rows only)
- Add images for the new concrete SKU
- If measurement units: append new sales unit rows + store rows

---

## Step U4 — Before/After Summary

Before writing any changes, show the user what will change:

```
Update preview for MY-PRODUCT-001
────────────────────────────────────
File: common/DE/product_price.csv
  Before: MY-PRODUCT-001,,DEFAULT,DE,EUR,1035,1230,
  After:  MY-PRODUCT-001,,DEFAULT,DE,EUR,1200,1428,

File: common/common/product_stock.csv
  Before: MY-PRODUCT-001-1,Warehouse1,200,0,0
  After:  MY-PRODUCT-001-1,Warehouse1,500,0,0
```

Ask: **"Does this look correct? Shall I apply these changes?"**

---

## Step U5 — Apply and Validate

Apply the changes using `update_csv_rows` from Step U3, then run the validation from Step 7.

---

# SHARED STEPS

---

## Step 7 — Validate All Changes

```python
import csv

base = 'data/import/common'
abstract_sku = '{abstract_sku}'
concrete_sku = '{concrete_sku}'

checks = [
    (f'{base}/common/product_abstract.csv', abstract_sku),
    (f'{base}/common/product_concrete.csv', abstract_sku),
    (f'{base}/common/product_abstract_approval_status.csv', abstract_sku),
    (f'{base}/common/product_shipment_type.csv', concrete_sku),
    (f'{base}/common/product_stock.csv', concrete_sku),
    (f'{base}/common/product_image.csv', abstract_sku),
    (f'{base}/DE/product_abstract_store.csv', abstract_sku),
    (f'{base}/AT/product_abstract_store.csv', abstract_sku),
    (f'{base}/US/product_abstract_store.csv', abstract_sku),
    (f'{base}/DE/product_price.csv', abstract_sku),
    (f'{base}/AT/product_price.csv', abstract_sku),
    (f'{base}/US/product_price.csv', abstract_sku),
]

for filepath, needle in checks:
    with open(filepath, encoding='utf-8') as f:
        content = f.read()
    rows_found = [r for r in content.split('\n') if needle in r]
    status = 'OK' if rows_found else 'MISSING'
    print(f'{status:7} {"/".join(filepath.split("/")[-2:])} ({len(rows_found)} rows)')
```

Also validate column counts in large files:

```python
import csv

for filepath in [
    'data/import/common/common/product_abstract.csv',
    'data/import/common/common/product_concrete.csv',
]:
    with open(filepath, newline='', encoding='utf-8') as f:
        reader = csv.reader(f)
        header = next(reader)
        ncols = len(header)
        bad = [(i+2, len(r), r[0]) for i, r in enumerate(reader) if r and len(r) != ncols]
    if bad:
        for ln, got, sku in bad[-5:]:
            print(f'MISMATCH line {ln}: sku={sku} — got {got} cols, expected {ncols}')
    else:
        print(f'OK {filepath.split("/")[-1]} — all rows have {ncols} cols')
```

---

## Step 8 — Summary

```
Product import summary
──────────────────────
Mode:          CREATE / UPDATE
Abstract SKU:  {abstract_sku}
Concrete SKU:  {concrete_sku}
Dataset:       common (+ b2b_common if requested)
Stores:        DE, AT, US

Files modified:
  ✅ product_abstract.csv
  ✅ product_concrete.csv
  ✅ product_abstract_approval_status.csv
  ✅ product_shipment_type.csv
  ✅ product_stock.csv
  ✅ product_image.csv
  ✅ product_abstract_store.csv (DE, AT, US)
  ✅ product_price.csv (DE, AT, US)
  ✅ product_measurement_base_unit.csv        [measurement units]
  ✅ product_measurement_sales_unit.csv       [measurement units]
  ✅ product_measurement_sales_unit_store.csv [measurement units]
  ✅ marketplace/merchant_product.csv         [marketplace]
  ✅ marketplace/product_stock.csv            [marketplace]
```

---

## Step 9 — Run the Import (and troubleshoot)

The CSVs are data only — they take effect when the importer runs:

```bash
vendor/bin/console data:import --config=data/import/local/full_EU.yml      # common dataset, DE/AT
vendor/bin/console data:import --config=data/import/local/full_US.yml      # common dataset, US
vendor/bin/console data:import --config=data/import/local/b2b_full_EU.yml  # b2b_common dataset
```

(The standard install uses `full_${SPRYKER_CURRENT_REGION}.yml`, resolving to `full_EU` / `full_US`.)

### CRITICAL: import order dependency

The measurement-unit importers (`product-measurement-base-unit`,
`product-measurement-sales-unit`) and packaging/marketplace importers look up the product
**from the database by SKU at the moment they run**. The product abstract/concrete must
already be imported, or you get:

```
Product abstract with SKU "..." was not found during import.
Product concrete with SKU "..." was not found during import.
```

A full `full_EU.yml` run handles this automatically — `product-abstract` (early in the file)
runs before `product-measurement-*` (later). **This error only appears when the steps are run
separately or out of order** (e.g. running the special-product-types / measurement section
standalone before the catalog/product import).

**Fix:** run the catalog/product import first (or just run the whole `full_EU.yml` end to end),
then re-run. Re-running is safe — importers upsert by key. Once the product exists in the DB,
the measurement import succeeds on the next run.

### Verify the import landed (DB check)

Use these read-only queries (via the project DB tooling) to confirm:

```sql
-- Product exists?
SELECT id_product_abstract, sku FROM spy_product_abstract WHERE sku = '{abstract_sku}';
SELECT id_product, sku, fk_product_abstract FROM spy_product WHERE sku = '{concrete_sku}';

-- Measurement base unit linked?
SELECT b.id_product_measurement_base_unit, u.code
FROM spy_product_measurement_base_unit b
JOIN spy_product_abstract a ON a.id_product_abstract = b.fk_product_abstract
JOIN spy_product_measurement_unit u ON u.id_product_measurement_unit = b.fk_product_measurement_unit
WHERE a.sku = '{abstract_sku}';

-- Sales units linked?
SELECT u.code, s.fk_product
FROM spy_product_measurement_sales_unit s
JOIN spy_product p ON p.id_product = s.fk_product
JOIN spy_product_measurement_unit u ON u.id_product_measurement_unit = s.fk_product_measurement_unit
WHERE p.sku = '{concrete_sku}';
```

If the product rows exist but measurement rows are missing → re-run the import (ordering issue,
not a data problem). If the product rows are missing too → the catalog import didn't run or the
abstract row is malformed (check column count and `category_key` validity).

---

# REFERENCE

## Import file map

| File | Scope | What it controls |
|------|-------|-----------------|
| `common/common/product_abstract.csv` | All stores | Core product identity, names, descriptions, URLs, SEO, attributes |
| `common/common/product_concrete.csv` | All stores | Concrete variants (one per colour/size/variant) |
| `common/common/product_abstract_approval_status.csv` | All stores | Approval workflow state |
| `common/common/product_shipment_type.csv` | All stores | Delivery method per concrete SKU |
| `common/common/product_stock.csv` | All stores | Warehouse stock and never-out-of-stock flag |
| `common/common/product_image.csv` | All stores | Product images (abstract + concrete, per locale) |
| `common/common/product_measurement_unit.csv` | All stores | Global unit definitions (METR, KILO, etc.) |
| `common/common/product_measurement_base_unit.csv` | All stores | Which unit the warehouse tracks per abstract SKU |
| `common/common/product_measurement_sales_unit.csv` | All stores | Sales unit options per concrete SKU + conversions |
| `common/common/marketplace/merchant_product.csv` | Marketplace | Which merchant owns this product |
| `common/common/marketplace/product_stock.csv` | Marketplace | Merchant warehouse stock |
| `common/{STORE}/product_abstract_store.csv` | Per store | Makes abstract visible in a store |
| `common/{STORE}/product_price.csv` | Per store | Prices in each currency |
| `common/{STORE}/product_measurement_sales_unit_store.csv` | Per store | Which sales units are active in a store |

## Measurement unit codes in the system

| Code | Unit | Default precision | In measurement_unit.csv? |
|------|------|-------------------|--------------------------|
| `ITEM` | Item/piece | — | No — built-in, never add |
| `METR` | Meter | 100 | Yes |
| `CMET` | Centimeter | 10 | Yes |
| `MMET` | Millimeter | 1 | Yes |
| `KILO` | Kilogram | 1000 | Yes |
| `GRAM` | Gram | 1 | Yes |
| `LITR` | Liter | 100 | Yes |
| `BOX` | Box | 1 | Yes |

## Measurement unit conversion examples

| Scenario | Base unit | Sales unit | Conversion | Precision |
|----------|-----------|-----------|------------|-----------|
| Cable: 3m segments, order by meter | ITEM | METR | `0.33333333` | `10` |
| Cable: 1m segments, order by cm | ITEM | CMET | `0.01` | `1` |
| Rope: 5m rolls, order by meter | ITEM | METR | `0.2` | `10` |
| Fish: stock by kg, order by kg | KILO | KILO | `1` | `1000` |
| Fabric: 0.5m² rolls, order by m² | ITEM | SMET | `0.5` | `100` |

## Import configs

| Config file | Products from | Measurement from | Stores |
|-------------|--------------|------------------|--------|
| `data/import/local/full_EU.yml` | `common/` | `common/` | DE, AT |
| `data/import/local/full_US.yml` | `common/` | `common/` | US |
| `data/import/local/b2b_full_EU.yml` | `b2b_common/` | `b2b_common/` | DE, AT |

## Price format

- All values are **integers in cents**: `1035` = 10.35 EUR
- DE/AT: EUR net + gross required; CHF net + gross required
- US: USD net only; leave gross column empty
- Always add both abstract-level row (abstract_sku filled, concrete_sku empty) AND concrete-level row (abstract_sku empty, concrete_sku filled)

## Quick checklist — CREATE

- [ ] Abstract SKU and concrete SKU(s) decided
- [ ] Category key confirmed (check existing values in product_abstract.csv)
- [ ] Names and descriptions in DE and EN
- [ ] URL slugs in DE and EN (must be unique)
- [ ] Pricing: EUR net+gross, CHF net+gross, USD net
- [ ] Stock quantity confirmed
- [ ] Image URL (or placeholder)
- [ ] Measurement units: base unit code, sales units, conversions, precision, is_default
- [ ] Abstract store entries for all 3 stores
- [ ] Measurement sales unit store entries (all 3 stores)
- [ ] Marketplace merchant assigned if needed
- [ ] All rows written via Python csv module
- [ ] Validation passed (all SKUs found, no column count mismatches)

## Quick checklist — UPDATE

- [ ] Identified which field(s) to change and in which file(s)
- [ ] Read current values with `grep` before modifying
- [ ] Showed before/after preview to user and got confirmation
- [ ] Used `update_csv_rows` (DictReader/DictWriter) — never manual string replacement
- [ ] Validation passed after update
