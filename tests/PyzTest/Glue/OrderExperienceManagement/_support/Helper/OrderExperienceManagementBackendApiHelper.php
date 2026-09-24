<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement\Helper;

use ArrayObject;
use Codeception\Module;
use Generated\Shared\DataBuilder\MerchantProfileBuilder;
use Generated\Shared\DataBuilder\QuoteBuilder;
use Generated\Shared\Transfer\BudgetTransfer;
use Generated\Shared\Transfer\CostCenterTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\OrderItemFilterTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PriceProductOfferTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductOfferStockTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\SalesOrderThresholdLocalizedMessageTransfer;
use Generated\Shared\Transfer\SalesOrderThresholdTransfer;
use Generated\Shared\Transfer\SalesOrderThresholdTypeTransfer;
use Generated\Shared\Transfer\SalesOrderThresholdValueTransfer;
use Generated\Shared\Transfer\SaveOrderTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\StockTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use PDO;
use Propel\Runtime\Propel;
use Spryker\Shared\DummyMarketplacePayment\DummyMarketplacePaymentConfig;
use Spryker\Shared\SalesOrderThreshold\SalesOrderThresholdConfig;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeatureTest\Shared\PurchasingControl\Helper\PurchasingControlHelper;
use SprykerTest\Shared\Customer\Helper\CustomerDataHelper;
use SprykerTest\Shared\PriceProduct\Helper\PriceProductDataHelper;
use SprykerTest\Shared\PriceProductOffer\Helper\PriceProductOfferHelper;
use SprykerTest\Shared\Product\Helper\ProductDataHelper;
use SprykerTest\Shared\ProductOfferStock\Helper\ProductOfferStockDataHelper;
use SprykerTest\Shared\Sales\Helper\SalesDataHelper;
use SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper;
use SprykerTest\Shared\Stock\Helper\StockDataHelper;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use SprykerTest\Zed\Merchant\Helper\MerchantHelper;
use SprykerTest\Zed\Oms\Helper\OmsHelper;
use SprykerTest\Zed\ProductOffer\Helper\ProductOfferHelper;
use SprykerTest\Zed\SalesOrderThreshold\Helper\SalesOrderThresholdHelper;

/**
 * Route building and fixture arrangement for the Orders resource of the OrderExperienceManagement
 * Backend API.
 *
 * One module rather than one per concern, because Codeception merges every enabled module into a
 * single actor and a method name defined in two of them would collide there.
 *
 * Routes are returned as paths with a leading slash, which is what
 * {@see \SprykerTest\ApiPlatform\Test\AbstractApiTestCase::handleApiRequest()} resolves against the
 * suite's base URL.
 */
class OrderExperienceManagementBackendApiHelper extends Module
{
    use LocatorHelperTrait;

    /**
     * @var array<string, string>
     */
    protected array $placedOrderReferences = [];

    /**
     * Budget and cost-center rows this suite created, removed at suite end for the same reason the
     * placed orders are — see {@see static::cleanupPlacedOrder()}. `CheckoutFacade::placeOrder()`
     * commits mid-request, and that commit takes everything already open on the connection with it,
     * these fixture rows included, so the suite's rollback no longer owns them.
     *
     * @var array<int, int>
     */
    protected array $budgetIds = [];

    /**
     * @var array<int, int>
     */
    protected array $costCenterIds = [];

    public const string RESOURCE_ORDERS = 'orders';

    protected const string QUERY_PARAM_FILTER = 'filter';

    protected const string FILTER_KEY_PREFIX = 'orders.';

    /**
     * JSON:API / API Platform parameters that are not filters and therefore never enter the
     * `filter[]` bag — mirrors the reserved list the framework itself hoists in JsonApiProvider.
     */
    protected const array RESERVED_QUERY_PARAMS = [
        'filter',
        'sort',
        'page',
        'perPage',
        'itemsPerPage',
        'pagination',
        'partial',
        'include',
        'fields',
    ];

    /**
     * The suite rolls its writes back, but the database it runs against is the shared development
     * one and already holds demo orders — so every assertion has to isolate the rows this method
     * created. A unique customer reference per test is the cheapest way to do that, since
     * `customerReference` is a first-class filter on the collection endpoint.
     */
    protected const string CUSTOMER_REFERENCE_PREFIX = 'oem-backend-api';

    /**
     * @var string
     */
    protected const OMS_PROCESS_NAME = 'Test01';

    /**
     * The demo carrier and method name the project's own checkout fixtures use, so the method this
     * suite creates looks like the one a real order would name.
     */
    protected const string SHIPMENT_CARRIER_NAME = 'Spryker Dummy Shipment';

    protected const string SHIPMENT_METHOD_NAME = 'Standard';

    /**
     * `paymentMethodKey`, not the display name ("Invoice"): the resolver matches on the key.
     *
     * This shop runs `spryker/dummy-marketplace-payment`, not plain `spryker/dummy-payment`, so the
     * key is `dummyMarketplacePaymentInvoice` — it also has to match a
     * `SalesConstants::PAYMENT_METHOD_STATEMACHINE_MAPPING` entry in `config/Shared/config_default.php`
     * or `OrderStateMachineResolver::resolve()` rejects the order with "You need to provide at least
     * one state machine process for given method!".
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePaymentResolver::findAvailablePaymentMethod()
     */
    protected const string PAYMENT_METHOD_KEY = DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE;

    protected const string CURRENCY_CODE = 'EUR';

