<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Payments\RestApi;

use Codeception\Util\HttpCode;
use PyzTest\Glue\Payments\PaymentsApiTester;
use Spryker\Glue\PaymentsRestApi\PaymentsRestApiConfig;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Payments
 * @group RestApi
 * @group PaymentCancellationsRestApiCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class PaymentCancellationsRestApiCest
{
    protected const string PAYMENT_PROVIDER_NAME = 'DummyPayment';

    protected const string PAYMENT_METHOD_NAME = 'Invoice';

    protected const string TRANSACTION_ID = 'tx_abc123';

    protected const string INVALID_TYPE = 'wrong-type';

    public function givenEmptyBodyWhenPostingPaymentCancellationsThenReturnsBadRequest(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();

        $I->sendPOST($url, []);

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenInvalidTypeWhenPostingPaymentCancellationsThenReturnsBadRequest(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(static::INVALID_TYPE, $this->buildValidAttributes()));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenEmptyAttributesWhenPostingPaymentCancellationsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, []));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentWhenPostingPaymentCancellationsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPreOrderPaymentDataWhenPostingPaymentCancellationsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['preOrderPaymentData']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentProviderNameWhenPostingPaymentCancellationsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']['paymentProviderName']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentMethodNameWhenPostingPaymentCancellationsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']['paymentMethodName']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingTransactionIdWhenPostingPaymentCancellationsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['preOrderPaymentData']['transactionId']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenValidRequestWhenPostingPaymentCancellationsThenRouteExists(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, $this->buildValidAttributes()));

        $I->dontSeeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function givenValidRequestWhenPostingPaymentCancellationsThenReturnsJsonApiResponse(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, $this->buildValidAttributes()));

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType([
            'errors' => 'array',
        ]);
    }

    public function givenEmptyAttributesWhenPostingPaymentCancellationsThenReturnsJsonApiErrorShape(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCancellationsUrl();
        $requestPayload = $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CANCELLATIONS, []);

        $I->sendPOST($url, $requestPayload);

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
            'payment' => [
                'paymentProviderName' => static::PAYMENT_PROVIDER_NAME,
                'paymentMethodName' => static::PAYMENT_METHOD_NAME,
            ],
            'preOrderPaymentData' => [
                'transactionId' => static::TRANSACTION_ID,
            ],
        ];
    }
}
