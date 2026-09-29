<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement\Helper;

use ArrayObject;
use Codeception\Module;
use Exception;
use Generated\Shared\DataBuilder\MerchantProfileBuilder;
use Generated\Shared\DataBuilder\MerchantRelationshipBuilder;
use Generated\Shared\DataBuilder\QuoteBuilder;
use Generated\Shared\Transfer\BudgetTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitCollectionTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyRoleCollectionTransfer;
use Generated\Shared\Transfer\CompanyRoleTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CostCenterTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\DiscountCalculatorTransfer;
use Generated\Shared\Transfer\DiscountConditionTransfer;
use Generated\Shared\Transfer\DiscountConfiguratorTransfer;
use Generated\Shared\Transfer\DiscountGeneralTransfer;
use Generated\Shared\Transfer\MerchantRelationshipRequestTransfer;
use Generated\Shared\Transfer\MerchantRelationshipTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\OrderItemFilterTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\PriceProductDimensionTransfer;
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
use Orm\Zed\Discount\Persistence\SpyDiscountQuery;
use Orm\Zed\Discount\Persistence\SpyDiscountVoucher;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use PDO;
use Propel\Runtime\Propel;
use Spryker\Shared\Discount\DiscountConstants;
use Spryker\Shared\DummyMarketplacePayment\DummyMarketplacePaymentConfig;
use Spryker\Shared\PriceProductMerchantRelationship\PriceProductMerchantRelationshipConfig;
use Spryker\Shared\SalesOrderThreshold\SalesOrderThresholdConfig;
use Spryker\Zed\Discount\DiscountDependencyProvider;
use Spryker\Zed\QuoteApproval\Communication\Plugin\Permission\PlaceOrderPermissionPlugin;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeatureTest\Shared\PurchasingControl\Helper\PurchasingControlHelper;
use SprykerTest\Shared\CompanyUser\Helper\CompanyUserHelper;
use SprykerTest\Shared\Customer\Helper\CustomerDataHelper;
use SprykerTest\Shared\PriceProduct\Helper\PriceProductDataHelper;
use SprykerTest\Shared\PriceProductOffer\Helper\PriceProductOfferHelper;
use SprykerTest\Shared\Product\Helper\ProductDataHelper;
use SprykerTest\Shared\ProductOfferStock\Helper\ProductOfferStockDataHelper;
use SprykerTest\Shared\Sales\Helper\SalesDataHelper;
use SprykerTest\Shared\Shipment\Helper\ShipmentMethodDataHelper;
use SprykerTest\Shared\Stock\Helper\StockDataHelper;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use SprykerTest\Zed\Company\Helper\CompanyHelper;
use SprykerTest\Zed\CompanyBusinessUnit\Helper\CompanyBusinessUnitHelper;
use SprykerTest\Zed\Merchant\Helper\MerchantHelper;
use SprykerTest\Zed\Oms\Helper\OmsHelper;
use SprykerTest\Zed\ProductOffer\Helper\ProductOfferHelper;
use SprykerTest\Zed\SalesOrderThreshold\Helper\SalesOrderThresholdHelper;

class OrderExperienceManagementBackendApiHelper extends Module
{
    use LocatorHelperTrait;

    /**
     * @var array<string, string>
     */
    protected array $placedOrderReferences = [];

    /**
     * @var array<int, int>
     */
    protected array $budgetIds = [];

    /**
     * @var array<int, int>
     */
    protected array $costCenterIds = [];

    /**
     * @var array<int, int>
     */
    protected array $discountIds = [];

    /**
     * @var array<int, int>
     */
    protected array $merchantRelationshipIds = [];

    public const string RESOURCE_ORDERS = 'orders';

    protected const string QUERY_PARAM_FILTER = 'filter';

    protected const string FILTER_KEY_PREFIX = 'orders.';

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

    protected const string CUSTOMER_REFERENCE_PREFIX = 'oem-backend-api';

    /**
     * @var string
     */
    protected const OMS_PROCESS_NAME = 'Test01';

    protected const string SHIPMENT_CARRIER_NAME = 'Spryker Dummy Shipment';

