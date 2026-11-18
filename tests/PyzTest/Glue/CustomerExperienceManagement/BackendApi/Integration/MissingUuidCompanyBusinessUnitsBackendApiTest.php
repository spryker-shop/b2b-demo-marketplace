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
 * The `uuid` column on `spy_company_business_unit` ships with this feature, so a project that
 * installs it over a populated database has business units whose uuid is empty until
 * `console uuid:generate CompanyBusinessUnit spy_company_business_unit` has run.
 *
 * These tests pin what the API does in that window. A business unit with no uuid has no JSON:API
 * identity, so any response that would have to carry it fails with error code 012, which names the
 * back-fill as the remedy, instead of dropping the row silently or reporting an opaque IRI error.
 * A business unit addressed directly by its former uuid is reported not found.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group MissingUuidCompanyBusinessUnitsBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnits
 */
class MissingUuidCompanyBusinessUnitsBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenABusinessUnitWithoutAUuidWhenGetCollectionThenItReportsTheMissingBackfill(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $this->tester->clearCompanyBusinessUnitUuid($uuid);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl());

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            static::RESPONSE_CODE_RESOURCE_IDENTIFIER_MISSING,
        );
    }

    public function testGivenABusinessUnitWithoutAUuidWhenGetByItsFormerUuidThenItIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();
        $this->tester->clearCompanyBusinessUnitUuid($uuid);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitUrl($uuid));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }

    public function testGivenABusinessUnitWithoutAUuidWhenUpdateByItsFormerUuidThenItIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();
        $this->tester->clearCompanyBusinessUnitUuid($uuid);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($uuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([CompanyBusinessUnitTransfer::NAME => 'Renamed']),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }
}
