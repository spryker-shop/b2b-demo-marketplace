<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group UpdateCompanyBusinessUnitAddressBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnitAddresses
 */
class UpdateCompanyBusinessUnitAddressBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_ISO2_CODE = 'iso2Code';

    protected const string ATTRIBUTE_CITY = 'city';

    protected const string ATTRIBUTE_STREET = 'street';

    protected const string ATTRIBUTE_ZIP_CODE = 'zipCode';

    protected const string ATTRIBUTE_COMMENT = 'comment';

    protected const string ATTRIBUTE_LABELS = 'labels';

    protected const string LABEL_NAME = 'contact person';

    protected const string RENAMED_CITY = 'CxmPatchedCity';

    public function testGivenACityWhenUpdateAddressThenOnlyThatPropertyChanges(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $attributes = $this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
        ]);
        $uuid = $this->createAddress($attributes);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_CITY => static::RENAMED_CITY,
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $resourceAttributes = $this->getResourceAttributes($response);
        $this->assertSame(static::RENAMED_CITY, $resourceAttributes[static::ATTRIBUTE_CITY] ?? null);
        $this->assertSame(
            $attributes[static::ATTRIBUTE_STREET],
            $resourceAttributes[static::ATTRIBUTE_STREET] ?? null,
            'A partial update must leave the properties it does not mention untouched.',
        );
        $this->assertSame(
            $attributes[static::ATTRIBUTE_ZIP_CODE],
            $resourceAttributes[static::ATTRIBUTE_ZIP_CODE] ?? null,
        );
    }

    public function testGivenANewCountryWhenUpdateAddressThenTheCountryIsApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->createAddress($this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
            static::ATTRIBUTE_ISO2_CODE => 'DE',
        ]));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_ISO2_CODE => 'AT',
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame('AT', $this->getResourceAttributes($response)[static::ATTRIBUTE_ISO2_CODE] ?? null);

        $reread = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressUrl($uuid));
        $this->assertSame(
            'AT',
            $this->getResourceAttributes($reread)[static::ATTRIBUTE_ISO2_CODE] ?? null,
            'The new country is persisted, not only echoed back.',
        );
    }

    public function testGivenOptionalFieldsWhenUpdateAddressThenTheyAreApplied(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->createAddress($this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
        ]));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_COMMENT => 'Ring twice.',
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame('Ring twice.', $this->getResourceAttributes($response)[static::ATTRIBUTE_COMMENT] ?? null);
    }

    public function testGivenLabelsWhenUpdateAddressThenTheyReplaceTheCurrentSet(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->createAddress($this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
        ]));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_LABELS => [static::LABEL_NAME],
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([static::LABEL_NAME], $this->getResourceAttributes($response)[static::ATTRIBUTE_LABELS] ?? null);

        $reread = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressUrl($uuid));
        $this->assertSame(
            [static::LABEL_NAME],
            $this->getResourceAttributes($reread)[static::ATTRIBUTE_LABELS] ?? null,
            'The labels are persisted, not only echoed back.',
        );
    }

    public function testGivenAnEmptyLabelListWhenUpdateAddressThenEveryLabelIsDetached(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->createAddress($this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
            static::ATTRIBUTE_LABELS => [static::LABEL_NAME],
        ]));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_LABELS => [],
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceAttributes($response)[static::ATTRIBUTE_LABELS] ?? null);
    }

    public function testGivenALabelThatIsNotConfiguredWhenUpdateAddressThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->createAddress($this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
        ]));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_LABELS => ['CxmNoSuchLabelIsConfigured'],
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_VALIDATION,
        );
    }

    public function testGivenAnUnknownUuidWhenUpdateAddressThenNotFoundIsReturned(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_CITY => static::RENAMED_CITY,
            ], static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenABlankStreetWhenUpdateAddressThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->createAddress($this->tester->buildValidCompanyBusinessUnitAddressAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
        ]));

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl($uuid),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_STREET => '',
            ], $uuid),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGivenNoAuthenticationWhenUpdateAddressThenItRespondsUnauthorized(): void
    {
        // Act — intentionally unauthenticated.
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitAddressUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody([
                static::ATTRIBUTE_CITY => static::RENAMED_CITY,
            ], static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return non-empty-string
     */
    protected function createAddress(array $attributes): string
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitAddressCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitAddressRequestBody($attributes),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the address under test: %s', (string)$response->getContent()),
        );

        $uuid = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $this->assertNotSame('', $uuid);

        /** @var non-empty-string $uuid */
        return $uuid;
    }
}
