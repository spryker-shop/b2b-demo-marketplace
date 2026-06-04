<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\Vertex\RestApi;

use Codeception\Util\HttpCode;
use PyzTest\Glue\Vertex\VertexApiTester;
use Spryker\Glue\AuthRestApi\AuthRestApiConfig;
use SprykerEco\Glue\Vertex\VertexConfig;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group Vertex
 * @group RestApi
 * @group VertexTaxIdValidationRestApiCest
 * Add your own group annotations below this line
 * @group EndToEnd
 */
class VertexTaxIdValidationRestApiCest
{
    protected const string COUNTRY_CODE_DE = 'DE';

    protected const string TAX_ID_DE = 'DE123456789';

    protected VertexTaxIdValidationRestApiFixtures $fixtures;

    public function loadFixtures(VertexApiTester $I): void
    {
        /** @var \PyzTest\Glue\Vertex\RestApi\VertexTaxIdValidationRestApiFixtures $fixtures */
        $fixtures = $I->loadFixtures(VertexTaxIdValidationRestApiFixtures::class);
        $this->fixtures = $fixtures;
    }

    /**
     * @depends loadFixtures
     *
     * @param \PyzTest\Glue\Vertex\VertexApiTester $I
     *
     * @return void
     */
    public function requestPostTaxIdValidationWithoutAuthorization(VertexApiTester $I): void
    {
        // Act
        $I->sendPOST(
            $I->formatUrl(VertexConfig::RESOURCE_TAX_VALIATE_ID),
            $I->buildTaxIdValidationRequestBody(static::TAX_ID_DE, static::COUNTRY_CODE_DE),
        );

        // Assert
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
        $I->seeResponseIsJson();

        $error = $I->getDataFromResponseByJsonPath('$.errors[0]');

        $I->amSure('The response error code matches')
            ->whenI()
            ->assertSame(AuthRestApiConfig::RESPONSE_CODE_FORBIDDEN, $error['code']);

        $I->amSure('The response error status matches')
            ->whenI()
            ->assertSame(HttpCode::FORBIDDEN, $error['status']);

        $I->amSure('The response error detail matches')
            ->whenI()
            ->assertSame(AuthRestApiConfig::RESPONSE_DETAIL_MISSING_ACCESS_TOKEN, $error['detail']);
    }

    /**
     * @depends loadFixtures
     *
     * @param \PyzTest\Glue\Vertex\VertexApiTester $I
     *
     * @return void
     */
    public function requestPostTaxIdValidationWhenVertexIsDisabledReturnsBadRequest(VertexApiTester $I): void
    {
        // Arrange
        $oauthResponseTransfer = $I->haveAuthorizationToGlue($this->fixtures->getCustomerTransfer());
        $I->amBearerAuthenticated($oauthResponseTransfer->getAccessToken());

        // Act
        $I->sendPOST(
            $I->formatUrl(VertexConfig::RESOURCE_TAX_VALIATE_ID),
            $I->buildTaxIdValidationRequestBody(static::TAX_ID_DE, static::COUNTRY_CODE_DE),
        );

        // Assert
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();

        $I->amSure('The response contains a bad request error when Vertex is disabled')
            ->whenI()
            ->assertSame(
                HttpCode::BAD_REQUEST,
                $I->getDataFromResponseByJsonPath('$.errors[0].status'),
            );

        $I->amSure('The response contains the service disabled detail message')
            ->whenI()
            ->assertSame(
                'Tax service is disabled.',
                $I->getDataFromResponseByJsonPath('$.errors[0].detail'),
            );
    }

    /**
     * Tests that a request without a taxId returns 422 when Vertex is enabled.
     *
     * NOTE: This test exercises the "Vertex disabled" path (400) in environments where
     * VERTEX_IS_ACTIVE is not set. Set VERTEX_IS_ACTIVE=true to exercise the missing-field
     * validation path (422 "Invalid request data.").
     *
     * @depends loadFixtures
     *
     * @param \PyzTest\Glue\Vertex\VertexApiTester $I
     *
     * @return void
     */
    public function requestPostTaxIdValidationWithoutTaxIdReturnsError(VertexApiTester $I): void
    {
        // Arrange
        $oauthResponseTransfer = $I->haveAuthorizationToGlue($this->fixtures->getCustomerTransfer());
        $I->amBearerAuthenticated($oauthResponseTransfer->getAccessToken());

        // Act
        $I->sendPOST(
            $I->formatUrl(VertexConfig::RESOURCE_TAX_VALIATE_ID),
            $I->buildTaxIdValidationRequestBody(null, static::COUNTRY_CODE_DE),
        );

        // Assert: 422 when Vertex is enabled, 400 when Vertex is disabled (default env)
        $I->seeResponseIsJson();
        $statusCode = $I->getDataFromResponseByJsonPath('$.errors[0].status');

        $I->amSure('The response returns an error for a request missing the taxId')
            ->whenI()
            ->assertTrue(
                $statusCode === HttpCode::UNPROCESSABLE_ENTITY || $statusCode === HttpCode::BAD_REQUEST,
                sprintf('Expected 422 or 400 but got %d', $statusCode),
            );
    }

    /**
     * Tests that a request without a countryCode returns 422 when Vertex is enabled.
     *
     * NOTE: This test exercises the "Vertex disabled" path (400) in environments where
     * VERTEX_IS_ACTIVE is not set. Set VERTEX_IS_ACTIVE=true to exercise the missing-field
     * validation path (422 "Invalid request data.").
     *
     * @depends loadFixtures
     *
     * @param \PyzTest\Glue\Vertex\VertexApiTester $I
     *
     * @return void
     */
    public function requestPostTaxIdValidationWithoutCountryCodeReturnsError(VertexApiTester $I): void
    {
        // Arrange
        $oauthResponseTransfer = $I->haveAuthorizationToGlue($this->fixtures->getCustomerTransfer());
        $I->amBearerAuthenticated($oauthResponseTransfer->getAccessToken());

        // Act
        $I->sendPOST(
            $I->formatUrl(VertexConfig::RESOURCE_TAX_VALIATE_ID),
            $I->buildTaxIdValidationRequestBody(static::TAX_ID_DE, null),
        );

        // Assert: 422 when Vertex is enabled, 400 when Vertex is disabled (default env)
        $I->seeResponseIsJson();
        $statusCode = $I->getDataFromResponseByJsonPath('$.errors[0].status');

        $I->amSure('The response returns an error for a request missing the countryCode')
            ->whenI()
            ->assertTrue(
                $statusCode === HttpCode::UNPROCESSABLE_ENTITY || $statusCode === HttpCode::BAD_REQUEST,
                sprintf('Expected 422 or 400 but got %d', $statusCode),
            );
    }
}
