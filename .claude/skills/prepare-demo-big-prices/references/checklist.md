# Demo with 5–10M prices: what must be updated

Scenario: 5 products priced €5M–€10M each, qty 1 each, one order placed with Invoice payment.

## Conclusion

Unit prices fit the current `INTEGER` columns, but order-level sums do not.
Make 3 schema overrides (1 is already done) and fix 1 data value. Everything else either fits or is a demo-script choice.

| Value | Cents | Fits `INT` (max 2,147,483,647 ≈ €21.47M)? |
|---|---|---|
| One unit, €10M net → €11.9M gross | 1,190,000,000 | ✅ |
| Order of 5 × €10M net → €59.5M gross | 5,950,000,000 | ❌ |

Any column that stores a sum over **3 or more** such products overflows.

## Must do

### 1. Schema overrides in `src/Demo` (BIGINT + `phpType="int"`)

| Table.column | Holds | Status |
|---|---|---|
| `spy_sales_order_totals.subtotal, order_expense_total, discount_total, grand_total, refund_total, canceled_total, tax_total` | order totals | ✅ done: `src/Demo/Zed/Sales/Persistence/Propel/Schema/spy_sales.schema.xml` |
| `spy_sales_payment.amount` | payment amount = grand total (written for every payment by `SalesPaymentCheckoutDoSaveOrderPlugin`) | ❌ add `src/Demo/Zed/Payment/Persistence/Propel/Schema/spy_sales_payment.schema.xml` |
| `spy_merchant_sales_order_totals.subtotal, order_expense_total, discount_total, grand_total, refund_total, canceled_total, tax_total` | per-merchant order totals | ❌ add `src/Demo/Zed/MerchantSalesOrder/Persistence/Propel/Schema/spy_merchant_sales_order.schema.xml`. Required if 3+ of the products belong to the same merchant. Do it anyway, because it's cheap. |

Pattern:

```xml
<table name="spy_sales_payment">
    <column name="amount" type="BIGINT" phpType="int"/>
</table>
```

Then run `propel:install` (or `propel:schema:copy && propel:model:build && propel:diff && propel:migrate`).

### Why `phpType="int"` is mandatory here

Propel maps `BIGINT` to PHP `string`: setters cast with `(string)` and getters return `string`.
Core mappers pass entity values directly into transfers, for example `SalesOrderTotalsMapper`: `->setGrandTotal($salesOrderTotalsEntity->getGrandTotal())`.
That string then reaches `MoneyBuilder::fromInteger()`, which throws `InvalidAmountArgumentException: Current amount was expected to be int` when the order, totals or payment amount is rendered.

- Core solves this by changing the core code itself. `spryker/sales-order-threshold` 1.14.0 has a plain `BIGINT` and adds `(int)` / `(string)` casts in its mapper and entity manager.
- We can't edit vendor mappers. `phpType="int"` makes Propel cast with `(int)` in the setter, getter and hydrate, so all existing core code keeps receiving `int`.
- This is safe: PHP `int` is 64-bit.

### 2. Data: hard-maximum sales order threshold blocks the order

`hard-maximum-threshold` is `10000000` cents (**€100,000**) in:

- `data/import/common/DE/sales_order_threshold.csv` (EUR, CHF)
- `data/import/common/US/sales_order_threshold.csv` (USD)
- `data/import/b2b_common/DE|US/sales_order_threshold.csv`

The €59.5M order fails at checkout (`SalesOrderThresholdCheckoutPreConditionPlugin`). Choose one:

- **Recommended:** raise it to, for example, `10000000000` (€100M). This needs `spy_sales_order_threshold.threshold` = `BIGINT`, which comes with the upmerge of `spryker/sales-order-threshold` 1.14.0 from master. Until that's upmerged, the max you can set is `2147483647` (€21.47M), which is still too low.
- Or remove the `hard-maximum-threshold` rows for the demo stores.

Keep `b2b_robot/*` unchanged, or check the robot tests if you change it (see the robot import sharing data).

## Demo script rules (no code change if followed)

| Rule | Why |
|---|---|
| Use **Invoice** (`MarketplacePayment01`), not Stripe | `spy_stripe_payment.amount` is `INTEGER`, and the Stripe API caps one charge at 99,999,999 minor units (≈ €1M). Stripe can't be fixed on our side. |
| Qty 1 per product, and use quantity-splittable products (`is_quantity_splittable` = 1, no packaging units) | `spy_sales_order_item` stores **sums** (`gross_price` = `getSumGrossPrice()`, `price_to_pay_aggregation`, `subtotal_aggregation`, `tax_amount_full_aggregation`…). Splittable items are saved as qty-1 rows, so they fit. A non-splittable row with qty ≥ 2 of a €10M product overflows. |
| Unit price ≤ €21,474,835 | Back Office price input limit (`MoneyGui` `MAX_MONEY_INT`, `ProductManagement` `PriceForm::MAX_PRICE_SIZE`) and the `spy_price_product_store` `INTEGER` columns. |
| Don't select a cost-center budget, or use one sized for the order | Demo budgets are €800–€2,500 (`data/import/common/common/budget.csv`). A `block` budget correctly stops the order, and a `warn` budget goes negative. `spy_budget*.amount` is already `BIGINT`. |
| Use a buyer without "Place order up to" limit (`Acme_Buyer`, not `*_With_Limit` if a limit is set in BO) | `PlaceOrderWithAmountUpToPermissionPlugin` denies orders above the configured cent amount. |

## Optional: only if the demo goes beyond "place order"

| Demo step | Column to override |
|---|---|
| Refund / return of the whole order | `spy_refund.amount` (sum of refundable items) |
| Non-splittable products with qty > 1 | `spy_sales_order_item`: `gross_price, net_price, price, price_to_pay_aggregation, subtotal_aggregation, tax_amount, tax_amount_full_aggregation, tax_amount_after_cancellation, discount_amount_aggregation, discount_amount_full_aggregation, expense_price_aggregation, product_option_price_aggregation, refundable_amount, canceled_amount` |

## Checked, no change needed

- `spy_price_product_store`, `spy_price_product_schedule`, `spy_price_product_offer` prices: unit price fits.
- Elasticsearch price fields (`integer`): unit price fits.
- `spy_sales_expense`, `spy_sales_discount.amount`, `spy_sales_order_item_option`: per-item or per-shipment values.
- `spy_sales_order_totals.merchant_commission_total` / `merchant_commission_refunded_total`: demo commissions are 3–7% plus €0.50 fixed, about €6M max, so they fit.
- `spy_sales_merchant_commission.amount`: per item.
- `spy_sales_payment_merchant_payout(_reversal).amount`: already `VARCHAR`.
- `spy_budget.amount`, `spy_budget_consumption.amount`: already `BIGINT`.
- Quote, cart, quote request and Redis/storage data: JSON, no limit.
- PHP arithmetic: 64-bit `int`. JS: below `2^53`.
- MySQL `SUM()` in dashboards and analytics: returns `DECIMAL`.

## Verification

1. Import 5 splittable products at €10M (`price_product.csv`), or set the prices in the Back Office.
2. As `Acme_Buyer` in DE, add the 5 products → checkout → Invoice → place the order.
3. Check:
   - `SELECT grand_total FROM spy_sales_order_totals ORDER BY id_sales_order_totals DESC LIMIT 1;` returns ≈ `5950000000`.
   - `spy_sales_payment.amount` and `spy_merchant_sales_order_totals.grand_total` show the same order-level values.
   - Order detail pages in Yves, Back Office and Merchant Portal render without `InvalidAmountArgumentException`.