    /**
     * Comfortably above any order this suite places, so the happy path turns on the budget being
     * RESOLVED rather than on how `BudgetCheckoutValidator` handles an exceeded one — that is
     * PurchasingControl's own test's subject, not this suite's.
     */
    protected const int BUDGET_AMOUNT = 100000000;

    /**
     * @uses \SprykerFeature\Shared\PurchasingControl\PurchasingControlConfig::ENFORCEMENT_RULE_BLOCK
     */
    protected const string BUDGET_ENFORCEMENT_RULE = 'block';

    protected const string ISO2_CODE = 'DE';

    /**
     * Net/gross for the catalogue price of the orderable product, in cents. Submitting this same
     * gross amount as the line's `unitPrice` keeps the order off the price-override path, so the
     * happy path resolves to exactly this price rather than an incidentally different one.
     *
     * The amount is chosen to land inside the DE/EUR sales-order-threshold window rather than
     * arbitrarily: the store has a hard minimum of €40 (below it `isPlaceableOrder()` refuses the
     * order outright), a hard maximum of €3000, and a soft minimum of €1000 that attaches a fee
     * expense. €1500 clears the hard minimum, stays under the hard maximum, and is above the soft
     * minimum so no threshold fee lands in `expenses` and muddies the totals assertions.
     *
     * @see spy_sales_order_threshold
     */
    protected const int PRODUCT_NET_AMOUNT = 126050;

    protected const int PRODUCT_GROSS_AMOUNT = 150000;

    /**
     * Priced below whatever hard-minimum threshold {@see haveHardMinimumSalesOrderThreshold()}
     * seeds for this test, so `isPlaceableOrder()` rejects the order via the Glossary-backed
     * hard-threshold pre-condition.
     */
    protected const int PRODUCT_BELOW_HARD_MINIMUM_NET_AMOUNT = 2000;

    protected const int PRODUCT_BELOW_HARD_MINIMUM_GROSS_AMOUNT = 2000;

    /**
     * Comfortably above {@see PRODUCT_BELOW_HARD_MINIMUM_GROSS_AMOUNT}: the strategy compares the
     * quote's item subtotal against this value, so it only needs to clear that one item's price.
     */
    protected const int HARD_MINIMUM_THRESHOLD_VALUE = 4000;

    protected const string HARD_MINIMUM_THRESHOLD_MESSAGE_EN_US = 'The order value is below the minimum required amount.';

    protected const string HARD_MINIMUM_THRESHOLD_MESSAGE_DE_DE = 'Der Bestellwert liegt unter dem erforderlichen Mindestbetrag.';

    /**
     * A reference no fixture will collide with, for the not-found cases.
     */
    public function getUnknownCustomerReference(): string
    {
        return sprintf('%s-unknown', static::CUSTOMER_REFERENCE_PREFIX);
    }

