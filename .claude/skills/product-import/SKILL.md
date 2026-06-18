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

# Product Import Reference

## Dataset Architecture

| Import config | Product data from | Stores |
|---|---|---|
| `local/full_EU.yml` | `common/` | DE, AT |
| `local/full_US.yml` | `common/` | US |
| `local/b2b_full_EU.yml` | `b2b_common/` | DE, AT |

Default: add products to `common/` (covers full_EU + full_US). Only touch `b2b_common/` when explicitly targeting the B2B demo.

---

## File Map

| File | What it controls |
|---|---|
| `common/common/product_abstract.csv` | Core identity, names, descriptions, URLs, SEO, attributes |
| `common/common/product_concrete.csv` | Concrete variants |
| `common/common/product_abstract_approval_status.csv` | Approval state — always `approved` for demo products |
| `common/common/product_shipment_type.csv` | Delivery method per concrete SKU — always `delivery` |
| `common/common/product_stock.csv` | Warehouse stock; warehouse name: `Warehouse1` |
| `common/common/product_image.csv` | Images (abstract + concrete, per locale) |
| `common/common/product_measurement_unit.csv` | Global unit definitions (METR, KILO, etc.) |
| `common/common/product_measurement_base_unit.csv` | Which unit the warehouse tracks per abstract SKU |
| `common/common/product_measurement_sales_unit.csv` | Sales unit options per concrete SKU + conversions |
| `common/common/marketplace/merchant_product.csv` | Which merchant owns this product |
| `common/common/marketplace/product_stock.csv` | Merchant warehouse stock |
| `common/{STORE}/product_abstract_store.csv` | Makes abstract visible in a store |
| `common/{STORE}/product_price.csv` | Prices in each currency |
| `common/{STORE}/product_measurement_sales_unit_store.csv` | Which sales units are active in a store |

---

## Column Reference

### product_abstract.csv

Key columns (1-indexed): `category_key`, `category_product_order` (0), `abstract_sku`, `tax_set_name`, `name.de_DE`, `name.en_US`, `description.de_DE`, `description.en_US`, `url.de_DE`, `url.en_US`, meta_title/keywords/description (DE+EN), then attribute key/value triplets (e.g. `brand,{value},brand,{value},brand,{value}`), then `is_active` last.

Fill all remaining columns with empty strings to match the exact column count — check `len(header)` before appending.

### product_concrete.csv — 23 columns

`abstract_sku, concrete_sku, name.de_DE, name.en_US, description.de_DE, description.en_US, is_searchable.de_DE(1), is_searchable.en_US(1), bundled(0), is_quantity_splittable, attribute_key_1..value_2.en_US [12 empty cols], is_active(1)`

`is_quantity_splittable` = `1` for measurement unit products, `0` for standard.

### product_image.csv

Add 4 rows (de_DE + en_US × abstract + concrete):
```
default,{url},{url},de_DE,{abstract_sku},,0,{slug}-abs-image-0,,,,,
default,{url},{url},en_US,{abstract_sku},,1,{slug}-abs-image-1,,,,,
default,{url},{url},de_DE,,{concrete_sku},0,{slug}-image-0,,,,,
default,{url},{url},en_US,,{concrete_sku},1,{slug}-image-1,,,,,
```

Image URL pattern: `https://spryker.s3.eu-central-1.amazonaws.com/image/{descriptive-name}.webp`

### product_price.csv

Header: `abstract_sku, concrete_sku, price_type, store, currency, value_net, value_gross, price_data.volume_prices`

All values are **integers in cents** (`1035` = 10.35 EUR). Always use `DEFAULT` price type. Add both an abstract-level row (`concrete_sku` empty) and a concrete-level row (`abstract_sku` empty).

| Store | Currencies | Net | Gross |
|---|---|---|---|
| DE | EUR, CHF | required | required |
| AT | EUR, CHF | required | required |
| US | USD | required | leave empty |

---

## Measurement Units

### Rules

- `ITEM` is built-in — **never add it to `measurement_unit.csv`**.
- Sales unit keys must be slug-based, not numeric: `sales_unit_{product-slug}-{unit}` (e.g. `sales_unit_my-product-meter`). Numeric keys cause conflicts.
- Only **one** sales unit per concrete SKU may have `is_default=1`.

### product_measurement_base_unit.csv

Format: `code,abstract_sku` — e.g. `ITEM,MY-PRODUCT-001`

