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
 * @group CreateCompanyRoleBackendApiTest
 * Add your own group annotations below this line
 */
class CreateCompanyRoleBackendApiTest extends AbstractCompanyRoleBackendApiTestCase
{
    public function testCreatesCompanyRoleWithPermissions(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $name = $this->generateCompanyRoleName();
        $permissionKeys = $this->getAvailablePermissionKeys(2);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $name,
                static::ATTRIBUTE_COMPANY_UUID => $companyTransfer->getUuidOrFail(),
                static::ATTRIBUTE_PERMISSION_KEYS => $permissionKeys,
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);

        $attributes = $this->getResourceAttributes($response);
        $this->assertNotEmpty($attributes[static::ATTRIBUTE_UUID]);
        $this->assertSame($name, $attributes[static::ATTRIBUTE_NAME]);
        $this->assertSame($companyTransfer->getUuidOrFail(), $attributes[static::ATTRIBUTE_COMPANY_UUID]);
        $this->assertSame($companyTransfer->getNameOrFail(), $attributes[static::ATTRIBUTE_COMPANY_NAME]);
        $this->assertEqualsCanonicalizing($permissionKeys, $attributes[static::ATTRIBUTE_PERMISSION_KEYS]);
    }

    public function testCreatesNonDefaultCompanyRoleWhenIsDefaultIsOmitted(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $defaultCompanyRoleUuid = $this->tester->getDefaultCompanyRoleUuid($companyTransfer->getIdCompanyOrFail());

        // Act
        $uuid = $this->haveCompanyRoleViaApi($companyTransfer);

        // Assert
        $this->assertFalse($this->tester->isCompanyRoleDefault($uuid));
        $this->assertTrue(
            $this->tester->isCompanyRoleDefault($defaultCompanyRoleUuid),
            'Creating a role without the flag must leave the existing default in place.',
        );
    }

    public function testCreatingDefaultCompanyRoleDemotesThePreviousDefault(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $previousDefaultCompanyRoleUuid = $this->tester->getDefaultCompanyRoleUuid($companyTransfer->getIdCompanyOrFail());

        // Act
        $uuid = $this->haveCompanyRoleViaApi($companyTransfer, [static::ATTRIBUTE_IS_DEFAULT => true]);

        // Assert
        $this->assertTrue($this->tester->isCompanyRoleDefault($uuid));
        $this->assertFalse($this->tester->isCompanyRoleDefault($previousDefaultCompanyRoleUuid));
        $this->assertSame(1, $this->tester->countDefaultCompanyRoles($companyTransfer->getIdCompanyOrFail()));
    }

    public function testRejectsNameAlreadyUsedWithinTheCompany(): void
    {
        // Arrange
        $companyTransfer = $this->haveCompany();
        $existingCompanyRoleTransfer = $this->haveCompanyRoleFor($companyTransfer);

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $existingCompanyRoleTransfer->getNameOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => $companyTransfer->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_COMPANY_ROLE_VALIDATION);
    }

    public function testAllowsNameUsedByAnotherCompany(): void
    {
        // Arrange
        $existingCompanyRoleTransfer = $this->haveCompanyRoleFor($this->haveCompany());

        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $existingCompanyRoleTransfer->getNameOrFail(),
                static::ATTRIBUTE_COMPANY_UUID => $this->haveCompany()->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertRespondsWithStatus($response, Response::HTTP_CREATED);
    }

    public function testRejectsUnknownPermissionKey(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $this->generateCompanyRoleName(),
                static::ATTRIBUTE_COMPANY_UUID => $this->haveCompany()->getUuidOrFail(),
                static::ATTRIBUTE_PERMISSION_KEYS => [static::UNKNOWN_PERMISSION_KEY],
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_UNPROCESSABLE_ENTITY, static::RESPONSE_CODE_UNKNOWN_PERMISSION);
    }

    public function testReturnsNotFoundForUnknownCompany(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $this->generateCompanyRoleName(),
                static::ATTRIBUTE_COMPANY_UUID => static::UNKNOWN_WELL_FORMED_UUID,
            ]),
        );

        // Assert
        $this->assertRespondsWithErrorCode($response, Response::HTTP_NOT_FOUND, static::RESPONSE_CODE_COMPANY_NOT_FOUND);
    }

    public function testRejectsBlankName(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => '',
                static::ATTRIBUTE_COMPANY_UUID => $this->haveCompany()->getUuidOrFail(),
            ]),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_NAME);
    }

    public function testRejectsMalformedCompanyUuid(): void
    {
        // Act
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody([
                static::ATTRIBUTE_NAME => $this->generateCompanyRoleName(),
                static::ATTRIBUTE_COMPANY_UUID => static::MALFORMED_UUID,
            ]),
        );

        // Assert
        $this->assertValidationFailedForAttribute($response, static::ATTRIBUTE_COMPANY_UUID);
    }
}
