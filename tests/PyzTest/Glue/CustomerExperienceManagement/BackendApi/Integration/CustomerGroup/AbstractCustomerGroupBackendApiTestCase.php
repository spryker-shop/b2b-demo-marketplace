<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CustomerGroup;

use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CustomerGroupToCompanyAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupToCompanyBusinessUnitAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupToCustomerAssignmentTransfer;
use Generated\Shared\Transfer\CustomerGroupTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractCustomerGroupBackendApiTestCase extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string LINK_FIRST = 'first';

    protected const string LINK_PREV = 'prev';

    protected const string LINK_LAST = 'last';

    protected const string QUERY_PARAMETER_PAGE = 'page';

    protected const string PAGE_PARAMETER_OFFSET = 'offset';

    protected const string GENERATED_UUID_TEMPLATE = '00000000-0000-4000-8000-%012d';

    protected const int MAXIMUM_UUIDS = 1000;

    protected const string ATTRIBUTE_UUID = 'uuid';

    protected const string ATTRIBUTE_NAME = 'name';

    protected const string ATTRIBUTE_DESCRIPTION = 'description';

    protected const string ATTRIBUTE_CREATED_AT = 'createdAt';

    protected const string ATTRIBUTE_CUSTOMER_REFERENCES = 'customerReferences';

    protected const string ATTRIBUTE_EMAIL = 'email';

    protected const string ATTRIBUTE_FIRST_NAME = 'firstName';

    protected const string ATTRIBUTE_LAST_NAME = 'lastName';

    protected const string ATTRIBUTE_COMPANY_NAME = 'companyName';

    protected const string ATTRIBUTE_COMPANY_BUSINESS_UNIT_NAME = 'companyBusinessUnitName';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->actingAsUser();
    }

    protected function generateCustomerGroupName(): string
    {
        return sprintf('cxm-group-%s', uniqid('', true));
    }

    /**
     * @param array<string, mixed> $seed
     */
    protected function haveCustomerGroup(array $seed = []): CustomerGroupTransfer
    {
        $seed += [CustomerGroupTransfer::NAME => $this->generateCustomerGroupName()];

        return $this->tester->haveCustomerGroup($seed);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CustomerTransfer> $customerTransfers
     * @param array<string, mixed> $seed
     */
    protected function haveCustomerGroupWithCustomers(array $customerTransfers, array $seed = []): CustomerGroupTransfer
    {
        $customerIds = [];

        foreach ($customerTransfers as $customerTransfer) {
            $customerIds[] = $customerTransfer->getIdCustomerOrFail();
        }

        return $this->haveCustomerGroup($seed + [
            CustomerGroupTransfer::CUSTOMER_ASSIGNMENT => [
                CustomerGroupToCustomerAssignmentTransfer::IDS_CUSTOMER_TO_ASSIGN => $customerIds,
                CustomerGroupToCustomerAssignmentTransfer::IDS_CUSTOMER_TO_DE_ASSIGN => [],
            ],
        ]);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CompanyTransfer> $companyTransfers
     */
    protected function haveCustomerGroupWithCompanies(array $companyTransfers): CustomerGroupTransfer
    {
        return $this->haveCustomerGroup([
            CustomerGroupTransfer::COMPANY_ASSIGNMENT => [
                CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_ASSIGN => array_map(
                    fn (CompanyTransfer $companyTransfer): int => $companyTransfer->getIdCompanyOrFail(),
                    $companyTransfers,
                ),
                CustomerGroupToCompanyAssignmentTransfer::IDS_COMPANY_TO_DE_ASSIGN => [],
            ],
        ]);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\CompanyBusinessUnitTransfer> $companyBusinessUnitTransfers
     */
    protected function haveCustomerGroupWithCompanyBusinessUnits(array $companyBusinessUnitTransfers): CustomerGroupTransfer
    {
        return $this->haveCustomerGroup([
            CustomerGroupTransfer::COMPANY_BUSINESS_UNIT_ASSIGNMENT => [
                CustomerGroupToCompanyBusinessUnitAssignmentTransfer::IDS_COMPANY_BUSINESS_UNIT_TO_ASSIGN => array_map(
                    fn (CompanyBusinessUnitTransfer $companyBusinessUnitTransfer): int => $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
                    $companyBusinessUnitTransfers,
                ),
                CustomerGroupToCompanyBusinessUnitAssignmentTransfer::IDS_COMPANY_BUSINESS_UNIT_TO_DE_ASSIGN => [],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $seed
     */
    protected function haveNamedCompany(string $name, array $seed = []): CompanyTransfer
    {
        return $this->tester->haveCompany($seed + [CompanyTransfer::NAME => $name]);
    }

    protected function haveCompanyBusinessUnitOf(CompanyTransfer $companyTransfer, ?string $name = null): CompanyBusinessUnitTransfer
    {
        $seed = [CompanyBusinessUnitTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail()];

        if ($name !== null) {
            $seed[CompanyBusinessUnitTransfer::NAME] = $name;
        }

        return $this->tester->haveCompanyBusinessUnit($seed);
    }

    /**
     * @param callable(array<string, mixed>): string $collectionUrlBuilder
     *
     * @return list<string>
     */
    protected function getIdsOfTwoPages(callable $collectionUrlBuilder, string $sort): array
    {
        $ids = [];

        foreach ([0, 2] as $offset) {
            $response = $this->tester->handleApiRequest('GET', $collectionUrlBuilder([
                'sort' => $sort,
                'page' => ['limit' => 2, 'offset' => $offset],
            ]));
            $this->assertRespondsWithStatus($response, Response::HTTP_OK);
            $ids = array_merge($ids, $this->getResourceIds($response));
        }

        return $ids;
    }

    protected function findLinkedPageOffset(Response $response, string $linkName): ?int
    {
        $link = $this->decodeJsonApi($response)[static::JSON_API_KEY_LINKS][$linkName] ?? null;

        if ($link === null) {
            return null;
        }

        parse_str((string)parse_url($link, PHP_URL_QUERY), $query);

        return (int)$query[static::QUERY_PARAMETER_PAGE][static::PAGE_PARAMETER_OFFSET];
    }

    /**
     * @return list<string>
     */
    protected static function generateUuids(int $count): array
    {
        return array_map(
            fn (int $index): string => sprintf(static::GENERATED_UUID_TEMPLATE, $index),
            range(1, $count),
        );
    }
}
