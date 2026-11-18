<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyRole;

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
 * @group GetCompanyRolePermissionsBackendApiTest
 * Add your own group annotations below this line
 */
class GetCompanyRolePermissionsBackendApiTest extends AbstractCompanyRoleBackendApiTestCase
{
    protected const string UNKNOWN_LOCALE_NAME = 'xx_XX';

    public function testReturnsAvailablePermissionsWithLocalizedNames(): void
    {
        // Act
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyRolePermissionCollectionUrl());

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $localizedNames = $this->getFirstResourceAttributes($response)[static::ATTRIBUTE_LOCALIZED_NAMES] ?? [];
        $this->assertNotEmpty($localizedNames, 'Every permission carries one name per available locale.');

        foreach ($localizedNames as $localizedName) {
            $this->assertNotEmpty($localizedName[static::ATTRIBUTE_LOCALE_NAME]);
            $this->assertNotEmpty($localizedName[static::ATTRIBUTE_NAME]);
        }
    }

    public function testFiltersPermissionsByLocalizedName(): void
    {
        // Arrange
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyRolePermissionCollectionUrl());
        $permissionKey = $this->getResourceIds($response)[0];
        $localizedName = $this->getFirstResourceAttributes($response)[static::ATTRIBUTE_LOCALIZED_NAMES][0];

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRolePermissionCollectionUrl([
                'filter' => [
                    'company-role-permissions.name' => mb_strtoupper($localizedName[static::ATTRIBUTE_NAME]),
                    'company-role-permissions.localeName' => $localizedName[static::ATTRIBUTE_LOCALE_NAME],
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertContains(
            $permissionKey,
            $this->getResourceIds($response),
            'The name filter matches case-insensitively in the given locale.',
        );
    }

    public function testUnknownLocaleMatchesNothing(): void
    {
        // Arrange
        $response = $this->handleApiRequest('GET', $this->tester->getCompanyRolePermissionCollectionUrl());
        $localizedName = $this->getFirstResourceAttributes($response)[static::ATTRIBUTE_LOCALIZED_NAMES][0];

        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRolePermissionCollectionUrl([
                'filter' => [
                    'company-role-permissions.name' => $localizedName[static::ATTRIBUTE_NAME],
                    'company-role-permissions.localeName' => static::UNKNOWN_LOCALE_NAME,
                ],
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceIds($response));
    }

    public function testPaginatesPermissions(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRolePermissionCollectionUrl(['page' => ['limit' => 1]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertCount(1, $this->getResourceIds($response));

        $pagination = $this->getMetaPagination($response);
        $this->assertGreaterThan(1, $pagination[static::PAGINATION_KEY_NUM_FOUND]);
        $this->assertSame($pagination[static::PAGINATION_KEY_NUM_FOUND], $pagination[static::PAGINATION_KEY_MAX_PAGE]);
    }
}
