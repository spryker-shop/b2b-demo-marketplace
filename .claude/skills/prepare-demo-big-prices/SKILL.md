---
name: prepare-demo-big-prices
description: >-
  Use when the user wants to prepare the demoshop for a demo with very large prices (millions per
  product, orders above €21.47M), or asks what breaks when cart or order amounts exceed the 32-bit
  INTEGER limit.
---

# Prepare demo with big prices

Goal: a buyer can add 5 products priced €5M–€10M (qty 1 each) to the cart and place an order with Invoice payment.
Change **only the fields that would fail**. Do not convert every price column.

The full analysis is in `references/checklist.md`. Read it before you start, because it explains the reason for every rule below.

## Core facts (don't re-derive)

- `INTEGER` max = 2,147,483,647 cents ≈ **€21.47M**.
- A unit price of €10M fits. The order total (5 × €10M + 19% tax ≈ €59.5M) does not, so only **order-level sums** overflow.
- Propel maps `BIGINT` to a PHP **string**. Core mappers pass entity values straight into transfers, and `MoneyBuilder::fromInteger()` then throws `InvalidAmountArgumentException`.
  → Every Demo override MUST be `type="BIGINT" phpType="int"`. Propel then casts to `int` in the setter, the getter and when loading from the database.
- Core modules that switch a column to `BIGINT` themselves add `(int)` / `(string)` casts in their own mappers (example: `spryker/sales-order-threshold` 1.14.0). Don't override those columns in `src/Demo`. Wait for the upmerge instead.

## Workflow

### 1. Audit the current state

Columns may already have changed after upmerges, so always re-check:

```bash
docker/sdk cli "vendor/bin/console propel:schema:copy"
.claude/skills/prepare-demo-big-prices/scripts/scan-money-columns.sh
```

Compare the output with the **Must do** table in `references/checklist.md`. A column is done when it shows `BIGINT` together with `phpType="int"`, or `BIGINT` that comes from core. Skip the done ones.

If the checklist and the code disagree, trust the code and update the checklist. Typical example: a core release now ships the column as `BIGINT`.

### 2. Apply schema overrides in `src/Demo`

There is one file per module, at `src/Demo/Zed/<Module>/Persistence/Propel/Schema/<core schema file name>`.
The `namespace` and `package` attributes must match the core schema file. Copy them from `vendor/spryker/<module>/.../Schema/*.schema.xml`.
List only the changed columns, each with just `name`, `type="BIGINT"` and `phpType="int"`. The merger keeps the other attributes.

| Module dir | Schema file | Table.columns |
|---|---|---|
| `Sales` | `spy_sales.schema.xml` | `spy_sales_order_totals`: subtotal, order_expense_total, discount_total, grand_total, refund_total, canceled_total, tax_total |
| `Payment` | `spy_sales_payment.schema.xml` | `spy_sales_payment.amount` |
| `MerchantSalesOrder` | `spy_merchant_sales_order.schema.xml` | `spy_merchant_sales_order_totals`: same 7 columns as the order totals |

Example:

```xml
<?xml version="1.0"?>
<database xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" name="zed" xsi:noNamespaceSchemaLocation="http://static.spryker.com/schema-01.xsd" namespace="Orm\Zed\Payment\Persistence" package="src.Orm.Zed.Payment.Persistence">

    <table name="spy_sales_payment">
        <column name="amount" type="BIGINT" phpType="int"/>
    </table>

</database>
```

The file must end with an empty line.

Don't touch the **Optional** columns from the checklist (refunds, non-splittable item sums) unless the user's demo includes those steps.

### 3. Build and check the diff

```bash
docker/sdk cli "vendor/bin/console propel:schema:copy && vendor/bin/console propel:model:build && vendor/bin/console propel:diff"
```

- File sync into the container can lag. If the merged schema in `src/Orm/Propel/Schema/` doesn't show the change, wait a moment and re-run `propel:schema:copy`.
- Check the base model, for example `src/Orm/Zed/Payment/Persistence/Base/SpySalesPayment.php`. The getter must be `@return int|null`, and both the setter and the hydrate must use `(int)`.
- The new `PropelMigration_*.php` file must contain only the expected `CHANGE ... BIGINT` lines. Don't apply it before you show the diff to the user. After that, run `vendor/bin/console propel:migrate`.

### 4. Fix blocking demo data

`hard-maximum-threshold` in `data/import/common/{DE,US}/sales_order_threshold.csv` and `data/import/b2b_common/{DE,US}/sales_order_threshold.csv` is €100k, so checkout rejects the order.

- If `spy_sales_order_threshold.threshold` is already `BIGINT` (upmerged from master): raise the value to `10000000000` (€100M).
- If it's still `INTEGER`: tell the user. The column can hold at most €21.47M, so the options are to remove the hard-max rows for the demo, or to wait for the upmerge.
- Don't change `data/import/b2b_robot/*` without checking the robot tests that use it.

### 5. Verify with a real order

Follow **Verification** in `references/checklist.md`:

1. Set 5 quantity-splittable products to €10M.
2. Log in as `Acme_Buyer` in DE and place the order with Invoice.
3. Run these SQL checks:
   - `spy_sales_order_totals.grand_total` ≈ `5950000000`
   - `spy_sales_payment.amount` matches
   - `spy_merchant_sales_order_totals.grand_total` matches
4. Open the order detail pages in Yves, the Back Office and the Merchant Portal. They must render without `InvalidAmountArgumentException`.

### 6. Report to the user

- Start with the result: which overrides were added, which data was changed, and whether the verification order passed.
- Repeat the demo script rules from the checklist: Invoice rather than Stripe, qty 1 of splittable products, no blocking budget, a buyer without an amount limit.
- Don't commit or push unless the user asks.