    /**
     * @return non-empty-string
     */
    public function getOrderUrl(string $orderReference): string
    {
        return sprintf('/%s/%s', static::RESOURCE_ORDERS, $orderReference);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getOrderCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_ORDERS) . $this->formatQuery($this->wrapFilters($query));
    }

    /**
     * Filters travel as `filter[orders.<field>]`; `sort` and `page` are JSON:API parameters in their
     * own right and stay outside the bag. Call sites pass plain field names and this wraps them, so
     * the request that actually goes over the wire still carries the real bracket syntax.
     *
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    protected function wrapFilters(array $query): array
    {
        $wrapped = [];

        foreach ($query as $key => $value) {
            if (in_array($key, static::RESERVED_QUERY_PARAMS, true)) {
                $wrapped[$key] = $value;

                continue;
            }

            $wrapped[static::QUERY_PARAM_FILTER][static::FILTER_KEY_PREFIX . $key] = $value;
        }

        return $wrapped;
    }

    /**
     * Places one order for a REAL, persisted customer.
     *
     * The customer has to exist as a `spy_customer` row, not merely be referenced by a well-formed
     * string: {@see \Spryker\Zed\Sales\Persistence\SalesRepository::setMissingCustomer()} nulls
     * `customerReference` on the way out whenever the order's customer relation does not resolve, so
     * an order pointed at a non-existent customer reads back with no customer reference at all —
     * which looks exactly like a mapping bug in the API.
     *
     * `idCustomer` is part of the override because
     * {@see \Spryker\Zed\Sales\Business\Model\Order\SalesOrderSaver::hydrateSalesOrderCustomer()}
     * derives the order's `fk_customer` from the quote's customer, and that FK is the relation
     * `setMissingCustomer()` checks.
     *
     * The OMS process is configured on every call rather than once, because
     * {@see OmsHelper::configureTestStateMachine()} both points the OMS config at the Oms module's
     * test state machine directory and clears the Propel persistence-manager cache — a per-call
     * cost that keeps the fixture usable from any test method regardless of what ran before it.
     *
     * @param array<string, mixed> $override
     */
    public function haveOrderForCustomer(CustomerTransfer $customerTransfer, array $override = []): SaveOrderTransfer
    {
        $this->getOmsHelper()->configureTestStateMachine([static::OMS_PROCESS_NAME]);

        return $this->getSalesDataHelper()->haveOrder(
            $override + [
                OrderTransfer::CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
                CustomerTransfer::ID_CUSTOMER => $customerTransfer->getIdCustomerOrFail(),
            ],
            static::OMS_PROCESS_NAME,
        );
    }

    /**
     * One fresh customer with a single order — the arrangement for every item-endpoint case.
     *
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: \Generated\Shared\Transfer\SaveOrderTransfer}
     */
    public function haveCustomerWithOrder(): array
    {
        $customerTransfer = $this->haveOrderingCustomer();

        return [$customerTransfer, $this->haveOrderForCustomer($customerTransfer)];
    }

    /**
     * One fresh customer with two orders, for the collection, sorting and pagination cases. A
     * customer of this test's own making is what isolates its rows: the database it runs against is
     * the shared development one and already holds demo orders.
     *
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: \Generated\Shared\Transfer\SaveOrderTransfer, 2: \Generated\Shared\Transfer\SaveOrderTransfer}
     */
    public function haveCustomerWithTwoOrders(): array
    {
        $customerTransfer = $this->haveOrderingCustomer();

        return [
            $customerTransfer,
            $this->haveOrderForCustomer($customerTransfer),
            $this->haveOrderForCustomer($customerTransfer),
        ];
    }

    /**
     * One fresh customer with a single order carrying TWO line items.
     *
     * `haveOrder()` builds its quote with one `withItem()` call, so the standard fixture cannot
     * express any case that turns on lines differing from each other — firing an event on a subset,
     * or the mixed-state order that distinguishes D6's two readings of `orderItemUuids`. The quote is
     * therefore assembled here, mirroring
     * {@see \SprykerTest\Shared\Sales\Helper\SalesDataHelper::createQuoteTransfer()} with a second
     * item, and placed through `haveOrderFromQuote()`.
     *
     * Callers should assert the resulting order really has two lines; a builder that stopped
     * appending would otherwise silently downgrade those tests to the single-item case.
     *
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: \Generated\Shared\Transfer\SaveOrderTransfer}
     */
    public function haveCustomerWithTwoItemOrder(): array
    {
        $this->getOmsHelper()->configureTestStateMachine([static::OMS_PROCESS_NAME]);

        $customerTransfer = $this->haveOrderingCustomer();

        $override = [
            OrderTransfer::CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
            CustomerTransfer::ID_CUSTOMER => $customerTransfer->getIdCustomerOrFail(),
        ];

        $quoteTransfer = (new QuoteBuilder($override))
            ->withStore($override)
            ->withItem($override)
            ->withAnotherItem($override)
            ->withCustomer($override)
            ->withTotals()
            ->withShippingAddress()
            ->withBillingAddress()
            ->withCurrency()
            ->build();

        return [
            $customerTransfer,
            $this->getSalesDataHelper()->haveOrderFromQuote($quoteTransfer, static::OMS_PROCESS_NAME),
        ];
    }

    /**
     * Points every item of the given order at the named OMS state, creating the state row if the
     * fixtures do not carry it.
     *
     * Writes the state directly instead of firing OMS events: the states a test needs to tell apart
     * are not necessarily reachable from the test process's initial state, and what is under test is
     * the FILTER, not the transitions that lead to a state.
     *
     * @param array<int, string> $itemUuids Limits the change to these lines; every line when empty.
     */
    public function setOrderItemStates(string $orderReference, string $stateName, array $itemUuids = []): void
    {
        $omsStateEntity = SpyOmsOrderItemStateQuery::create()
            ->filterByName($stateName)
            ->findOneOrCreate();
        $omsStateEntity->save();

        $salesOrderItemQuery = SpySalesOrderItemQuery::create()
            ->useOrderQuery()
                ->filterByOrderReference($orderReference)
            ->endUse();

        if ($itemUuids !== []) {
            $salesOrderItemQuery->filterByUuid_In($itemUuids);
        }

        foreach ($salesOrderItemQuery->find() as $salesOrderItemEntity) {
            $salesOrderItemEntity->setFkOmsOrderItemState($omsStateEntity->getIdOmsOrderItemState());
            $salesOrderItemEntity->save();
        }
    }

    /**
     * The events the PLATFORM considers manual for this order's items, as a union across them.
     *
     * Read through `OmsFacade::getOrderItemManualEvents()` — deliberately a DIFFERENT source from the
     * one the API uses. That method returns `manual` OR `on-enter` events, so it is a strict superset
     * of what `availableEvents` may report. A test asserting containment therefore checks the API
     * against the platform rather than against a copy of the API's own logic, which would prove
     * nothing.
     *
     * @return array<int, string>
     */
    public function getPlatformManualEventsForOrder(string $orderReference): array
    {
        $manualEventsByOrderItemId = $this->getLocator()->oms()->facade()->getOrderItemManualEvents(
            (new OrderItemFilterTransfer())->addOrderReference($orderReference),
        );

        $eventNames = [];

        foreach ($manualEventsByOrderItemId as $itemEventNames) {
            $eventNames = array_merge($eventNames, $itemEventNames);
        }

        return array_values(array_unique($eventNames));
    }

    /**
     * A persisted customer to place orders for.
     */
    public function haveOrderingCustomer(): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([
            CustomerTransfer::EMAIL => uniqid('oem.backend.api.', true) . '@spryker.local',
        ]);
    }

    /**
     * A product an order can actually be placed for: priced in the current store's default price
     * type and in stock there.
     *
     * All three parts are required by something different on the placement path — the catalogue
     * price by `OrderIntakePriceResolver`, the stock by the checkout availability pre-condition, and
     * the product itself by `OrderIntakeItemExpander` — and each one missing fails in a different
     * place, so they are arranged together rather than left to individual tests.
     *
     * Ported from {@see \PyzTest\Glue\Checkout\CheckoutApiTester::haveProductWithStock()}, which
     * is the project's existing recipe for a placeable line.
     *
     * The item also needs a merchant offer: this shop runs `spryker/dummy-marketplace-payment`, whose
     * {@see \Spryker\Zed\DummyMarketplacePayment\Business\Filter\PaymentMethodFilter} only offers
     * `PAYMENT_METHOD_KEY` when every line in the quote carries a `merchantReference` — an
     * operator-sold (non-marketplace) line filters the method out and order creation then fails
     * payment resolution instead of placing the order.
     *
     * @return array{0: \Generated\Shared\Transfer\ProductConcreteTransfer, 1: string} The product and
     * the merchant reference fulfilling it.
     */
    public function haveOrderableProduct(): array
    {
        $productConcreteTransfer = $this->getProductDataHelper()->haveFullProduct();

        $this->getStockDataHelper()->haveProductInStockForStore($this->getCurrentStore(), [
            StockProductTransfer::SKU => $productConcreteTransfer->getSkuOrFail(),
            StockProductTransfer::IS_NEVER_OUT_OF_STOCK => true,
        ]);

        $priceProductTransfer = $this->getPriceProductDataHelper()->havePriceProduct([
            PriceProductTransfer::SKU_PRODUCT_ABSTRACT => $productConcreteTransfer->getAbstractSku(),
            PriceProductTransfer::SKU_PRODUCT => $productConcreteTransfer->getSkuOrFail(),
            PriceProductTransfer::ID_PRODUCT => $productConcreteTransfer->getIdProductConcreteOrFail(),
            PriceProductTransfer::PRICE_TYPE_NAME => 'DEFAULT',
            PriceProductTransfer::MONEY_VALUE => [
                MoneyValueTransfer::NET_AMOUNT => static::PRODUCT_NET_AMOUNT,
                MoneyValueTransfer::GROSS_AMOUNT => static::PRODUCT_GROSS_AMOUNT,
            ],
        ]);

        $merchantReference = $this->haveMerchantOfferForProduct($productConcreteTransfer, $priceProductTransfer);

        return [$productConcreteTransfer, $merchantReference];
    }

    /**
     * A merchant with an active, stocked, priced offer for the given product — what
     * `PaymentMethodFilter` needs to see on the line for `PAYMENT_METHOD_KEY` to stay available.
     *
     * Ported from {@see \PyzTest\Glue\Checkout\CheckoutApiTester::createProductOfferWithStock()}.
     *
     * This project registers `MerchantProfileMerchantPostCreatePlugin` as a merchant post-create
     * plugin, which requires `MerchantTransfer::merchantProfile` to be set — `MerchantHelper::
     * haveMerchant()`'s own default fixture leaves it unset, so it has to be seeded explicitly here.
     *
     * `$priceProductTransfer` is the one {@see haveOrderableProduct()} already created for this SKU:
     * `PriceProductOfferHelper::havePriceProductOffer()` creates its own base `spy_price_product` row
     * whenever `fkPriceProductStore` is left unset, and a second row for the same SKU/price type
     * collides with the one already there.
     */
    protected function haveMerchantOfferForProduct(
        ProductConcreteTransfer $productConcreteTransfer,
        PriceProductTransfer $priceProductTransfer
    ): string {
        $merchantTransfer = $this->getMerchantHelper()->haveMerchant([
            MerchantTransfer::MERCHANT_PROFILE => (new MerchantProfileBuilder())->build(),
        ]);
        $storeTransfer = $this->getCurrentStore();

        $productOfferTransfer = $this->getProductOfferHelper()->haveProductOffer([
            ProductOfferTransfer::CONCRETE_SKU => $productConcreteTransfer->getSkuOrFail(),
            ProductOfferTransfer::ID_PRODUCT_CONCRETE => $productConcreteTransfer->getIdProductConcreteOrFail(),
            ProductOfferTransfer::STORES => new ArrayObject([$storeTransfer]),
            ProductOfferTransfer::MERCHANT_REFERENCE => $merchantTransfer->getMerchantReferenceOrFail(),
        ]);

        $productOfferStockTransfer = $this->getProductOfferStockDataHelper()->haveProductOfferStock([
            ProductOfferStockTransfer::ID_PRODUCT_OFFER => $productOfferTransfer->getIdProductOfferOrFail(),
            ProductOfferStockTransfer::QUANTITY => 1,
            ProductOfferStockTransfer::IS_NEVER_OUT_OF_STOCK => true,
            ProductOfferStockTransfer::STOCK => [
                StockTransfer::STORE_RELATION => [
                    StoreRelationTransfer::ID_STORES => [$storeTransfer->getIdStoreOrFail()],
                ],
            ],
        ]);

        $this->getStockDataHelper()->updateStock($productOfferStockTransfer->getStockOrFail()->setIsActive(true));

        $this->getPriceProductOfferHelper()->havePriceProductOffer([
            PriceProductOfferTransfer::FK_PRODUCT_OFFER => $productOfferTransfer->getIdProductOfferOrFail(),
            PriceProductOfferTransfer::FK_PRICE_PRODUCT_STORE => $priceProductTransfer->getMoneyValueOrFail()->getIdEntityOrFail(),
        ]);

        return $merchantTransfer->getMerchantReferenceOrFail();
    }

    /**
     * Same recipe as {@see haveOrderableProduct()}, priced below the store's hard minimum
     * threshold instead of inside its placeable window.
     */
    public function haveOrderableProductBelowHardMinimumThreshold(): ProductConcreteTransfer
    {
        $productConcreteTransfer = $this->getProductDataHelper()->haveFullProduct();

        $this->getStockDataHelper()->haveProductInStockForStore($this->getCurrentStore(), [
            StockProductTransfer::SKU => $productConcreteTransfer->getSkuOrFail(),
            StockProductTransfer::IS_NEVER_OUT_OF_STOCK => true,
        ]);

        $this->getPriceProductDataHelper()->havePriceProduct([
            PriceProductTransfer::SKU_PRODUCT_ABSTRACT => $productConcreteTransfer->getAbstractSku(),
            PriceProductTransfer::SKU_PRODUCT => $productConcreteTransfer->getSkuOrFail(),
            PriceProductTransfer::ID_PRODUCT => $productConcreteTransfer->getIdProductConcreteOrFail(),
            PriceProductTransfer::PRICE_TYPE_NAME => 'DEFAULT',
            PriceProductTransfer::MONEY_VALUE => [
                MoneyValueTransfer::NET_AMOUNT => static::PRODUCT_BELOW_HARD_MINIMUM_NET_AMOUNT,
                MoneyValueTransfer::GROSS_AMOUNT => static::PRODUCT_BELOW_HARD_MINIMUM_GROSS_AMOUNT,
            ],
        ]);

        return $productConcreteTransfer;
    }

    public function haveHardMinimumSalesOrderThreshold(): void
    {
        $this->getSalesOrderThresholdHelper()->haveSalesOrderThreshold([
            SalesOrderThresholdTransfer::CURRENCY => $this->getLocator()->currency()->facade()->fromIsoCode(static::CURRENCY_CODE),
            SalesOrderThresholdValueTransfer::THRESHOLD => static::HARD_MINIMUM_THRESHOLD_VALUE,
            SalesOrderThresholdTypeTransfer::KEY => SalesOrderThresholdConfig::THRESHOLD_STRATEGY_KEY_HARD,
            SalesOrderThresholdTypeTransfer::THRESHOLD_GROUP => SalesOrderThresholdConfig::GROUP_HARD,
            SalesOrderThresholdTransfer::LOCALIZED_MESSAGES => [
                (new SalesOrderThresholdLocalizedMessageTransfer())
                    ->setLocaleCode('en_US')
                    ->setMessage(static::HARD_MINIMUM_THRESHOLD_MESSAGE_EN_US),
                (new SalesOrderThresholdLocalizedMessageTransfer())
                    ->setLocaleCode('de_DE')
                    ->setMessage(static::HARD_MINIMUM_THRESHOLD_MESSAGE_DE_DE),
            ],
        ]);
    }

    /**
     * An active shipment method priced for the current store, resolvable by the NAME the payload
     * sends.
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeShipmentExpander::findActiveShipmentMethod()
     */
    public function haveActiveShipmentMethod(): ShipmentMethodTransfer
    {
        return $this->getShipmentMethodDataHelper()->haveShipmentMethod(
            [
                ShipmentMethodTransfer::CARRIER_NAME => static::SHIPMENT_CARRIER_NAME,
                ShipmentMethodTransfer::NAME => static::SHIPMENT_METHOD_NAME,
                ShipmentMethodTransfer::IS_ACTIVE => true,
            ],
            [],
            ShipmentMethodDataHelper::DEFAULT_PRICE_LIST,
            [$this->getCurrentStore()->getIdStoreOrFail()],
        );
    }

    /**
     * Everything a placeable POST /orders payload needs, arranged and assembled in one call.
     *
     * @param array<string, mixed> $override
     *
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: array<string, mixed>}
     */
    public function haveValidOrderPayload(array $override = []): array
    {
        $customerTransfer = $this->haveOrderingCustomer();
        [$productConcreteTransfer, $merchantReference] = $this->haveOrderableProduct();
        $this->haveActiveShipmentMethod();

        return [
            $customerTransfer,
            $this->buildValidOrderAttributes(
                $customerTransfer->getCustomerReferenceOrFail(),
                $productConcreteTransfer->getSkuOrFail(),
                $override,
                $merchantReference,
            ),
        ];
    }

    /**
     * Same shape as {@see haveValidOrderPayload()}, but the item's catalogue price sits below the
     * store's hard minimum threshold instead of inside its placeable window.
     *
     * @param array<string, mixed> $override
     *
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: array<string, mixed>}
     */
    public function haveOrderPayloadBelowHardMinimumThreshold(array $override = []): array
    {
        $this->haveHardMinimumSalesOrderThreshold();
        $customerTransfer = $this->haveOrderingCustomer();
        $productConcreteTransfer = $this->haveOrderableProductBelowHardMinimumThreshold();
        $this->haveActiveShipmentMethod();

        return [
            $customerTransfer,
            $this->buildValidOrderAttributes(
                $customerTransfer->getCustomerReferenceOrFail(),
                $productConcreteTransfer->getSkuOrFail(),
                $override + [
                    'items' => [
                        [
                            'sku' => $productConcreteTransfer->getSkuOrFail(),
                            'quantity' => 1,
                            'unitPrice' => static::PRODUCT_BELOW_HARD_MINIMUM_GROSS_AMOUNT,
                        ],
                    ],
                ],
            ),
        ];
    }

    /**
     * The minimum payload the resource declares as required, with addresses supplied inline rather
     * than by `uuid` so the customer needs no address book.
     *
     * `$merchantReference` is omitted from the line entirely when null, for an operator-sold item —
     * {@see haveOrderableProductBelowHardMinimumThreshold()} calls this without one, since that
     * scenario is rejected on threshold before payment resolution is ever reached.
     *
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidOrderAttributes(string $customerReference, string $sku, array $override = [], ?string $merchantReference = null): array
    {
        return $override + [
            'customerReference' => $customerReference,
            'store' => $this->getCurrentStore()->getNameOrFail(),
            'currency' => static::CURRENCY_CODE,
            'paymentMethod' => static::PAYMENT_METHOD_KEY,
            'shipment' => [
                'shipmentMethod' => static::SHIPMENT_METHOD_NAME,
                'shippingAddress' => $this->buildInlineAddress(),
            ],
            'billingAddress' => $this->buildInlineAddress(),
            'items' => [
                array_filter([
                    'sku' => $sku,
                    'quantity' => 1,
                    'unitPrice' => static::PRODUCT_GROSS_AMOUNT,
                    'merchantReference' => $merchantReference,
                ], static fn (mixed $value): bool => $value !== null),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildInlineAddress(): array
    {
        return [
            'salutation' => 'Ms',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'address1' => 'Julie-Wolfthorn-Strasse',
            'address2' => '1',
            'zipCode' => '10115',
            'city' => 'Berlin',
            'iso2Code' => static::ISO2_CODE,
        ];
    }

    /**
     * An active budget an order can actually be charged against, on an active cost center.
     *
     * Every field the builders randomise is pinned, because three of them decide whether the order
     * is placed at all: `isActive` (the builder rolls a boolean, and an inactive budget sends
     * `BudgetCheckoutValidator` down the "is a budget required" branch), `amount` (the builder rolls
     * 100–1000000 cents, which need not cover the order), and `currencyIsoCode` (rolled from three
     * currencies, and it is reported back on the resource).
     *
     * The cost center's company business unit is deliberately NOT connected to the ordering customer.
     * {@see \SprykerFeature\Zed\PurchasingControl\Business\CostCenter\CostCenterActiveChecker::findActiveCostCentersForQuote()}
     * returns null for a quote whose customer has no company user, and the validator then accepts any
     * active budget — so the fixture stays at a budget and a cost center rather than dragging in a
     * company, a business unit and a company user that none of the assertions are about.
     *
     * @param array<string, mixed> $overrides
     */
    public function haveActiveBudget(array $overrides = []): BudgetTransfer
    {
        $costCenterTransfer = $this->getPurchasingControlHelper()->haveCostCenter([
            CostCenterTransfer::IS_ACTIVE => true,
        ]);

        $budgetTransfer = $this->getPurchasingControlHelper()->haveBudget(
            $costCenterTransfer->getIdCostCenterOrFail(),
            $overrides + [
                BudgetTransfer::IS_ACTIVE => true,
                BudgetTransfer::AMOUNT => static::BUDGET_AMOUNT,
                BudgetTransfer::CURRENCY_ISO_CODE => static::CURRENCY_CODE,
                BudgetTransfer::ENFORCEMENT_RULE => static::BUDGET_ENFORCEMENT_RULE,
            ],
        );

        $this->costCenterIds[] = $costCenterTransfer->getIdCostCenterOrFail();
        $this->budgetIds[] = $budgetTransfer->getIdBudgetOrFail();

        return $budgetTransfer;
    }

    /**
     * Records an order that POST /orders actually placed, for deletion when the SUITE ends.
     *
     * Placement is not covered by the suite's transaction isolation — `CheckoutFacade::placeOrder()`
     * commits, and that commit survives the rollback `TransactionHelper` issues afterwards, so a
     * successful POST leaves a real order in the database. The read-only suites never see this, and
     * neither does CXM's, which only writes through Zed facades inside the request.
     *
     * Deletion is deferred to `_afterSuite()` rather than registered with
     * {@see \SprykerTest\Shared\Testify\Helper\DataCleanupHelper}: that helper is enabled BEFORE
     * `TransactionHelper` in the umbrella module list, so its `_after()` runs while the test
     * transaction is still open and the deletes are rolled back along with everything else —
     * leaving exactly the committed order they were meant to remove. By suite end every
     * transaction is closed, so the deletes stick.
     */
    public function cleanupPlacedOrder(string $orderReference): void
    {
        $this->placedOrderReferences[$orderReference] = $orderReference;
    }

    /**
     * Removes every order this suite placed, and the customer it was placed for.
     */
    public function _afterSuite(): void
    {
        foreach ($this->placedOrderReferences as $orderReference) {
            $customerReference = $this->findOrderCustomerReference($orderReference);

            $this->deletePlacedOrder($orderReference);

            if ($customerReference === null) {
                continue;
            }

            $this->deleteCustomerByReference($customerReference);
        }

        $this->placedOrderReferences = [];

        $this->deleteBudgetFixtures();
    }

    /**
     * Runs AFTER the placed orders are gone: `spy_budget_consumption` points at both, and the order
     * side of that is what `deletePlacedOrder()` clears.
     */
    protected function deleteBudgetFixtures(): void
    {
        $connection = Propel::getConnection();

        foreach ($this->budgetIds as $idBudget) {
            $connection->exec(sprintf('DELETE FROM spy_budget_consumption WHERE fk_budget = %d', $idBudget));
            $connection->exec(sprintf('DELETE FROM spy_budget WHERE id_budget = %d', $idBudget));
        }

        foreach ($this->costCenterIds as $idCostCenter) {
            $connection->exec(sprintf('DELETE FROM spy_cost_center_to_company_business_unit WHERE fk_cost_center = %d', $idCostCenter));
            $connection->exec(sprintf('DELETE FROM spy_cost_center WHERE id_cost_center = %d', $idCostCenter));
        }

        $this->budgetIds = [];
        $this->costCenterIds = [];
    }

    protected function findOrderCustomerReference(string $orderReference): ?string
    {
        $connection = Propel::getConnection();

        $customerReference = $connection
            ->query(sprintf(
                'SELECT customer_reference FROM spy_sales_order WHERE order_reference = %s',
                $connection->quote($orderReference),
            ))
            ->fetchColumn();

        return $customerReference === false || $customerReference === null || $customerReference === ''
            ? null
            : (string)$customerReference;
    }

    /**
     * Through the facade rather than SQL: a customer has its own web of relations (addresses,
     * consents, change requests), and `deleteCustomer()` already knows how to take them with it.
     *
     * `spy_customer_discount` is the exception it does NOT know about — placement records the
     * discounts the order consumed against the customer, and that FK is RESTRICT, so the facade's
     * delete fails on it. Removed here first, which is safe because the row is a usage record of an
     * order that has just been deleted.
     */
    protected function deleteCustomerByReference(string $customerReference): void
    {
        $customerFacade = $this->getLocator()->customer()->facade();
        $customerTransfer = $customerFacade->findByReference($customerReference);

        if ($customerTransfer === null) {
            return;
        }

        Propel::getConnection()->exec(sprintf(
            'DELETE FROM spy_customer_discount WHERE fk_customer = %d',
            $customerTransfer->getIdCustomerOrFail(),
        ));

        $customerFacade->deleteCustomer($customerTransfer);
    }

    /**
     * Raw SQL rather than Propel queries: every foreign key into `spy_sales_order` and
     * `spy_sales_order_item` is RESTRICT (only `spy_stripe_payment` cascades), so the children have
     * to be removed explicitly and in order, and naming ten ORM query classes to do it would couple
     * this cleanup to ten namespaces without making it any clearer.
     *
     * The statement list covers what a placed order actually writes, confirmed against a real one:
     * discounts, expense, item, item metadata, totals, payment, shipment — plus the OMS state
     * history, which a later transition adds.
     */
    protected function deletePlacedOrder(string $orderReference): void
    {
        $connection = Propel::getConnection();

        $idSalesOrder = $connection
            ->query(sprintf('SELECT id_sales_order FROM spy_sales_order WHERE order_reference = %s', $connection->quote($orderReference)))
            ->fetchColumn();

        if (!$idSalesOrder) {
            return;
        }

        $idSalesOrder = (int)$idSalesOrder;
        $itemSubQuery = sprintf('SELECT id_sales_order_item FROM spy_sales_order_item WHERE fk_sales_order = %d', $idSalesOrder);
        $addressIds = $connection
            ->query(sprintf(
                'SELECT fk_sales_order_address_billing, fk_sales_order_address_shipping FROM spy_sales_order WHERE id_sales_order = %d',
                $idSalesOrder,
            ))
            ->fetch(PDO::FETCH_NUM) ?: [];

        foreach (
            [
            // Written by PurchasingControl's ConsumeBudgetCheckoutPostSavePlugin when the order named
            // a budget; its fk_sales_order is RESTRICT, so the order below cannot go without it.
            sprintf('DELETE FROM spy_budget_consumption WHERE fk_sales_order = %d', $idSalesOrder),
            sprintf('DELETE FROM spy_sales_discount WHERE fk_sales_order_item IN (%s)', $itemSubQuery),
            sprintf('DELETE FROM spy_sales_discount WHERE fk_sales_order = %d', $idSalesOrder),
            sprintf('DELETE FROM spy_sales_order_item_metadata WHERE fk_sales_order_item IN (%s)', $itemSubQuery),
            sprintf('DELETE FROM spy_oms_order_item_state_history WHERE fk_sales_order_item IN (%s)', $itemSubQuery),
            sprintf('DELETE FROM spy_oms_transition_log WHERE fk_sales_order = %d', $idSalesOrder),
            // Items before the shipment they point at (`spy_sales_order_item.fk_sales_shipment`),
            // and the shipment before the expense it points at (`spy_sales_shipment.fk_sales_expense`).
            sprintf('DELETE FROM spy_sales_order_item WHERE fk_sales_order = %d', $idSalesOrder),
            sprintf('DELETE FROM spy_sales_shipment WHERE fk_sales_order = %d', $idSalesOrder),
            sprintf('DELETE FROM spy_sales_expense WHERE fk_sales_order = %d', $idSalesOrder),
            sprintf('DELETE FROM spy_sales_payment WHERE fk_sales_order = %d', $idSalesOrder),
            sprintf('DELETE FROM spy_sales_order_totals WHERE fk_sales_order = %d', $idSalesOrder),
            sprintf('DELETE FROM spy_sales_order WHERE id_sales_order = %d', $idSalesOrder),
            ] as $statement
        ) {
            $connection->exec($statement);
        }

        $addressIds = array_filter(array_map('intval', $addressIds));

        if ($addressIds === []) {
            return;
        }

        $connection->exec(sprintf(
            'DELETE FROM spy_sales_order_address WHERE id_sales_order_address IN (%s)',
            implode(',', $addressIds),
        ));
    }

    /**
     * A well-formed uuid no budget carries, so the lookup runs and comes back empty rather than the
     * value being rejected earlier as malformed.
     */
    public function getUnknownBudgetUuid(): string
    {
        return '00000000-0000-4000-8000-000000000000';
    }

    public function getUnknownSku(): string
    {
        return 'oem-backend-api-sku-does-not-exist';
    }

    public function getUnknownShipmentMethodName(): string
    {
        return 'OemBackendApiNoSuchShipmentMethod';
    }

    public function getCurrentStore(): StoreTransfer
    {
        return $this->getStoreFacade()->getCurrentStore();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildOrderRequestBody(array $attributes): string
    {
        return (string)json_encode([
            'data' => [
                'type' => static::RESOURCE_ORDERS,
                'attributes' => $attributes,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function formatQuery(array $query): string
    {
        if ($query === []) {
            return '';
        }

        return '?' . http_build_query($query);
    }

    protected function getSalesDataHelper(): SalesDataHelper
    {
        /** @var \SprykerTest\Shared\Sales\Helper\SalesDataHelper $salesDataHelper */
        $salesDataHelper = $this->getModule('\\' . SalesDataHelper::class);

        return $salesDataHelper;
    }

    protected function getCustomerDataHelper(): CustomerDataHelper
    {
        /** @var \SprykerTest\Shared\Customer\Helper\CustomerDataHelper $customerDataHelper */
        $customerDataHelper = $this->getModule('\\' . CustomerDataHelper::class);

        return $customerDataHelper;
    }

    protected function getProductDataHelper(): ProductDataHelper
    {
        /** @var \SprykerTest\Shared\Product\Helper\ProductDataHelper $productDataHelper */
        $productDataHelper = $this->getModule('\\' . ProductDataHelper::class);

        return $productDataHelper;
    }

    protected function getPriceProductDataHelper(): PriceProductDataHelper
    {
        /** @var \SprykerTest\Shared\PriceProduct\Helper\PriceProductDataHelper $priceProductDataHelper */
        $priceProductDataHelper = $this->getModule('\\' . PriceProductDataHelper::class);

        return $priceProductDataHelper;
    }

    protected function getStockDataHelper(): StockDataHelper
    {
        /** @var \SprykerTest\Shared\Stock\Helper\StockDataHelper $stockDataHelper */
        $stockDataHelper = $this->getModule('\\' . StockDataHelper::class);

        return $stockDataHelper;
    }

    protected function getSalesOrderThresholdHelper(): SalesOrderThresholdHelper
    {
        /** @var \SprykerTest\Zed\SalesOrderThreshold\Helper\SalesOrderThresholdHelper $salesOrderThresholdHelper */
        $salesOrderThresholdHelper = $this->getModule('\\' . SalesOrderThresholdHelper::class);

        return $salesOrderThresholdHelper;
    }

    protected function getShipmentMethodDataHelper(): ShipmentMethodDataHelper
    {
        /** @var \SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper $shipmentMethodDataHelper */
        $shipmentMethodDataHelper = $this->getModule('\\' . ShipmentMethodDataHelper::class);

        return $shipmentMethodDataHelper;
    }

    protected function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getLocator()->store()->facade();
    }

    protected function getPurchasingControlHelper(): PurchasingControlHelper
    {
        /** @var \SprykerFeatureTest\Shared\PurchasingControl\Helper\PurchasingControlHelper $purchasingControlHelper */
        $purchasingControlHelper = $this->getModule('\\' . PurchasingControlHelper::class);

        return $purchasingControlHelper;
    }

    protected function getOmsHelper(): OmsHelper
    {
        /** @var \SprykerTest\Zed\Oms\Helper\OmsHelper $omsHelper */
        $omsHelper = $this->getModule('\\' . OmsHelper::class);

        return $omsHelper;
    }

    protected function getMerchantHelper(): MerchantHelper
    {
        /** @var \SprykerTest\Zed\Merchant\Helper\MerchantHelper $merchantHelper */
        $merchantHelper = $this->getModule('\\' . MerchantHelper::class);

        return $merchantHelper;
    }

    protected function getProductOfferHelper(): ProductOfferHelper
    {
        /** @var \SprykerTest\Zed\ProductOffer\Helper\ProductOfferHelper $productOfferHelper */
        $productOfferHelper = $this->getModule('\\' . ProductOfferHelper::class);

        return $productOfferHelper;
    }

    protected function getProductOfferStockDataHelper(): ProductOfferStockDataHelper
    {
        /** @var \SprykerTest\Shared\ProductOfferStock\Helper\ProductOfferStockDataHelper $productOfferStockDataHelper */
        $productOfferStockDataHelper = $this->getModule('\\' . ProductOfferStockDataHelper::class);

        return $productOfferStockDataHelper;
    }

    protected function getPriceProductOfferHelper(): PriceProductOfferHelper
    {
        /** @var \SprykerTest\Shared\PriceProductOffer\Helper\PriceProductOfferHelper $priceProductOfferHelper */
        $priceProductOfferHelper = $this->getModule('\\' . PriceProductOfferHelper::class);

        return $priceProductOfferHelper;
    }
}
