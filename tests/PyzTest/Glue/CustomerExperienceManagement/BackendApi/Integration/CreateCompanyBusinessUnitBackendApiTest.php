<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration;

use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
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
 * @group CreateCompanyBusinessUnitBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnits
 */
class CreateCompanyBusinessUnitBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const int NAME_MAX_LENGTH = 100;

    protected const int PHONE_MAX_LENGTH = 20;

    protected const string UNKNOWN_UUID = '11111111-2222-4333-8444-555555555555';

    public function testGivenANameAndCompanyWhenCreateBusinessUnitThenItBelongsToThatCompany(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $attributes = $this->tester->buildValidCompanyBusinessUnitAttributes([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertAttributesMatch(
            [
                CompanyBusinessUnitTransfer::NAME => $attributes[CompanyBusinessUnitTransfer::NAME],
                static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                static::ATTRIBUTE_ADDRESS_UUIDS => [],
            ],
            $this->getResourceAttributes($response),
            'A new business unit belongs to the company it named and starts with no addresses.',
        );
    }

    public function testGivenAParentOfTheSameCompanyWhenCreateBusinessUnitThenTheParentIsAssigned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $parentUuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody(
                $this->tester->buildValidCompanyBusinessUnitAttributes([
                    static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                    static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => $parentUuid,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $this->assertAttributesMatch(
            [static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => $parentUuid],
            $this->getResourceAttributes($response),
        );
    }

    /**
     * The Back Office parent selector only offers units of the chosen company, and the core writer
     * does not check it, so the API has to.
     */
    public function testGivenAParentOfAnotherCompanyWhenCreateBusinessUnitThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $parentUuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody(
                $this->tester->buildValidCompanyBusinessUnitAttributes([
                    static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
                    static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => $parentUuid,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_PARENT_COMPANY_BUSINESS_UNIT_COMPANY_MISMATCH,
        );
    }

    public function testGivenAddressesOfTheSameCompanyWhenCreateBusinessUnitThenTheyAreAssigned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $firstAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);
        $secondAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody(
                $this->tester->buildValidCompanyBusinessUnitAttributes([
                    static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
                    static::ATTRIBUTE_ADDRESS_UUIDS => [$firstAddressUuid, $secondAddressUuid],
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
        $assignedAddressUuids = $this->getResourceAttributes($response)[static::ATTRIBUTE_ADDRESS_UUIDS] ?? [];
        sort($assignedAddressUuids);
        $expectedAddressUuids = [$firstAddressUuid, $secondAddressUuid];
        sort($expectedAddressUuids);
        $this->assertSame(
            $expectedAddressUuids,
            $assignedAddressUuids,
            'A business unit created with addressUuids is assigned exactly those addresses.',
        );
    }

    public function testGivenAnAddressOfAnotherCompanyWhenCreateBusinessUnitThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $foreignAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody(
                $this->tester->buildValidCompanyBusinessUnitAttributes([
                    static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
                    static::ATTRIBUTE_ADDRESS_UUIDS => [$foreignAddressUuid],
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_COMPANY_MISMATCH,
        );
    }

    public function testGivenAnUnknownAddressWhenCreateBusinessUnitThenTheAddressIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody(
                $this->tester->buildValidCompanyBusinessUnitAttributes([
                    static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
                    static::ATTRIBUTE_ADDRESS_UUIDS => [static::UNKNOWN_UUID],
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenAnUnknownCompanyWhenCreateBusinessUnitThenTheCompanyIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody(
                $this->tester->buildValidCompanyBusinessUnitAttributes([
                    static::ATTRIBUTE_COMPANY_UUID => static::UNKNOWN_UUID,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_NOT_FOUND,
        );
    }

    public function testGivenAnUnknownParentWhenCreateBusinessUnitThenTheParentIsReportedSeparately(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody(
                $this->tester->buildValidCompanyBusinessUnitAttributes([
                    static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
                    static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => static::UNKNOWN_UUID,
                ]),
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_PARENT_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }

    /**
     * @dataProvider providePayloadsTheValidationSchemaRejects
     *
     * @param array<string, mixed> $attributes
     */
    public function testGivenAPayloadTheSchemaRejectsWhenCreateBusinessUnitThenItIsUnprocessable(
        array $attributes,
    ): void {
        // Arrange
        $this->tester->actingAsUser();
        $attributes[static::ATTRIBUTE_COMPANY_UUID] ??= $this->haveCompanyViaApi();

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public function providePayloadsTheValidationSchemaRejects(): array
    {
        return [
            'no name' => [[]],
            'blank name' => [[CompanyBusinessUnitTransfer::NAME => '']],
            'name over the length limit' => [[
                CompanyBusinessUnitTransfer::NAME => str_repeat('a', static::NAME_MAX_LENGTH + 1),
            ]],
            'phone over the length limit' => [[
                CompanyBusinessUnitTransfer::NAME => 'CxmBuLongPhone',
                CompanyBusinessUnitTransfer::PHONE => str_repeat('9', static::PHONE_MAX_LENGTH + 1),
            ]],
            'companyUuid that is not a uuid' => [[
                CompanyBusinessUnitTransfer::NAME => 'CxmBuBadCompanyUuid',
                'companyUuid' => 'not-a-uuid',
            ]],
            'addressUuids holding something that is not a uuid' => [[
                CompanyBusinessUnitTransfer::NAME => 'CxmBuBadAddressUuid',
                static::ATTRIBUTE_ADDRESS_UUIDS => ['not-a-uuid'],
            ]],
            'addressUuids that is not an array' => [[
                CompanyBusinessUnitTransfer::NAME => 'CxmBuAddressUuidsNotArray',
                static::ATTRIBUTE_ADDRESS_UUIDS => 'not-an-array',
            ]],
        ];
    }

    public function testGivenNoAccessTokenWhenCreateBusinessUnitThenItIsUnauthorized(): void
    {
        // Arrange
        $attributes = $this->tester->buildValidCompanyBusinessUnitAttributes([
            static::ATTRIBUTE_COMPANY_UUID => static::UNKNOWN_UUID,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyBusinessUnitCollectionUrl(),
            $this->tester->buildCompanyBusinessUnitRequestBody($attributes),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
