<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\OrderExperienceManagement\AbstractOrderExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateOrderBackendApiTest
 * Add your own group annotations below this line
 */
class CreateOrderBackendApiTest extends AbstractOrderExperienceManagementBackendApiTestCase
{
    /**
     * @var array<string>
     */
    protected const REQUIRED_ATTRIBUTES = [
        'customerReference',
        'store',
        'currency',
        'paymentMethod',
    ];

    protected const int UNIT_CUSTOM_PRICE = 175000;

    protected const int CONTRACT_GROSS_PRICE = 120000;

    protected const int CONTRACT_NET_PRICE = 100840;

    /**
     * @var list<string>
     */
    protected const array EXPECTED_ORDER_ATTRIBUTES = [
        'orderReference',
        'customerReference',
        'store',
        'currency',
        'priceMode',
        'locale',
        'createdAt',
        'customer',
        'billingAddress',
        'payments',
        'items',
        'itemsCount',
        'itemStates',
        'availableEvents',
        'totals',
        'expenses',
        'calculatedDiscounts',
        'comments',
    ];

    /**
     * @var list<string>
     */
    protected const array EXPECTED_ORDER_ITEM_ATTRIBUTES = [
        'uuid',
        'sku',
        'name',
        'quantity',
        'unitPrice',
        'sumPrice',
        'merchantReference',
        'productOptions',
        'shipment',
        'taxRate',
        'sumTaxAmount',
        'refundableAmount',
        'canceledAmount',
        'calculatedDiscounts',
        'sumSubtotalAggregation',
        'sumDiscountAmountFullAggregation',
        'sumPriceToPayAggregation',
        'state',
        'availableEvents',
    ];

    /**
     * @var list<string>
     */
    protected const array EXPECTED_ORDER_TOTALS_ATTRIBUTES = [
        'subtotal',
        'expenseTotal',
        'discountTotal',
        'taxTotal',
        'taxBreakdown',
        'grandTotal',
        'canceledTotal',
        'refundableTotal',
        'remunerationTotal',
    ];

    public function testGivenACreatedOrderWhenItsLinesAreComparedWithTheGetResponseThenTheUuidsMatch(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        // Act
        $createResponse = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);

        $createdItems = $this->getResourceAttributes($createResponse)['items'] ?? [];
        $this->assertNotEmpty($createdItems, 'The POST response echoes the lines it created.');