    protected const string SHIPMENT_METHOD_NAME = 'Standard';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePaymentResolver::findAvailablePaymentMethod()
     */
    protected const string PAYMENT_METHOD_KEY = DummyMarketplacePaymentConfig::PAYMENT_METHOD_DUMMY_MARKETPLACE_PAYMENT_INVOICE;

    protected const string CURRENCY_CODE = 'EUR';

    protected const int BUDGET_AMOUNT = 100000000;

    /**
     * @uses \SprykerFeature\Shared\PurchasingControl\PurchasingControlConfig::ENFORCEMENT_RULE_BLOCK
     */
    protected const string BUDGET_ENFORCEMENT_RULE = 'block';

    protected const string ISO2_CODE = 'DE';

    protected const string COMPANY_STATUS_APPROVED = 'approved';

    /**
     * 10.00 %, in the hundredths the percentage calculator expects.
     */
    protected const string VOUCHER_PERCENTAGE = '1000';

    /**
     * @see spy_sales_order_threshold
     */
    protected const int PRODUCT_NET_AMOUNT = 126050;

    protected const int PRODUCT_GROSS_AMOUNT = 150000;

    protected const int PRODUCT_BELOW_HARD_MINIMUM_NET_AMOUNT = 2000;

    protected const int PRODUCT_BELOW_HARD_MINIMUM_GROSS_AMOUNT = 2000;

    protected const int HARD_MINIMUM_THRESHOLD_VALUE = 4000;

    protected const string HARD_MINIMUM_THRESHOLD_MESSAGE_EN_US = 'The order value is below the minimum required amount.';

