<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderPayments\RestApi;

use Codeception\Util\HttpCode;
use PyzTest\Glue\OrderPayments\OrderPaymentsApiTester;
use Spryker\Glue\OrderPaymentsRestApi\OrderPaymentsRestApiConfig;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group OrderPayments
 * @group RestApi
 * @group OrderPaymentsRestApiCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class OrderPaymentsRestApiCest
{
    protected const string PAYMENT_IDENTIFIER = 'test-payment-identifier';

    protected const string NON_EXISTENT_PAYMENT_IDENTIFIER = 'non-existent-payment-identifier';

    protected const string INVALID_TYPE = 'wrong-type';

    protected const string DATA_PAYLOAD_KEY = 'key';

    protected const string DATA_PAYLOAD_VALUE = 'value';

    public function givenEmptyBodyWhenPostingOrderPaymentsThenReturnsBadRequest(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();

        $I->sendPOST($url, []);

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenInvalidTypeWhenPostingOrderPaymentsThenReturnsBadRequest(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();
        $requestPayload = $this->buildRequestPayload(static::INVALID_TYPE, $this->buildValidAttributes());

        $I->sendPOST($url, $requestPayload);

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenEmptyAttributesWhenPostingOrderPaymentsThenReturnsUnprocessableEntity(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();
        $requestPayload = $this->buildRequestPayload(OrderPaymentsRestApiConfig::RESOURCE_ORDER_PAYMENTS, []);

        $I->sendPOST($url, $requestPayload);

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentIdentifierWhenPostingOrderPaymentsThenReturnsUnprocessableEntity(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['paymentIdentifier']);

        $I->sendPOST($url, $this->buildRequestPayload(OrderPaymentsRestApiConfig::RESOURCE_ORDER_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingDataPayloadWhenPostingOrderPaymentsThenReturnsUnprocessableEntity(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['dataPayload']);

        $I->sendPOST($url, $this->buildRequestPayload(OrderPaymentsRestApiConfig::RESOURCE_ORDER_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenNonExistentPaymentIdentifierWhenPostingOrderPaymentsThenReturnsUnprocessableEntity(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        $attributes['paymentIdentifier'] = static::NON_EXISTENT_PAYMENT_IDENTIFIER;

        $I->sendPOST($url, $this->buildRequestPayload(OrderPaymentsRestApiConfig::RESOURCE_ORDER_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
        $I->seeResponseMatchesJsonType([
            'errors' => 'array',
        ]);
    }

    public function givenValidRequestWhenPostingOrderPaymentsThenRouteExists(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(OrderPaymentsRestApiConfig::RESOURCE_ORDER_PAYMENTS, $this->buildValidAttributes()));

        $I->dontSeeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function givenValidRequestWhenPostingOrderPaymentsThenReturnsJsonApiErrorShape(OrderPaymentsApiTester $I): void
    {
        $url = $I->buildOrderPaymentsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(OrderPaymentsRestApiConfig::RESOURCE_ORDER_PAYMENTS, $this->buildValidAttributes()));

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType([
            'errors' => 'array',
        ]);
        $I->seeResponseMatchesJsonType([
            'status' => 'integer',
            'detail' => 'string',
        ], '$.errors[0]');
    }

    /**
     * @param string $type
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    protected function buildRequestPayload(string $type, array $attributes): array
    {
        return [
            'data' => [
                'type' => $type,
                'attributes' => $attributes,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildValidAttributes(): array
    {
        return [
            'paymentIdentifier' => static::PAYMENT_IDENTIFIER,
            'dataPayload' => [
                static::DATA_PAYLOAD_KEY => static::DATA_PAYLOAD_VALUE,
            ],
        ];
    }
}
