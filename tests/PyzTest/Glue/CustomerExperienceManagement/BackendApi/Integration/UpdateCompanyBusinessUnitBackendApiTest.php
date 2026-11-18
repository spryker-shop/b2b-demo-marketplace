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
 * @group UpdateCompanyBusinessUnitBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnits
 */
class UpdateCompanyBusinessUnitBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string UNKNOWN_UUID = '11111111-2222-4333-8444-555555555555';

    protected const string SECOND_UNKNOWN_UUID = '66666666-7777-4888-8999-aaaaaaaaaaaa';

    protected const string RENAMED = 'CxmBusinessUnitRenamed';

    public function testGivenANewNameWhenUpdateBusinessUnitThenOnlyTheNameChanges(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                CompanyBusinessUnitTransfer::NAME => static::RENAMED,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertAttributesMatch(
            [
                CompanyBusinessUnitTransfer::NAME => static::RENAMED,
                static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
            ],
            $this->getResourceAttributes($response),
            'The owning company is untouched by a rename.',
        );
    }

    /**
     * The address saver runs on every save and unassigns whatever the payload does not name, so a
     * rename must not be read as "assign no addresses".
     */
    public function testGivenAnUpdateWithoutAddressUuidsWhenUpdateBusinessUnitThenAssignmentsSurvive(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();
        $addressUuids = $this->getResourceAttributes(
            $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitUrl($uuid)),
        )[static::ATTRIBUTE_ADDRESS_UUIDS] ?? [];

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                CompanyBusinessUnitTransfer::NAME => static::RENAMED,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $addressUuids,
            $this->getResourceAttributes($response)[static::ATTRIBUTE_ADDRESS_UUIDS] ?? null,
            'An update that says nothing about addresses must leave the assignment as it was.',
        );
    }

    public function testGivenANullParentWhenUpdateBusinessUnitThenTheParentIsDetached(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $parentUuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $uuid = $this->haveCompanyBusinessUnitViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
            static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => $parentUuid,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => null,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNull(
            $this->getResourceAttributes($response)[static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID] ?? null,
            'An explicit null detaches the business unit from its parent.',
        );
    }

    public function testGivenTheOwningCompanyWhenUpdateBusinessUnitThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_COMPANY_UUID => $this->haveCompanyViaApi(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    public function testGivenItselfAsParentWhenUpdateBusinessUnitThenTheCycleIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => $uuid,
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_HIERARCHY_CYCLE,
        );
    }

    public function testGivenSeveralAddressesWhenUpdateBusinessUnitThenAllOfThemAreAssigned(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $firstAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);
        $secondAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);
        $thirdAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [$firstAddressUuid, $secondAddressUuid, $thirdAddressUuid],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $assignedAddressUuids = $this->getResourceAttributes($response)[static::ATTRIBUTE_ADDRESS_UUIDS] ?? [];
        $this->assertEqualsCanonicalizing(
            [$firstAddressUuid, $secondAddressUuid, $thirdAddressUuid],
            $assignedAddressUuids,
            'Every address named in the payload is assigned, not just the first.',
        );
    }

    public function testGivenOneUnknownAddressAmongKnownOnesWhenUpdateBusinessUnitThenItIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $knownAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [$knownAddressUuid, static::UNKNOWN_UUID],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
        $this->assertStringContainsString(
            static::UNKNOWN_UUID,
            (string)$response->getContent(),
            'The error names the uuid that could not be resolved.',
        );
    }

    public function testGivenSeveralUnknownAddressesWhenUpdateBusinessUnitThenEveryOneIsReported(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $knownAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
        ]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [
                    static::UNKNOWN_UUID,
                    $knownAddressUuid,
                    static::SECOND_UNKNOWN_UUID,
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
        $content = (string)$response->getContent();
        $this->assertStringContainsString(static::UNKNOWN_UUID, $content);
        $this->assertStringContainsString(
            static::SECOND_UNKNOWN_UUID,
            $content,
            'Every unresolvable uuid is reported, not only the first one encountered.',
        );
        $this->assertStringNotContainsString(
            $knownAddressUuid,
            $content,
            'An address that resolved is not reported as an error.',
        );
    }

    public function testGivenAnUnknownAddressWhenUpdateBusinessUnitThenTheAddressIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [static::UNKNOWN_UUID],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenAnAddressOfAnotherCompanyWhenUpdateThenItIsRejected(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();
        $foreignAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [$foreignAddressUuid],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_COMPANY_MISMATCH,
        );
    }

    public function testGivenAnUnknownAndAForeignAddressWhenUpdateThenBothAreReported(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $foreignAddressUuid = $this->haveCompanyBusinessUnitAddressViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [$foreignAddressUuid, static::UNKNOWN_UUID],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $errorCodes = array_column($this->decodeJsonApi($response)['errors'] ?? [], 'code');
        $this->assertSame(
            [
                CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_COMPANY_MISMATCH,
                CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
            ],
            $errorCodes,
            'Each rejected entry is reported with its own reason, in the order the entries were submitted.',
        );
    }

    public function testGivenAMalformedAddressUuidWhenUpdateThenTheSchemaRejectsTheRequest(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [static::UNKNOWN_UUID, 'not-a-uuid'],
            ]),
        );

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertStringContainsString(
            'addressUuids.1',
            (string)$response->getContent(),
            'The schema names the position of the malformed entry.',
        );
    }

    public function testGivenAnUnknownBusinessUnitWhenUpdateThenItIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl(static::UNKNOWN_UUID),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                CompanyBusinessUnitTransfer::NAME => static::RENAMED,
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }
}