### product_measurement_sales_unit.csv

Header: `sales_unit_key, concrete_sku, code, conversion, precision, is_displayed, is_default`

Example (3m segment cable, ordered by meter):
```
sales_unit_my-product-meter,MY-PRODUCT-001-1,METR,0.33333333,10,1,1
sales_unit_my-product-item,MY-PRODUCT-001-1,ITEM,1,1,1,0
```

### product_measurement_sales_unit_store.csv

Format: `sales_unit_key,store_name` — one row per sales unit per store.

### Unit codes

| Code | Unit | Precision | In measurement_unit.csv? |
|---|---|---|---|
| `ITEM` | Item/piece | — | No — built-in, never add |
| `METR` | Meter | 100 | Yes |
| `CMET` | Centimeter | 10 | Yes |
| `MMET` | Millimeter | 1 | Yes |
| `KILO` | Kilogram | 1000 | Yes |
| `GRAM` | Gram | 1 | Yes |
| `LITR` | Liter | 100 | Yes |
| `BOX` | Box | 1 | Yes |

### Conversion examples

| Scenario | Base unit | Sales unit | Conversion | Precision |
|---|---|---|---|---|
| 3m segments, order by meter | ITEM | METR | `0.33333333` | `10` |
| 1m segments, order by cm | ITEM | CMET | `0.01` | `1` |
| 5m rolls, order by meter | ITEM | METR | `0.2` | `10` |
| Stock by kg, order by kg | KILO | KILO | `1` | `1000` |

---

## Marketplace Files

### merchant_product.csv

Format: `sku,merchant_reference,is_shared` — e.g. `MY-PRODUCT-001,MER000008,1`

Common merchants: `MER000001` (Spryker Systems), `MER000008` (industrial/components).

### marketplace/product_stock.csv

Format: `concrete_sku,name,quantity,is_never_out_of_stock,is_bundle`

Warehouse name pattern: `Spryker {merchant_reference} Warehouse 1`

---

## Working with CSV Files

**Always use Python `csv` module — never shell `printf >>` or `echo >>`** (shell appends introduce blank lines and wrong quoting).

```bash
# Find all files referencing a SKU
grep -rl "MY-PRODUCT-001" data/import/common/

# Show rows for a SKU in a specific file
grep "MY-PRODUCT-001" data/import/common/DE/product_price.csv

# Check last used sales_unit_key
tail -3 data/import/common/common/product_measurement_sales_unit.csv
```

```python
import csv

# Append rows (validates column count)
def append_rows(path, rows):
    with open(path, newline='', encoding='utf-8') as f:
        ncols = len(next(csv.reader(f)))
    assert all(len(r) == ncols for r in rows), "Column count mismatch"
    with open(path, 'a', newline='', encoding='utf-8') as f:
        csv.writer(f, quoting=csv.QUOTE_MINIMAL).writerows(rows)

# Update rows in place
def update_rows(path, match_col, match_val, updates):
    with open(path, newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        fields = reader.fieldnames
        rows = [dict(r, **updates) if r[match_col] == match_val else r for r in reader]
    with open(path, 'w', newline='', encoding='utf-8') as f:
        w = csv.DictWriter(f, fields, quoting=csv.QUOTE_MINIMAL)
        w.writeheader()
        w.writerows(rows)

# Validate column count across all rows
def check_columns(path):
    with open(path, newline='', encoding='utf-8') as f:
        reader = csv.reader(f)
        n = len(next(reader))
        bad = [i+2 for i, r in enumerate(reader) if r and len(r) != n]
    return bad  # line numbers with wrong column count
```

---

## CRITICAL: Import Order Dependency

Measurement-unit importers look up the product **from the database by SKU** at the moment they run. If the product abstract/concrete hasn't been imported yet, you get:

```
Product abstract with SKU "..." was not found during import.
```

A full `full_EU.yml` run handles this automatically (`product-abstract` runs before `product-measurement-*`). **This error only appears when running measurement/packaging importers standalone before the catalog import.**

Fix: run the full config end-to-end, or run the catalog/product import first. Re-running is safe — importers upsert by key.

---

## Import Commands

```bash
vendor/bin/console data:import --config=data/import/local/full_EU.yml      # common, DE/AT
vendor/bin/console data:import --config=data/import/local/full_US.yml      # common, US
vendor/bin/console data:import --config=data/import/local/b2b_full_EU.yml  # b2b_common, DE/AT
```
