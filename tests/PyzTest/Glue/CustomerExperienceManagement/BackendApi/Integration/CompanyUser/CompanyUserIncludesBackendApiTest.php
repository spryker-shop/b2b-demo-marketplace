<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyUser;

use Generated\Shared\Transfer\CompanyUserTransfer;
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
 * @group CompanyUser
 * @group CompanyUserIncludesBackendApiTest
 * Add your own group annotations below this line
 */
class CompanyUserIncludesBackendApiTest extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string INCLUDE_COMPANIES_AND_COMPANY_BUSINESS_UNITS = 'companies,company-business-units';

    protected const string RESOURCE_TYPE_COMPANIES = 'companies';

    protected const string RESOURCE_TYPE_COMPANY_BUSINESS_UNITS = 'company-business-units';

    protected const string FIRST_NAME_FIRST_COMPANY = 'Ada';

    protected const string FIRST_NAME_SECOND_COMPANY = 'Grace';

    public function testGivenCompanyUsersOfTwoCompaniesWhenSearchWithIncludesThenEachCompanyUserLinksItsOwnCompanyAndBusinessUnit(): void
    {
        // Arrange
        $listedLastName = $this->tester->getListedCompanyUserLastName();
        $firstCompanyUserTransfer = $this->haveCompanyUserInOwnCompany(static::FIRST_NAME_FIRST_COMPANY, $listedLastName);
        $secondCompanyUserTransfer = $this->haveCompanyUserInOwnCompany(static::FIRST_NAME_SECOND_COMPANY, $listedLastName);
        $this->tester->actingAsUser();

        // Act
        $response = $this->tester->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
            'include' => static::INCLUDE_COMPANIES_AND_COMPANY_BUSINESS_UNITS,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $this->getExpectedRelationshipIds([$firstCompanyUserTransfer, $secondCompanyUserTransfer]),
            $this->getRelationshipIdsByMemberId($response),
        );
        $this->assertSame(2, $this->getMetaPagination($response)[static::PAGINATION_KEY_NUM_FOUND]);
    }

    public function testGivenTwoCompanyUsersOfOneCompanyWhenSearchWithIncludesThenBothLinkTheCompanyWhichIsIncludedOnce(): void
    {
        // Arrange
        [$firstCompanyUserTransfer, $secondCompanyUserTransfer, $listedLastName] = $this->haveTwoListedCompanyUsers();
        $this->tester->actingAsUser();

        // Act
        $response = $this->tester->handleApiRequest('GET', $this->tester->getCompanyUserCollectionUrl([
            'q' => $listedLastName,
            'include' => static::INCLUDE_COMPANIES_AND_COMPANY_BUSINESS_UNITS,
        ]));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            $this->getExpectedRelationshipIds([$firstCompanyUserTransfer, $secondCompanyUserTransfer]),
            $this->getRelationshipIdsByMemberId($response),
        );
        $this->assertSame(
            [$firstCompanyUserTransfer->getCompanyOrFail()->getUuidOrFail()],
            array_column($this->findIncludedResources($response, static::RESOURCE_TYPE_COMPANIES), static::JSON_API_KEY_ID),
        );
    }

    protected function haveCompanyUserInOwnCompany(string $firstName, string $lastName): CompanyUserTransfer
    {
        $companyContext = $this->tester->haveCompanyContext();

        return $this->tester->haveCompanyUserFor(
            $companyContext['company'],
            $companyContext['businessUnit'],
            $companyContext['role'],
            $firstName,
            $lastName,
        );
    }

    /**
     * @param list<\Generated\Shared\Transfer\CompanyUserTransfer> $companyUserTransfers
     *
     * @return array<string, array<string, list<string>>>
     */
    protected function getExpectedRelationshipIds(array $companyUserTransfers): array
    {
        $relationshipIds = [];

        foreach ($companyUserTransfers as $companyUserTransfer) {
            $relationshipIds[$companyUserTransfer->getUuidOrFail()] = [
                static::RESOURCE_TYPE_COMPANIES => [$companyUserTransfer->getCompanyOrFail()->getUuidOrFail()],
                static::RESOURCE_TYPE_COMPANY_BUSINESS_UNITS => [$companyUserTransfer->getCompanyBusinessUnitOrFail()->getUuidOrFail()],
            ];
        }

        ksort($relationshipIds);

        return $relationshipIds;
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    protected function getRelationshipIdsByMemberId(Response $response): array
    {
        $relationshipIds = [];

        foreach ($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA] as $member) {
            foreach ([static::RESOURCE_TYPE_COMPANIES, static::RESOURCE_TYPE_COMPANY_BUSINESS_UNITS] as $relationshipName) {
                $relationshipIds[$member[static::JSON_API_KEY_ID]][$relationshipName] = array_column(
                    $member[static::JSON_API_KEY_RELATIONSHIPS][$relationshipName][static::JSON_API_KEY_DATA] ?? [],
                    static::JSON_API_KEY_ID,
                );
            }
        }

        ksort($relationshipIds);

        return $relationshipIds;
    }
}
