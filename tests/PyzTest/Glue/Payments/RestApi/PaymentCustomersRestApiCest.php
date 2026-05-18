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
 * @group PaymentCustomersRestApiCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class PaymentCustomersRestApiCest
{
    protected const string PAYMENT_PROVIDER_NAME = 'DummyPayment';

    protected const string PAYMENT_METHOD_NAME = 'Invoice';

    protected const string PSP_KEY = 'pspCustomerId';

    protected const string PSP_VALUE = 'cus_abc123';

    protected const string INVALID_TYPE = 'wrong-type';

    public function givenEmptyBodyWhenPostingPaymentCustomersThenReturnsBadRequest(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();

        $I->sendPOST($url, []);

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenInvalidTypeWhenPostingPaymentCustomersThenReturnsBadRequest(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();

        $I->sendPOST($url, $this->buildRequestPayload(static::INVALID_TYPE, $this->buildValidAttributes()));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenEmptyAttributesWhenPostingPaymentCustomersThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, []));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentWhenPostingPaymentCustomersThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingCustomerPaymentServiceProviderDataWhenPostingPaymentCustomersThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['customerPaymentServiceProviderData']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentProviderNameWhenPostingPaymentCustomersThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']['paymentProviderName']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentMethodNameWhenPostingPaymentCustomersThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']['paymentMethodName']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenValidRequestWhenPostingPaymentCustomersThenRouteExists(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, $this->buildValidAttributes()));

        $I->dontSeeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function givenValidRequestWhenPostingPaymentCustomersThenReturnsJsonApiResponse(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, $this->buildValidAttributes()));

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType([
            'errors' => 'array',
        ]);
    }

    public function givenEmptyAttributesWhenPostingPaymentCustomersThenReturnsJsonApiErrorShape(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentCustomersUrl();
        $requestPayload = $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENT_CUSTOMERS, []);

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
            'customerPaymentServiceProviderData' => [
                static::PSP_KEY => static::PSP_VALUE,
            ],
        ];
    }
}
