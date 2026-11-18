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
 * @group UpdateCompanyRoleBackendApiTest
 * Add your own group annotations below this line
 */
class UpdateCompanyRoleBackendApiTest extends AbstractCompanyRoleBackendApiTestCase
{
    public function testRenameKeepsPermissionsWhenPermissionKeysAreOmitted(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $permissionKeys = $this->getAvailablePermissionKeys(2);
        $uuid = $this->haveCompanyRoleViaApi($companyTransfer, [static::ATTRIBUTE_PERMISSION_KEYS => $permissionKeys]);
        $newName = $this->generateCompanyRoleName();

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($uuid),
            $this->tester->buildCompanyRoleRequestBody([static::ATTRIBUTE_NAME => $newName]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);

        $attributes = $this->getResourceAttributes($response);
        $this->assertSame($uuid, $attributes[static::ATTRIBUTE_UUID], 'Renaming must not change the identifier.');
        $this->assertSame($newName, $attributes[static::ATTRIBUTE_NAME]);
        $this->assertEqualsCanonicalizing(
            $permissionKeys,
            $attributes[static::ATTRIBUTE_PERMISSION_KEYS],
            'An omitted permissionKeys must leave the stored set untouched.',
        );
    }

    public function testPermissionKeysReplaceTheWholePermissionSet(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        [$firstPermissionKey, $secondPermissionKey] = $this->getAvailablePermissionKeys(2);
        $uuid = $this->haveCompanyRoleViaApi($companyTransfer, [static::ATTRIBUTE_PERMISSION_KEYS => [$firstPermissionKey]]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($uuid),
            $this->tester->buildCompanyRoleRequestBody([static::ATTRIBUTE_PERMISSION_KEYS => [$secondPermissionKey]]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([$secondPermissionKey], $this->getResourceAttributes($response)[static::ATTRIBUTE_PERMISSION_KEYS]);
    }

    public function testEmptyPermissionKeysDetachesEveryPermission(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $uuid = $this->haveCompanyRoleViaApi($companyTransfer, [
            static::ATTRIBUTE_PERMISSION_KEYS => $this->getAvailablePermissionKeys(2),
        ]);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($uuid),
            $this->tester->buildCompanyRoleRequestBody([static::ATTRIBUTE_PERMISSION_KEYS => []]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertSame([], $this->getResourceAttributes($response)[static::ATTRIBUTE_PERMISSION_KEYS]);
    }

    public function testPromotingCompanyRoleToDefaultDemotesThePreviousDefault(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $previousDefaultCompanyRoleUuid = $this->tester->getDefaultCompanyRoleUuid($companyTransfer->getIdCompanyOrFail());
        $companyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($companyRoleTransfer->getUuidOrFail()),
            $this->tester->buildCompanyRoleRequestBody([static::ATTRIBUTE_IS_DEFAULT => true]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
        $this->assertTrue($this->getResourceAttributes($response)[static::ATTRIBUTE_IS_DEFAULT]);
        $this->assertFalse($this->tester->isCompanyRoleDefault($previousDefaultCompanyRoleUuid));
        $this->assertSame(1, $this->tester->countDefaultCompanyRoles($companyTransfer->getIdCompanyOrFail()));
    }

    public function testRejectsClearingTheDefaultFlagOfTheDefaultRole(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $defaultCompanyRoleUuid = $this->tester->getDefaultCompanyRoleUuid($companyTransfer->getIdCompanyOrFail());

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($defaultCompanyRoleUuid),
            $this->tester->buildCompanyRoleRequestBody([static::ATTRIBUTE_IS_DEFAULT => false]),
        );

        // Assert
        $this->assertRespondsWithErrorCode(
            $response,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::RESPONSE_CODE_COMPANY_ROLE_DEFAULT_CANNOT_BE_CLEARED,
        );
        $this->assertTrue($this->tester->isCompanyRoleDefault($defaultCompanyRoleUuid));
    }

    public function testRejectsMovingCompanyRoleToAnotherCompany(): void
    {
        // Arrange
        $companyRoleTransfer = $this->haveCompanyRoleFor($this->haveCompany());

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($companyRoleTransfer->getUuidOrFail()),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_COMPANY_UUID => $this->haveCompany()->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_COMPANY_ROLE_COMPANY_IMMUTABLE);
    }

    public function testAllowsSendingTheCompanyRolesOwnCompanyUuid(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $companyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($companyRoleTransfer->getUuidOrFail()),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_COMPANY_UUID => $companyTransfer->getUuidOrFail(),
                static::ATTRIBUTE_NAME => $this->generateCompanyRoleName(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_OK);
    }

    public function testRejectsRenameToANameAnotherRoleOfTheCompanyUses(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $existingCompanyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);
        $companyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($companyRoleTransfer->getUuidOrFail()),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $existingCompanyRoleTransfer->getNameOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_COMPANY_ROLE_VALIDATION);
    }

    public function testAllowsSendingTheCompanyRolesOwnNameUnchanged(): void
    {
        // Arrange
        $companyRoleTransfer = $this->haveCompanyRoleFor($this->haveCompany());

        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl($companyRoleTransfer->getUuidOrFail()),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $companyRoleTransfer->getNameOrFail(),
            ]),
        );

        // Assert
        $this->assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            'The uniqueness check must exclude the role being updated.',
        );
    }

    public function testReturnsNotFoundForUnknownUuid(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'PATCH',
            $this->tester->getCompanyRoleUrl(static::UNKNOWN_WELL_FORMED_UUID),
            $this->tester->buildCompanyRoleRequestBody([static::ATTRIBUTE_NAME => $this->generateCompanyRoleName()]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_NOT_FOUND, static::RESPONSE_CODE_COMPANY_ROLE_NOT_FOUND);
    }
}
