<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\BackendApi\Integration\CompanyRole;

use Generated\Shared\Transfer\CompanyRoleTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use PyzTest\Glue\CustomerExperienceManagement\AbstractCustomerExperienceManagementBackendApiTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractCompanyRoleBackendApiTestCase extends AbstractCustomerExperienceManagementBackendApiTestCase
{
    protected const string ATTRIBUTE_UUID = 'uuid';

    protected const string ATTRIBUTE_NAME = 'name';

    protected const string ATTRIBUTE_IS_DEFAULT = 'isDefault';

    protected const string ATTRIBUTE_COMPANY_NAME = 'companyName';

    protected const string ATTRIBUTE_PERMISSION_KEYS = 'permissionKeys';

    protected const string ATTRIBUTE_LOCALIZED_NAMES = 'localizedNames';

    protected const string ATTRIBUTE_LOCALE_NAME = 'localeName';

    protected const string UNKNOWN_PERMISSION_KEY = 'CxmUnknownPermissionPlugin';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_INVALID_SORT_FIELD
     */
    protected const string RESPONSE_CODE_INVALID_SORT_FIELD = '1203';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_NOT_FOUND
     */
    protected const string RESPONSE_CODE_COMPANY_NOT_FOUND = '1213';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD
     */
    protected const string RESPONSE_CODE_UNSUPPORTED_FILTER_FIELD = '1215';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_ROLE_NOT_FOUND
     */
    protected const string RESPONSE_CODE_COMPANY_ROLE_NOT_FOUND = '1218';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_ROLE_VALIDATION
     */
    protected const string RESPONSE_CODE_COMPANY_ROLE_VALIDATION = '1230';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_ROLE_IS_DEFAULT
     */
    protected const string RESPONSE_CODE_COMPANY_ROLE_IS_DEFAULT = '1231';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_ROLE_HAS_COMPANY_USERS
     */
    protected const string RESPONSE_CODE_COMPANY_ROLE_HAS_COMPANY_USERS = '1232';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_UNKNOWN_PERMISSION
     */
    protected const string RESPONSE_CODE_UNKNOWN_PERMISSION = '1233';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_ROLE_DEFAULT_CANNOT_BE_CLEARED
     */
    protected const string RESPONSE_CODE_COMPANY_ROLE_DEFAULT_CANNOT_BE_CLEARED = '1234';

    /**
     * @uses \SprykerFeature\Glue\CustomerExperienceManagement\CustomerExperienceManagementConfig::RESPONSE_CODE_COMPANY_ROLE_COMPANY_IMMUTABLE
     */
    protected const string RESPONSE_CODE_COMPANY_ROLE_COMPANY_IMMUTABLE = '1235';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->actingAsUser();
    }

    protected function generateCompanyRoleName(): string
    {
        return sprintf('cxm-role-%s', uniqid('', true));
    }

    protected function haveCompany(): CompanyTransfer
    {
        return $this->tester->haveCompany([CompanyTransfer::NAME => uniqid('CxmRoleCompany', false)]);
    }

    /**
     * @param array<string, mixed> $seed
     */
    protected function haveCompanyRoleFor(CompanyTransfer $companyTransfer, array $seed = []): CompanyRoleTransfer
    {
        return $this->tester->haveCompanyRole($seed + [
            CompanyRoleTransfer::NAME => $this->generateCompanyRoleName(),
            CompanyRoleTransfer::FK_COMPANY => $companyTransfer->getIdCompanyOrFail(),
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return non-empty-string
     */
    protected function haveCompanyRoleViaApi(CompanyTransfer $companyTransfer, array $attributes = []): string
    {
        $response = $this->handleApiRequest(
            'POST',
            $this->tester->getCompanyRoleCollectionUrl(),
            $this->tester->buildCompanyRoleRequestBody($attributes + [
                static::ATTRIBUTE_NAME => $this->generateCompanyRoleName(),
                static::ATTRIBUTE_COMPANY_UUID => $companyTransfer->getUuidOrFail(),
            ]),
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $response->getStatusCode(),
            sprintf('Could not provision the company role under test: %s', (string)$response->getContent()),
        );

        $uuid = (string)($this->decodeJsonApi($response)[static::JSON_API_KEY_DATA][static::JSON_API_KEY_ID] ?? '');
        $this->assertNotSame('', $uuid, 'The created company role must be addressable by uuid.');

        /** @var non-empty-string $uuid */
        return $uuid;
    }

    /**
     * @return list<string>
     */
    protected function getAvailablePermissionKeys(int $count): array
    {
        $response = $this->handleApiRequest(
            'GET',
            $this->tester->getCompanyRolePermissionCollectionUrl(['page' => ['limit' => $count]]),
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string)$response->getContent());

        $permissionKeys = $this->getResourceIds($response);
        $this->assertCount($count, $permissionKeys, 'The installation must offer enough permissions for the test.');

        return array_values($permissionKeys);
    }
}