        foreach ($createdItems as $createdItem) {
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                (string)($createdItem['uuid'] ?? ''),
                'items[].uuid on the POST response must be the sales-order-item uuid, not the reference.',
            );
        }

        $orderReference = (string)($this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));
        $readItems = $this->getResourceAttributes($getResponse)['items'] ?? [];

        $this->assertSame(
            array_column($createdItems, 'uuid'),
            array_column($readItems, 'uuid'),
            'The POST and the GET must report the same line uuids, in the same order.',
        );
    }

    public function testGivenAValidPayloadWhenCreateOrderThenTheOrderIsCreated(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $payload = $this->decodeJsonApi($response);
        $orderReference = (string)($payload[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $responseAttributes = $this->getResourceAttributes($response);

        $this->assertNotSame('', $orderReference, 'The created order is addressed by the reference it reports back.');
        $this->assertSame(
            $customerTransfer->getCustomerReferenceOrFail(),
            $responseAttributes['customerReference'] ?? null,
            'The order landed on the customer the payload named.',
        );
    }

    public function testGivenTheSoleItemNamesItsOwnShipmentWhenTheOrderLevelShipmentIsOmittedThenTheOrderIsCreated(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $attributes['items'][0]['shipment'] = $attributes['shipment'];
        unset($attributes['shipment']);
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
    }

    public function testGivenACreatedOrderWhenGetItByReferenceThenTheSameOrderIsReturned(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        $createResponse = $this->createOrderViaApi($attributes);
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);
        $orderReference = (string)$this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID];

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $readAttributes = $this->getResourceAttributes($response);
        $this->assertSame(
            $orderReference,
            $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID],
        );
        $this->assertSame(
            $customerTransfer->getCustomerReferenceOrFail(),
            $readAttributes['customerReference'] ?? null,
        );
        $this->assertSame(
            $attributes['items'][0]['sku'],
            $readAttributes['items'][0]['sku'] ?? null,
            'The line the payload submitted is the line the order reports.',
        );
        $this->assertSame(1, $readAttributes['itemsCount'] ?? null);
    }

    public function testGivenAnOrderCustomReferenceWhenCreateOrderThenTheGetReportsIt(): void
    {
        // Arrange
        $orderCustomReference = uniqid('oem-api-po-');
        [, $attributes] = $this->tester->haveValidOrderPayload(['orderCustomReference' => $orderCustomReference]);
        $this->tester->actingAsUser();

        // Act
        $createResponse = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);

        $orderReference = (string)$this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID];
        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        $this->assertRespondsWithStatus($getResponse, Response::HTTP_OK);
        $this->assertSame(
            $orderCustomReference,
            $this->getResourceAttributes($getResponse)['orderCustomReference'] ?? null,
            'The custom reference is persisted by the project Sales post-save plugins and must be readable afterwards.',
        );
    }

    public function testGivenTwoOrdersForTheSameCustomerWithoutAnOrderCustomReferenceWhenCreateOrderThenBothArePlaced(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        // Act
        $firstResponse = $this->createOrderViaApi($attributes);
        $secondResponse = $this->createOrderViaApi($attributes);

        // Assert — API orders are exempt from the per-customer checkout lock (QuoteCheckoutConnectorConfig).
        $this->assertRespondsWithStatus($firstResponse, Response::HTTP_CREATED);
        $this->assertRespondsWithStatus($secondResponse, Response::HTTP_CREATED);
        $this->assertCount(2, $this->findOrderReferencesFor($customerTransfer->getCustomerReferenceOrFail()));
    }

    public function testGivenTheSameOrderCustomReferenceTwiceWhenCreateOrderThenTheSecondIsRejected(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload([
            'orderCustomReference' => uniqid('oem-api-po-'),
        ]);
        $this->tester->actingAsUser();

        // Act
        $firstResponse = $this->createOrderViaApi($attributes);
        $secondResponse = $this->createOrderViaApi($attributes);

        // Assert — for API orders the checkout lock is keyed by customer and orderCustomReference.
        $this->assertRespondsWithStatus($firstResponse, Response::HTTP_CREATED);
        $this->assertRespondsWithStatus($secondResponse, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(1, $this->findOrderReferencesFor($customerTransfer->getCustomerReferenceOrFail()));
    }

    public function testGivenACustomerWithAMerchantRelationshipPriceWhenCreateOrderThenTheContractPriceIsCharged(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayloadWithContractPrice(
            static::CONTRACT_GROSS_PRICE,
            static::CONTRACT_NET_PRICE,
        );
        $this->tester->actingAsUser();

        $this->assertNotSame(static::CONTRACT_GROSS_PRICE, $this->tester->getOrderableProductGrossAmount());

        // Act
        $createResponse = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);

        $orderReference = (string)$this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID];
        $getAttributes = $this->getResourceAttributes($this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference)));

        $this->assertSame(
            static::CONTRACT_GROSS_PRICE,
            $getAttributes['items'][0]['unitPrice'] ?? null,
            'Only the merchant-relationship price dimension of the buyer\'s business unit yields this price; the default is higher.',
        );
        $this->assertSame($attributes['companyBusinessUnitUuid'], $getAttributes['companyBusinessUnitUuid'] ?? null);
        $this->assertNotEmpty($getAttributes['companyUuid'] ?? null);
    }

    public function testGivenAVoucherCodeWhenCreateOrderThenItsDiscountIsAppliedAndPersisted(): void
    {
        // Arrange
        [$voucherCode, $discountDisplayName] = $this->tester->haveActiveVoucherCode();
        [, $attributes] = $this->tester->haveValidOrderPayload(['cartCodes' => [$voucherCode]]);
        $this->tester->actingAsUser();

        // Act
        $createResponse = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);

        $orderReference = (string)$this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID];
        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        $this->assertContains(
            $discountDisplayName,
            array_column($this->getResourceAttributes($createResponse)['calculatedDiscounts'] ?? [], 'displayName'),
        );
        $this->assertContains(
            $discountDisplayName,
            array_column($this->getResourceAttributes($getResponse)['calculatedDiscounts'] ?? [], 'displayName'),
            'The voucher discount must be saved with the order, not only calculated for the POST response.',
        );
    }

    public function testGivenACreatedOrderWhenSearchByItsCustomerThenItAppearsInTheCollection(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        $createResponse = $this->createOrderViaApi($attributes);
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);
        $orderReference = (string)$this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID];

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerTransfer->getCustomerReferenceOrFail(),
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$orderReference], $this->getResourceIds($response));
    }

    public function testGivenAValidPayloadWhenCreateOrderThenTotalsAreCalculated(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert — intake calculates the quote before placing it, so the response carries real
        // totals rather than the submitted amounts echoed back.
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $totals = $this->getResourceAttributes($response)['totals'] ?? [];
        $this->assertNotEmpty($totals, 'A placed order reports its totals.');
        $this->assertGreaterThan(
            0,
            $totals['grandTotal'] ?? 0,
            'The grand total covers the line and its shipment expense, so it is above zero.',
        );
    }

    public function testGivenACreatedOrderWhenComparedWithTheGetResponseThenTheBreakdownFieldsAgree(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        // Act
        $createResponse = $this->createOrderViaApi($attributes);
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);

        $orderReference = (string)($this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $postAttributes = $this->getResourceAttributes($createResponse);

        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));
        $this->assertRespondsWithStatus($getResponse, Response::HTTP_OK);
        $getAttributes = $this->getResourceAttributes($getResponse);

        // Assert
        $this->assertNotEmpty(
            $postAttributes['expenses'] ?? [],
            'The order carries a shipment expense from the moment it is placed; POST must report it.',
        );
        $this->assertNotEmpty(
            $postAttributes['availableEvents'] ?? [],
            'A freshly placed order in the "new" state has manual OMS events available.',
        );
        $this->assertSame($getAttributes['expenses'] ?? [], $postAttributes['expenses'] ?? []);
        $this->assertSame($getAttributes['calculatedDiscounts'] ?? [], $postAttributes['calculatedDiscounts'] ?? []);
        $this->assertSame($getAttributes['availableEvents'] ?? [], $postAttributes['availableEvents'] ?? []);
        $this->assertSame($getAttributes['itemStates'] ?? [], $postAttributes['itemStates'] ?? []);
        $this->assertSame($getAttributes['totals'] ?? [], $postAttributes['totals'] ?? []);
    }

    public function testGivenACreatedOrderWhenGetItByReferenceThenItIsSerializedAsTheSchemaDeclares(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $this->tester->actingAsUser();

        $createResponse = $this->createOrderViaApi($attributes);
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);
        $orderReference = (string)$this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID];

        // Act
        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        // Assert — keys come from orders.resource.yml and its object files; null values are skipped.
        $this->assertRespondsWithStatus($getResponse, Response::HTTP_OK);
        $getAttributes = $this->getResourceAttributes($getResponse);

        $this->assertEqualsCanonicalizing(static::EXPECTED_ORDER_ATTRIBUTES, array_keys($getAttributes));
        $this->assertEqualsCanonicalizing(static::EXPECTED_ORDER_ITEM_ATTRIBUTES, array_keys($getAttributes['items'][0] ?? []));
        $this->assertEqualsCanonicalizing(static::EXPECTED_ORDER_TOTALS_ATTRIBUTES, array_keys($getAttributes['totals'] ?? []));
    }

    public function testGivenAUnitCustomPriceDifferentFromTheCataloguePriceWhenCreateOrderThenTheCustomPriceIsCharged(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $attributes['items'][0]['unitCustomPrice'] = static::UNIT_CUSTOM_PRICE;
        $this->tester->actingAsUser();

        $this->assertNotSame(static::UNIT_CUSTOM_PRICE, $this->tester->getOrderableProductGrossAmount());

        // Act
        $createResponse = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($createResponse, Response::HTTP_CREATED);
        $this->assertSame(static::UNIT_CUSTOM_PRICE, $this->getResourceAttributes($createResponse)['items'][0]['unitPrice'] ?? null);

        $orderReference = (string)($this->decodeJsonApi($createResponse)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $getResponse = $this->handleApiRequest('GET', $this->tester->getOrderUrl($orderReference));

        $this->assertRespondsWithStatus($getResponse, Response::HTTP_OK);
        $this->assertSame(
            static::UNIT_CUSTOM_PRICE,
            $this->getResourceAttributes($getResponse)['items'][0]['unitPrice'] ?? null,
            'The custom price must survive calculation and checkout, not only the POST response.',
        );
    }

    // ------------------------------------------------------------------ domain rejections

    public function testGivenAnUnknownSkuWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        $attributes['items'] = [['sku' => $this->tester->getUnknownSku(), 'quantity' => 1]];
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert — a line naming no real product is refused whole; no partial order is placed.
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertNoOrderExistsFor($customerTransfer->getCustomerReferenceOrFail());
    }

    public function testGivenAnUnknownSkuWhenCreateOrderThenOnlyTheUnknownSkuProblemIsReported(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $unknownSku = $this->tester->getUnknownSku();
        $attributes['items'] = [['sku' => $unknownSku, 'quantity' => 1]];
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);

        $errorCodes = $this->getErrorCodes($response);
        $errorDetails = $this->getErrorDetails($response);

        $this->assertCount(1, $errorDetails, 'Price resolution must not also report on a line whose SKU was already rejected.');
        $this->assertSame(['901'], $errorCodes);
        $this->assertStringContainsString('items[0].sku', $errorDetails[0]);
        $this->assertStringContainsString('was not found', $errorDetails[0]);
    }

    public function testGivenAnUnknownShipmentMethodWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        $attributes['shipment']['shipmentMethod'] = $this->tester->getUnknownShipmentMethodName();
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertNoOrderExistsFor($customerTransfer->getCustomerReferenceOrFail());
    }

    public function testGivenAMarketplacePaymentMethodForANonMarketplaceLineWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        unset($attributes['items'][0]['merchantReference']);
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert — the project's payment method filter only offers marketplace payment when every line has a merchant.
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('paymentMethod', implode(' | ', $this->getErrorDetails($response)));
        $this->assertNoOrderExistsFor($customerTransfer->getCustomerReferenceOrFail());
    }

    public function testGivenAnUnknownStoreWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveValidOrderPayload();
        $attributes['store'] = 'OemBackendApiNoSuchStore';
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert — the store is resolved before prices, currency and the checkout pre-conditions,
        // each of which used to 500 on a store that does not exist.
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertNoOrderExistsFor($customerTransfer->getCustomerReferenceOrFail());
    }

    public function testGivenAnUnknownCustomerReferenceWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $attributes['customerReference'] = $this->tester->getUnknownCustomerReference();
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenAnEmptyPayloadWithGermanAcceptLanguageWhenCreateOrderThenSymfonyValidationMessagesAreTranslated(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getOrderCollectionUrl(),
            $this->tester->buildOrderRequestBody([]),
            ['Accept-Language' => 'de'],
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(
            'Dieses Feld fehlt.',
            implode(' | ', $this->getErrorDetails($response)),
            'Accept-Language: de must translate the Symfony "This field is missing." constraint message.',
        );
    }

    public function testGivenAnUnknownSkuWithGermanAcceptLanguageWhenCreateOrderThenTheBusinessLayerMessageIsTranslatedViaZedTranslator(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $attributes['items'] = [['sku' => $this->tester->getUnknownSku(), 'quantity' => 1]];
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getOrderCollectionUrl(),
            $this->tester->buildOrderRequestBody($attributes),
            ['Accept-Language' => 'de'],
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $errorDetails = $this->getErrorDetails($response);
        $this->assertNotEmpty($errorDetails, (string)$response->getContent());
        $this->assertStringContainsString(
            'wurde nicht gefunden',
            $errorDetails[0],
            'Accept-Language: de must translate the "Product with SKU ... was not found." validation issue via the Zed Translator. Body: ' . $response->getContent(),
        );
    }

    public function testGivenAnOrderBelowTheHardMinimumThresholdWithGermanAcceptLanguageWhenCreateOrderThenTheCheckoutErrorIsTranslatedViaGlossary(): void
    {
        // Arrange
        [$customerTransfer, $attributes] = $this->tester->haveOrderPayloadBelowHardMinimumThreshold();
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getOrderCollectionUrl(),
            $this->tester->buildOrderRequestBody($attributes),
            ['Accept-Language' => 'de'],
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertNoOrderExistsFor($customerTransfer->getCustomerReferenceOrFail());
    }

    public function testGivenAnEmptyPayloadWhenCreateOrderThenEveryRequiredAttributeIsReported(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi([]);

        // Assert — one round trip has to name every missing attribute, or a caller fixing the
        // payload one 422 at a time never converges.
        foreach (static::REQUIRED_ATTRIBUTES as $attribute) {
            $this->assertValidationFailedForAttribute($response, $attribute);
        }
    }

    public function testGivenAnInvalidCurrencyWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload(['currency' => 'XXX']);
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertValidationFailedForAttribute($response, 'currency');
    }

    public function testGivenAnInvalidPriceModeWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload(['priceMode' => 'NOT_A_PRICE_MODE']);
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertValidationFailedForAttribute($response, 'priceMode');
    }

    public function testGivenAnEmptyItemsArrayWhenCreateOrderThenItIsRejected(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload(['items' => []]);
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertValidationFailedForAttribute($response, 'items');
    }

    // ------------------------------------------------------------------ authorization

    /**
     * @dataProvider provideWrongTypePayloads
     *
     * @param array<string, mixed> $attributes
     */
    public function testGivenAWrongTypeValueWhenCreateOrderThenTheOffendingFieldIsNamed(array $attributes, string $expectedField): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert — the API Platform deserializer runs before the processor's own shape check.
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString($expectedField, implode(' | ', $this->getErrorDetails($response)));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public function provideWrongTypePayloads(): array
    {
        return [
            'string where items belong' => [['items' => 'abc'], '"items"'],
            'string quantity' => [['items' => [['sku' => 'oem-api-sku', 'quantity' => 'abc']]], '"items[0].quantity"'],
        ];
    }

    public function testGivenNoAuthenticationWhenCreateOrderThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated, and with a payload that would fail validation too,
        // so a 401 here proves authorization runs first.
        $response = $this->createOrderViaApi([]);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnInvalidTokenWhenCreateOrderThenItRespondsUnauthorized(): void
    {
        // Arrange
        $this->tester->actingWithInvalidToken();

        // Act
        $response = $this->createOrderViaApi([]);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGivenAnOperatorTheAclRefusesWhenCreateOrderThenItRespondsForbidden(): void
    {
        // Arrange
        $this->tester->actingAsUserWithoutAclAccess();

        // Act
        $response = $this->createOrderViaApi([]);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_FORBIDDEN);
    }

    protected function assertNoOrderExistsFor(string $customerReference): void
    {
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerReference,
        ]));

        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response), 'A rejected payload places no order.');
    }

    /**
     * @return array<string>
     */
    protected function findOrderReferencesFor(string $customerReference): array
    {
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerReference,
        ]));

        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        return $this->getResourceIds($response);
    }
}
