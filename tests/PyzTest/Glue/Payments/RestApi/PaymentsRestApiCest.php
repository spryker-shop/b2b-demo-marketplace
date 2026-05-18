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
 * @group PaymentsRestApiCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class PaymentsRestApiCest
{
    protected const string PAYMENT_PROVIDER_NAME = 'DummyPayment';

    protected const string PAYMENT_METHOD_NAME = 'Invoice';

    protected const int PAYMENT_AMOUNT = 1000;

    protected const string CUSTOMER_FIRST_NAME = 'Sonia';

    protected const string CUSTOMER_LAST_NAME = 'Wagner';

    protected const string CUSTOMER_EMAIL = 'sonia@spryker.com';

    protected const string COUNTRY_CODE = 'DE';

    protected const string CURRENCY_CODE = 'EUR';

    protected const string INVALID_TYPE = 'wrong-type';

    public function givenEmptyBodyWhenPostingPaymentsThenReturnsBadRequest(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();

        $I->sendPOST($url, []);

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenInvalidTypeWhenPostingPaymentsThenReturnsBadRequest(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $requestPayload = $this->buildRequestPayload(static::INVALID_TYPE, $this->buildValidAttributes());

        $I->sendPOST($url, $requestPayload);

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function givenEmptyAttributesWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $requestPayload = $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, []);

        $I->sendPOST($url, $requestPayload);

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingQuoteWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['quote']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentProviderNameWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']['paymentProviderName']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingPaymentMethodNameWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']['paymentMethodName']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingAmountWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['payment']['amount']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenNonNumericAmountWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        $attributes['payment']['amount'] = 'not-a-number';

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenMissingQuoteCustomerWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        unset($attributes['quote']['customer']);

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenInvalidEmailWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        $attributes['quote']['customer']['email'] = 'not-an-email';

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenInvalidCountryCodeWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        $attributes['quote']['billingAddress']['iso2Code'] = 'ZZ';

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenInvalidCurrencyCodeWhenPostingPaymentsThenReturnsUnprocessableEntity(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $attributes = $this->buildValidAttributes();
        $attributes['quote']['currency']['code'] = 'ZZZ';

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $attributes));

        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseIsJson();
    }

    public function givenValidRequestWhenPostingPaymentsThenRouteExists(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $this->buildValidAttributes()));

        $I->dontSeeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->seeResponseIsJson();
    }

    public function givenValidRequestWhenPostingPaymentsThenReturnsJsonApiResponse(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();

        $I->sendPOST($url, $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, $this->buildValidAttributes()));

        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY);
        $I->seeResponseMatchesJsonType([
            'errors' => 'array',
        ]);
    }

    public function givenEmptyAttributesWhenPostingPaymentsThenReturnsJsonApiErrorShape(PaymentsApiTester $I): void
    {
        $url = $I->buildPaymentsUrl();
        $requestPayload = $this->buildRequestPayload(PaymentsRestApiConfig::RESOURCE_TYPE_PAYMENTS, []);

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
                'amount' => static::PAYMENT_AMOUNT,
            ],
            'quote' => [
                'customer' => [
                    'firstName' => static::CUSTOMER_FIRST_NAME,
                    'lastName' => static::CUSTOMER_LAST_NAME,
                    'email' => static::CUSTOMER_EMAIL,
                ],
                'billingAddress' => [
                    'iso2Code' => static::COUNTRY_CODE,
                ],
                'currency' => [
                    'code' => static::CURRENCY_CODE,
                ],
            ],
        ];
    }
}
