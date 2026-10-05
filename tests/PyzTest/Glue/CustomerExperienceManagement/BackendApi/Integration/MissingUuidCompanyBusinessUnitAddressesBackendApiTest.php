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
 * The `uuid` column on `spy_company_unit_address` ships with this feature, so a project that
 * installs it over a populated database has addresses whose uuid is empty until
 * `console uuid:generate CompanyUnitAddress spy_company_unit_address` has run.
 *
 * These tests pin what the API does in that window. An address with no uuid has no JSON:API
 * identity, so any response that would have to carry it fails with error code 012, which names the
 * back-fill as the remedy, instead of dropping the row silently or reporting an opaque IRI error.
 * An address addressed directly by its former uuid is reported not found.
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group MissingUuidCompanyBusinessUnitAddressesBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnitAddresses
 */
class MissingUuidCompanyBusinessUnitAddressesBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    public function testGivenAnAddressWithoutAUuidWhenGetCollectionThenItReportsTheMissingBackfill(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $this->tester->clearCompanyUnitAddressUuid($uuid);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressCollectionUrl());

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            static::RESPONSE_CODE_RESOURCE_IDENTIFIER_MISSING,
        );
    }

    public function testGivenAnAddressWithoutAUuidWhenFilteringByCompanyThenItStillReportsTheMissingBackfill(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $uuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $this->tester->clearCompanyUnitAddressUuid($uuid);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyBusinessUnitAddressCollectionUrl() . sprintf(
                '?filter[company-business-unit-addresses.companyUuid]=%s',
                $companyUuid,
            ),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            static::RESPONSE_CODE_RESOURCE_IDENTIFIER_MISSING,
        );
    }

    public function testGivenAnAddressWithoutAUuidWhenGetByItsFormerUuidThenItIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitAddressViaApi();
        $this->tester->clearCompanyUnitAddressUuid($uuid);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitAddressUrl($uuid));

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
    }

    public function testGivenAnAddressWithoutAUuidWhenAssigningItToABusinessUnitThenItIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $businessUnitUuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $addressUuid = $this->haveCompanyBusinessUnitAddressViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $this->tester->clearCompanyUnitAddressUuid($addressUuid);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyBusinessUnitUrl($businessUnitUuid),
            $this->tester->buildCompanyBusinessUnitRequestBody([
                static::ATTRIBUTE_ADDRESS_UUIDS => [$addressUuid],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_UNIT_ADDRESS_NOT_FOUND,
        );
    }
}
