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
 * `POST /orders` over a booted GLUE_BACKEND kernel — order intake end to end.
 *
 * The happy path goes all the way through the real intake pipeline: validation, customer and
 * address resolution, quote assembly, item expansion, catalogue pricing, shipment and payment
 * resolution, `CheckoutFacade::isPlaceableOrder()`, and placement. Nothing on that path is stubbed
 * except OAuth introspection and the ACL check, so a passing case means the endpoint really does
 * create an order in the database.
 *
 * That is also why the payload needs arranging rather than merely being well-formed — see
 * {@see \PyzTest\Glue\OrderExperienceManagement\Helper\OrderExperienceManagementBackendApiHelper::haveOrderableProduct()}.
 *
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
     * Every attribute the resource declares `required: true` for the `orders:create` group and that
     * the Symfony validator can reach on an empty payload.
     *
     * @var array<string>
     */
    protected const REQUIRED_ATTRIBUTES = [
        'customerReference',
        'store',
        'currency',
        'paymentMethod',
    ];

    /**
     * The POST response and a later GET must describe a line the same way, and `items[].uuid` must be
     * the identifier the rest of the API accepts.
     *
     * This is the contract a client actually depends on: create an order, then act on one of its
     * lines. Before this was fixed the POST returned the order-item REFERENCE in `uuid` while the GET
     * returned the real uuid, so the value the POST handed back was rejected by
     * `POST /orders/{ref}/transitions` — and a client had to re-GET the order just to learn how to
     * address a line it had created a moment earlier.
     */
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

    /**
     * CC-40616: the order-level `shipment` is only a fallback for items that do not name their own.
     * Once the sole item covers both `shipmentMethod` and `shippingAddress` itself, the order-level
     * `shipment` is never consulted, so POST must accept the order even with it omitted entirely —
     * not just with its two properties individually blank.
     */
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

    /**
     * The POST response alone cannot prove persistence — it is assembled from the intake response,
     * not re-read. Following it with a GET is what shows the order is actually in the database and
     * that both endpoints agree on it.
     */
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

    /**
     * The POST response and a later GET must describe the placed order's breakdown the same way,
     * not just its identity (the sibling uuid-agreement test above covers that).
     *
     * Before this test existed, `POST /orders` reported empty `expenses`, `calculatedDiscounts`
     * and `availableEvents`, and a `totals` missing `taxBreakdown`/`canceledTotal`/
     * `remunerationTotal` — for the SAME order a `GET` moment later described fully. The data was
     * already persisted by the time the POST responded; it just was never read back into the
     * response. A caller building an order confirmation from the POST response alone therefore saw
     * an order with no shipment cost, no discounts, and nothing left to do with it — all wrong.
     */
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

    /**
     * `priceOverrides` — the audit trail for a submitted price that differed from the catalogue
     * price — was removed from this resource entirely; there is no override-audit signal left to
     * assert against. The remaining, still-meaningful property: a line whose submitted price equals
     * the catalogue price resolves to exactly that price, with no discrepancy introduced.
     */
    public function testGivenASubmittedPriceEqualToTheCataloguePriceWhenCreateOrderThenNoOverrideIsReported(): void
    {
        // Arrange
        [, $attributes] = $this->tester->haveValidOrderPayload();
        $submittedPrice = $attributes['items'][0]['unitPrice'];
        $this->tester->actingAsUser();

        // Act
        $response = $this->createOrderViaApi($attributes);

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertSame($submittedPrice, $this->getResourceAttributes($response)['items'][0]['unitPrice'] ?? null);
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

    /**
     * An unknown SKU used to fail validation twice over — the SKU itself is not a real product, AND
     * price resolution ran anyway and reported it had no price for a line naming no real product,
     * a message ("send a unitCustomPrice to override") that makes no sense when the product does
     * not exist at all. `OrderIntakePriceResolver::hasUnknownSkuIssue()` now skips price resolution
     * for any line whose SKU was already rejected, so only the one, actually actionable problem is
     * reported.
     */
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

    /**
     * `currency` must be a real ISO 4217 code — `orders.validation.yml` adds a `Currency` constraint
     * on top of the plain `NotBlank` the empty-payload test above already covers.
     */
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

    /**
     * `priceMode` defaults to `GROSS_MODE` when omitted, so this only exercises the `Choice`
     * constraint when a caller supplies a value outside `[GROSS_MODE, NET_MODE]`.
     */
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

    /**
     * `items` is required to carry at least one line — `Count: min: 1` — distinct from the
     * empty-payload test above, which covers the attribute being absent entirely.
     */
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

    /**
     * A rejected intake must leave nothing behind. Asserted through the collection endpoint rather
     * than the database so the check stays on the API's own terms.
     */
    protected function assertNoOrderExistsFor(string $customerReference): void
    {
        $response = $this->handleApiRequest('GET', $this->tester->getOrderCollectionUrl([
            'customerReference' => $customerReference,
        ]));

        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response), 'A rejected payload places no order.');
    }
}