    protected const string HARD_MINIMUM_THRESHOLD_MESSAGE_DE_DE = 'Der Bestellwert liegt unter dem erforderlichen Mindestbetrag.';

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
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: \Generated\Shared\Transfer\SaveOrderTransfer}
     */
    public function haveCustomerWithOrder(): array
    {
        $customerTransfer = $this->haveOrderingCustomer();

        return [$customerTransfer, $this->haveOrderForCustomer($customerTransfer)];
    }

    /**
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
     * @param array<int, string> $itemUuids
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

    public function haveOrderingCustomer(): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([
            CustomerTransfer::EMAIL => uniqid('oem.backend.api.', true) . '@spryker.local',
        ]);
    }

    /**
     * @return array{0: \Generated\Shared\Transfer\ProductConcreteTransfer, 1: string, 2: \Generated\Shared\Transfer\PriceProductTransfer, 3: \Generated\Shared\Transfer\MerchantTransfer}
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

        $merchantTransfer = $this->haveMerchantOfferForProduct($productConcreteTransfer, $priceProductTransfer);

        return [$productConcreteTransfer, $merchantTransfer->getMerchantReferenceOrFail(), $priceProductTransfer, $merchantTransfer];
    }

    protected function haveMerchantOfferForProduct(
        ProductConcreteTransfer $productConcreteTransfer,
        PriceProductTransfer $priceProductTransfer,
    ): MerchantTransfer {
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

        return $merchantTransfer;
    }

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
                        ],
                    ],
                ],
            ),
        ];
    }

    /**
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
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: array<string, mixed>}
     */
    public function haveValidOrderPayloadWithContractPrice(int $contractGrossAmount, int $contractNetAmount): array
    {
        $customerTransfer = $this->haveOrderingCustomer();
        [$productConcreteTransfer, $merchantReference, $priceProductTransfer, $merchantTransfer] = $this->haveOrderableProduct();
        $this->haveActiveShipmentMethod();

        $companyBusinessUnitTransfer = $this->haveCompanyBusinessUnitForCustomer($customerTransfer);
        $merchantRelationshipTransfer = $this->haveMerchantRelationship($merchantTransfer, $companyBusinessUnitTransfer);
        $this->haveMerchantRelationshipPrice(
            $productConcreteTransfer,
            $priceProductTransfer,
            $merchantRelationshipTransfer,
            $contractGrossAmount,
            $contractNetAmount,
        );

        return [
            $customerTransfer,
            $this->buildValidOrderAttributes(
                $customerTransfer->getCustomerReferenceOrFail(),
                $productConcreteTransfer->getSkuOrFail(),
                ['companyBusinessUnitUuid' => $companyBusinessUnitTransfer->getUuidOrFail()],
                $merchantReference,
            ),
        ];
    }

    /**
     * @throws \Exception
     *
     * @return array{0: string, 1: string} The voucher code and the display name of its discount.
     */
    public function haveActiveVoucherCode(): array
    {
        $displayName = uniqid('OEM Backend API voucher ', true);

        $discountConfiguratorTransfer = (new DiscountConfiguratorTransfer())
            ->setDiscountGeneral(
                (new DiscountGeneralTransfer())
                    ->setDiscountType(DiscountConstants::TYPE_VOUCHER)
                    ->setDisplayName($displayName)
                    ->setIsActive(true)
                    ->setIsExclusive(false)
                    ->setValidFrom('2020-01-01 00:00:00')
                    ->setValidTo('2035-01-01 00:00:00')
                    ->setStoreRelation(
                        (new StoreRelationTransfer())->setIdStores([$this->getCurrentStore()->getIdStoreOrFail()]),
                    ),
            )
            ->setDiscountCalculator(
                (new DiscountCalculatorTransfer())
                    ->setCalculatorPlugin(DiscountDependencyProvider::PLUGIN_CALCULATOR_PERCENTAGE)
                    ->setAmount(static::VOUCHER_PERCENTAGE)
                    ->setCollectorQueryString("sku = '*'"),
            )
            ->setDiscountCondition(
                (new DiscountConditionTransfer())
                    ->setDecisionRuleQueryString("sub-total >= '0'")
                    ->setMinimumItemAmount(1),
            );

        $discountConfiguratorResponseTransfer = $this->getLocator()->discount()->facade()->createDiscount($discountConfiguratorTransfer);

        if (!$discountConfiguratorResponseTransfer->getIsSuccessful()) {
            $messages = [];

            foreach ($discountConfiguratorResponseTransfer->getMessages() as $messageTransfer) {
                $messages[] = (string)$messageTransfer->getValue();
            }

            throw new Exception(sprintf('Failed to create the voucher discount: %s', implode('; ', $messages)));
        }

        $idDiscount = $discountConfiguratorResponseTransfer->getDiscountConfiguratorOrFail()
            ->getDiscountGeneralOrFail()
            ->getIdDiscountOrFail();
        $this->discountIds[] = $idDiscount;

        $voucherCode = uniqid('oem-api-');
        (new SpyDiscountVoucher())
            ->setFkDiscountVoucherPool(SpyDiscountQuery::create()->findPk($idDiscount)->getFkDiscountVoucherPool())
            ->setCode($voucherCode)
            ->setIsActive(true)
            ->save();

        return [$voucherCode, $displayName];
    }

    protected function haveCompanyBusinessUnitForCustomer(CustomerTransfer $customerTransfer): CompanyBusinessUnitTransfer
    {
        $companyTransfer = $this->getCompanyHelper()->haveActiveCompany([
            CompanyTransfer::STATUS => static::COMPANY_STATUS_APPROVED,
        ]);

        $companyBusinessUnitTransfer = $this->getCompanyBusinessUnitHelper()->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
        ]);

        $companyUserTransfer = $this->getCompanyUserHelper()->haveCompanyUser([
            CompanyUserTransfer::CUSTOMER => $customerTransfer,
            CompanyUserTransfer::FK_CUSTOMER => $customerTransfer->getIdCustomerOrFail(),
            CompanyUserTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
            CompanyUserTransfer::FK_COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
            CompanyUserTransfer::IS_ACTIVE => true,
        ]);

        $this->assignPlaceOrderRole($companyUserTransfer, $companyTransfer);

        return $this->getLocator()->companyBusinessUnit()->facade()->getCompanyBusinessUnitById(
            (new CompanyBusinessUnitTransfer())->setIdCompanyBusinessUnit($companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail()),
        );
    }

    /**
     * Without it the project's quote approval pre-check refuses the order as over the purchasing limit.
     *
     * @throws \Exception
     */
    protected function assignPlaceOrderRole(CompanyUserTransfer $companyUserTransfer, CompanyTransfer $companyTransfer): void
    {
        $permissionTransfer = $this->getLocator()->permission()->facade()->findPermissionByKey(PlaceOrderPermissionPlugin::KEY);

        if ($permissionTransfer === null) {
            throw new Exception(sprintf('Permission "%s" is not synchronised.', PlaceOrderPermissionPlugin::KEY));
        }

        $companyRoleTransfer = $this->getLocator()->companyRole()->facade()->create(
            (new CompanyRoleTransfer())
                ->setFkCompany($companyTransfer->getIdCompanyOrFail())
                ->setName(uniqid('OEM Backend API buyer ', true))
                ->setIsDefault(false)
                ->setPermissionCollection((new PermissionCollectionTransfer())->addPermission($permissionTransfer)),
        )->getCompanyRoleTransferOrFail();

        $this->getLocator()->companyRole()->facade()->saveCompanyUser(
            $companyUserTransfer->setCompanyRoleCollection((new CompanyRoleCollectionTransfer())->addRole($companyRoleTransfer)),
        );
    }

    protected function haveMerchantRelationship(
        MerchantTransfer $merchantTransfer,
        CompanyBusinessUnitTransfer $companyBusinessUnitTransfer,
    ): MerchantRelationshipTransfer {
        $merchantRelationshipTransfer = (new MerchantRelationshipBuilder([
            MerchantRelationshipTransfer::FK_MERCHANT => $merchantTransfer->getIdMerchantOrFail(),
            MerchantRelationshipTransfer::FK_COMPANY_BUSINESS_UNIT => $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
            MerchantRelationshipTransfer::MERCHANT_RELATIONSHIP_KEY => uniqid('oem-api-mr-'),
        ]))->build()
            ->setIdMerchantRelationship(null)
            ->setMerchant($merchantTransfer)
            ->setOwnerCompanyBusinessUnit($companyBusinessUnitTransfer)
            ->setAssigneeCompanyBusinessUnits(
                (new CompanyBusinessUnitCollectionTransfer())->addCompanyBusinessUnit($companyBusinessUnitTransfer),
            );

        /** @var \Generated\Shared\Transfer\MerchantRelationshipResponseTransfer $merchantRelationshipResponseTransfer */
        $merchantRelationshipResponseTransfer = $this->getLocator()->merchantRelationship()->facade()->createMerchantRelationship(
            $merchantRelationshipTransfer,
            (new MerchantRelationshipRequestTransfer())->setMerchantRelationship($merchantRelationshipTransfer),
        );
        $merchantRelationshipTransfer = $merchantRelationshipResponseTransfer->getMerchantRelationshipOrFail();

        $this->merchantRelationshipIds[] = $merchantRelationshipTransfer->getIdMerchantRelationshipOrFail();

        return $merchantRelationshipTransfer;
    }

    protected function haveMerchantRelationshipPrice(
        ProductConcreteTransfer $productConcreteTransfer,
        PriceProductTransfer $defaultPriceProductTransfer,
        MerchantRelationshipTransfer $merchantRelationshipTransfer,
        int $grossAmount,
        int $netAmount,
    ): void {
        $defaultMoneyValueTransfer = $defaultPriceProductTransfer->getMoneyValueOrFail();

        $priceProductTransfer = (new PriceProductTransfer())
            ->setIdPriceProduct($defaultPriceProductTransfer->getIdPriceProductOrFail())
            ->setIdProduct($productConcreteTransfer->getIdProductConcreteOrFail())
            ->setSkuProduct($productConcreteTransfer->getSkuOrFail())
            ->setSkuProductAbstract($productConcreteTransfer->getAbstractSkuOrFail())
            ->setFkPriceType($defaultPriceProductTransfer->getFkPriceType())
            ->setPriceType($defaultPriceProductTransfer->getPriceType())
            ->setPriceTypeName($defaultPriceProductTransfer->getPriceTypeName())
            ->setMoneyValue(
                (new MoneyValueTransfer())
                    ->setFkStore($defaultMoneyValueTransfer->getFkStoreOrFail())
                    ->setFkCurrency($defaultMoneyValueTransfer->getFkCurrencyOrFail())
                    ->setCurrency($defaultMoneyValueTransfer->getCurrency())
                    ->setGrossAmount($grossAmount)
                    ->setNetAmount($netAmount),
            )
            ->setPriceDimension(
                (new PriceProductDimensionTransfer())
                    ->setType(PriceProductMerchantRelationshipConfig::PRICE_DIMENSION_MERCHANT_RELATIONSHIP)
                    ->setIdMerchantRelationship($merchantRelationshipTransfer->getIdMerchantRelationshipOrFail()),
            );

        $this->getLocator()->priceProduct()->facade()->persistPriceProductStore($priceProductTransfer);
    }

    public function cleanupPlacedOrder(string $orderReference): void
    {
        $this->placedOrderReferences[$orderReference] = $orderReference;
    }

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
        $this->deleteDiscountFixtures();
        $this->deleteMerchantRelationshipFixtures();
    }

    protected function deleteDiscountFixtures(): void
    {
        $connection = Propel::getConnection();

        foreach ($this->discountIds as $idDiscount) {
            $idVoucherPool = (int)SpyDiscountQuery::create()->findPk($idDiscount)?->getFkDiscountVoucherPool();

            $connection->exec(sprintf('DELETE FROM spy_discount_store WHERE fk_discount = %d', $idDiscount));
            $connection->exec(sprintf('DELETE FROM spy_discount_amount WHERE fk_discount = %d', $idDiscount));
            $connection->exec(sprintf('DELETE FROM spy_discount WHERE id_discount = %d', $idDiscount));

            if ($idVoucherPool === 0) {
                continue;
            }

            $connection->exec(sprintf('DELETE FROM spy_discount_voucher WHERE fk_discount_voucher_pool = %d', $idVoucherPool));
            $connection->exec(sprintf('DELETE FROM spy_discount_voucher_pool WHERE id_discount_voucher_pool = %d', $idVoucherPool));
        }

        $this->discountIds = [];
    }

    protected function deleteMerchantRelationshipFixtures(): void
    {
        $connection = Propel::getConnection();

        foreach ($this->merchantRelationshipIds as $idMerchantRelationship) {
            $connection->exec(sprintf('DELETE FROM spy_price_product_merchant_relationship WHERE fk_merchant_relationship = %d', $idMerchantRelationship));
            $connection->exec(sprintf('DELETE FROM spy_merchant_relationship_to_company_business_unit WHERE fk_merchant_relationship = %d', $idMerchantRelationship));
            $connection->exec(sprintf('DELETE FROM spy_merchant_relationship WHERE id_merchant_relationship = %d', $idMerchantRelationship));
        }

        $this->merchantRelationshipIds = [];
    }

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

    public function getUnknownBudgetUuid(): string
    {
        return '00000000-0000-4000-8000-000000000000';
    }

    public function getOrderableProductGrossAmount(): int
    {
        return static::PRODUCT_GROSS_AMOUNT;
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

    protected function getCompanyHelper(): CompanyHelper
    {
        /** @var \SprykerTest\Zed\Company\Helper\CompanyHelper $companyHelper */
        $companyHelper = $this->getModule('\\' . CompanyHelper::class);

        return $companyHelper;
    }

    protected function getCompanyBusinessUnitHelper(): CompanyBusinessUnitHelper
    {
        /** @var \SprykerTest\Zed\CompanyBusinessUnit\Helper\CompanyBusinessUnitHelper $companyBusinessUnitHelper */
        $companyBusinessUnitHelper = $this->getModule('\\' . CompanyBusinessUnitHelper::class);

        return $companyBusinessUnitHelper;
    }

    protected function getCompanyUserHelper(): CompanyUserHelper
    {
        /** @var \SprykerTest\Shared\CompanyUser\Helper\CompanyUserHelper $companyUserHelper */
        $companyUserHelper = $this->getModule('\\' . CompanyUserHelper::class);

        return $companyUserHelper;
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
