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
 * @group DeleteCompanyBusinessUnitBackendApiTest
 * Add your own group annotations below this line
 * @group CompanyBusinessUnits
 */
class DeleteCompanyBusinessUnitBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string UNKNOWN_UUID = '11111111-2222-4333-8444-555555555555';

    public function testGivenABusinessUnitWhenDeleteThenItIsGoneFromTheApi(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $uuid = $this->haveCompanyBusinessUnitViaApi();

        // Act
        $response = $this->handleApiRequest('DELETE', $this->tester->getCompanyBusinessUnitUrl($uuid));

        // Assert
        $this->assertContains(
            $response->getStatusCode(),
            [Response::HTTP_NO_CONTENT, Response::HTTP_OK],
            'A delete answers with no content.',
        );
        $this->assertRespondsWithErrorCode(
            $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitUrl($uuid)),
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }

    /**
     * The core clears the parent of every unit that reported to the deleted one, rather than
     * refusing the delete or cascading it.
     */
    public function testGivenAParentWhenDeleteThenItsChildrenLoseTheParentAndSurvive(): void
    {
        // Arrange
        $this->tester->actingAsUser();
        $companyUuid = $this->haveCompanyViaApi();
        $parentUuid = $this->haveCompanyBusinessUnitViaApi([static::ATTRIBUTE_COMPANY_UUID => $companyUuid]);
        $childUuid = $this->haveCompanyBusinessUnitViaApi([
            static::ATTRIBUTE_COMPANY_UUID => $companyUuid,
            static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID => $parentUuid,
        ]);

        // Act
        $this->handleApiRequest('DELETE', $this->tester->getCompanyBusinessUnitUrl($parentUuid));

        // Assert
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyBusinessUnitUrl($childUuid));
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertNull(
            $this->getResourceAttributes($response)[static::ATTRIBUTE_PARENT_BUSINESS_UNIT_UUID] ?? null,
            'A child of the deleted business unit is left without a parent.',
        );
    }

    public function testGivenAnUnknownBusinessUnitWhenDeleteThenItIsReportedNotFound(): void
    {
        // Arrange
        $this->tester->actingAsUser();

        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCompanyBusinessUnitUrl(static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_NOT_FOUND,
            CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_BUSINESS_UNIT_NOT_FOUND,
        );
    }

    public function testGivenNoAccessTokenWhenDeleteThenItIsUnauthorized(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'DELETE',
            $this->tester->getCompanyBusinessUnitUrl(static::UNKNOWN_UUID),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_UNAUTHORIZED);
    }
}
