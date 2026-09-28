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
 * These tests pin what the API does in that window: the collection stays readable, and a business
 * unit that cannot be addressed reports that plainly instead of failing or matching on an empty
 * value.
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
    public function testGivenABusinessUnitWithoutAUuidWhenGetCollectionThenItStillAnswers(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $this->tester->clearCompanyBusinessUnitUuid($uuid);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus(
            $response,
            Response::HTTP_OK,
            'A business unit still waiting for uuid:generate must not break the collection.',
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
