<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CreateCompanyBusinessUnitAddressBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnitAddresses
 */
class CreateCompanyBusinessUnitAddressBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_ISO2_CODE = 'iso2Code';

    protected const string ATTRIBUTE_CITY = 'city';

    protected const string ATTRIBUTE_LABELS = 'labels';

    protected const string LABEL_NAME = 'contact person';

    public function testGivenAValidAddressWhenCreateThenTheResponseCarriesTheDerivedFields(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $attributes = $this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitAddressCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody($attributes),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            (string)$response->getContent(),
        );

        $resourceAttributes = $this->getResourceAttributes($response);
        $this->assertSame(
            $companyUuid,
            $resourceAttributes[static::ATTRIBUTE_COMPANY_UUID] ?? null,
            'The owning company is exposed by uuid, which is read from the company relation.',
        );
        $this->assertSame(
            $attributes[static::ATTRIBUTE_ISO2_CODE],
            $resourceAttributes[static::ATTRIBUTE_ISO2_CODE] ?? null,
            'The country code is derived from the country relation, not stored on the address row.',
        );
        $this->assertSame(
            $attributes[static::ATTRIBUTE_CITY],
            $resourceAttributes[static::ATTRIBUTE_CITY] ?? null,
        );
    }

    public function testGivenLabelsWhenCreateAddressThenTheResponseEchoesThem(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
            static::ATTRIBUTE_LABELS => [static::LABEL_NAME],
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitAddressCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody($attributes),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            (string)$response->getContent(),
        );
        $uuid = $this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID];
        $reread = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressUrl($uuid));

        $this->assertSame(
            [static::LABEL_NAME],
            $this->getResourceAttributes($reread)[static::ATTRIBUTE_LABELS] ?? null,
            'The labels are persisted and readable.',
        );
        $this->assertSame(
            [static::LABEL_NAME],
            $this->getResourceAttributes($response)[static::ATTRIBUTE_LABELS] ?? null,
            'The labels attached by the write are answered back to the caller.',
        );
    }

    public function testGivenAnUpdateWhenPatchAddressThenTheResponseStillCarriesTheDerivedFields(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_CITY => 'CxmPatchedCity',
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $resourceAttributes = $this->getResourceAttributes($response);
        $this->assertSame('CxmPatchedCity', $resourceAttributes[static::ATTRIBUTE_CITY] ?? null);
        $this->assertSame(
            $companyUuid,
            $resourceAttributes[static::ATTRIBUTE_COMPANY_UUID] ?? null,
            'The owning company is still exposed after an update.',
        );
        $this->assertNotNull(
            $resourceAttributes[static::ATTRIBUTE_ISO2_CODE] ?? null,
            'The country code is still derived after an update.',
        );
    }
}
