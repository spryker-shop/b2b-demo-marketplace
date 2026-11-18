<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyRole;

use Generated\Shared\Transfer\CompanyRoleTransfer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Glue
 * @group CustomerExperienceManagement
 * @group BackendApi
 * @group Integration
 * @group CompanyRole
 * @group GetCompanyRolesBackendApiTest
 * Add your own group annotations below this line
 */
class GetCompanyRolesBackendApiTest extends AbstractCompanyRoleBackendApiTestCase
{
    public function testReturnsSingleCompanyRoleByUuid(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $permissionKeys = $this->getAvailablePermissionKeys(2);
        $uuid = $this->haveCompanyRoleViaApi($companyTransfer, [static::ATTRIBUTE_PERMISSION_KEYS => $permissionKeys]);

        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyRoleUrl($uuid));

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame($uuid, $attributes[static::ATTRIBUTE_UUID]);
        $this->assertFalse($attributes[static::ATTRIBUTE_IS_DEFAULT]);
        $this->assertSame($companyTransfer->getUuidOrFail(), $attributes[static::ATTRIBUTE_COMPANY_UUID]);
        $this->assertSame($companyTransfer->getNameOrFail(), $attributes[static::ATTRIBUTE_COMPANY_NAME]);
        $this->assertEqualsCanonicalizing($permissionKeys, $attributes[static::ATTRIBUTE_PERMISSION_KEYS]);
    }

    public function testReturnsNotFoundForUnknownUuid(): void
    {
        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyRoleUrl(static::UNKNOWN_WELL_FORMED_UUID));

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_NOT_FOUND, static::RESPONSE_CODE_COMPANY_ROLE_NOT_FOUND);
    }

    public function testFiltersCompanyRolesByCompanyUuid(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $companyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);
        $otherCompanyRoleTransfer = $this->haveCompanyRoleFor($this->haveCompany());

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl([
                'filter' => ['company-roles.companyUuid' => $companyTransfer->getUuidOrFail()],
                'page' => ['limit' => 100],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertContains($companyRoleTransfer->getUuidOrFail(), $uuids);
        $this->assertNotContains($otherCompanyRoleTransfer->getUuidOrFail(), $uuids);

        foreach ($this->getJsonApiMembers($response, static::JSON_API_KEY_DATA) as $member) {
            $this->assertSame($companyTransfer->getUuidOrFail(), $member[static::JSON_API_KEY_ATTRIBUTES][static::ATTRIBUTE_COMPANY_UUID]);
        }
    }

    public function testFiltersCompanyRolesByNameFragment(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $companyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);
        $this->haveCompanyRoleFor($companyTransfer);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl([
                'filter' => ['company-roles.name' => substr($companyRoleTransfer->getNameOrFail(), 4)],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$companyRoleTransfer->getUuidOrFail()], $this->getResourceIds($response));
    }

    public function testFiltersCompanyRolesByIsDefault(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $this->haveCompanyRoleFor($companyTransfer);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl([
                'filter' => [
                    'company-roles.companyUuid' => $companyTransfer->getUuidOrFail(),
                    'company-roles.isDefault' => 'true',
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$this->tester->getDefaultCompanyRoleUuid($companyTransfer->getIdCompanyOrFail())],
            $this->getResourceIds($response),
            'A company holds exactly one default role.',
        );
    }

    public function testFindsCompanyRolesByFreeTextSearchOnCompanyName(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $companyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);
        $otherCompanyRoleTransfer = $this->haveCompanyRoleFor($this->haveCompany());

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl([
                'q' => $companyTransfer->getNameOrFail(),
                'page' => ['limit' => 100],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $uuids = $this->getResourceIds($response);
        $this->assertContains($companyRoleTransfer->getUuidOrFail(), $uuids);
        $this->assertNotContains($otherCompanyRoleTransfer->getUuidOrFail(), $uuids);
    }

    public function testSortsCompanyRolesByNameDescending(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $firstCompanyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer, [CompanyRoleTransfer::NAME => 'cxm-sort-aaa-' . uniqid()]);
        $secondCompanyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer, [CompanyRoleTransfer::NAME => 'cxm-sort-zzz-' . uniqid()]);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl([
                'filter' => [
                    'company-roles.companyUuid' => $companyTransfer->getUuidOrFail(),
                    'company-roles.name' => 'cxm-sort-',
                ],
                'sort' => '-name',
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame(
            [$secondCompanyRoleTransfer->getUuidOrFail(), $firstCompanyRoleTransfer->getUuidOrFail()],
            $this->getResourceIds($response),
        );
    }

    public function testPaginatesCompanyRoles(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $this->haveCompanyRoleFor($companyTransfer);
        $this->haveCompanyRoleFor($companyTransfer);

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl([
                'filter' => ['company-roles.name' => 'cxm-role-', 'company-roles.companyUuid' => $companyTransfer->getUuidOrFail()],
                'page' => ['limit' => 1],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->getResourceIds($response));

        $pagination = $this->getMetaPagination($response);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame(2, $pagination[static::PAGINATION_KEY_MAX_PAGE]);
    }

    public function testRejectsUnsupportedFilterField(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl(['filter' => ['company-roles.permissionKeys' => 'x']]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_BAD_REQUEST, static::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD);
    }

    public function testRejectsUnsupportedSortField(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRoleCollectionUrl(['sort' => 'uuid']),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_BAD_REQUEST, static::RESPONSE_CODE_INVALID_SORT_FIELD);
    }
}
