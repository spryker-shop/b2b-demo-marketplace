<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyRoleCollectionTransfer;
use Generated\Shared\Transfer\CompanyRoleTransfer;
use Generated\Shared\Transfer\CompanyTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Orm\Zed\CompanyBusinessUnit\Persistence\SpyCompanyBusinessUnitQuery;
use Orm\Zed\CompanyRole\Persistence\SpyCompanyRoleQuery;
use Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery;
use Orm\Zed\Customer\Persistence\Map\SpyCustomerTableMap;
use Orm\Zed\Customer\Persistence\SpyCustomerQuery;
use Orm\Zed\CustomerAccess\Persistence\SpyUnauthenticatedCustomerAccessQuery;
use Orm\Zed\CustomerGroup\Persistence\SpyCustomerGroupQuery;
use RuntimeException;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use SprykerFeatureTest\Glue\CustomerExperienceManagement\Helper\CustomerExperienceManagementBackendApiHelper as BackendApiRequestHelper;
use SprykerTest\Shared\Customer\Helper\CustomerDataHelper;
use SprykerTest\Shared\CustomerNote\Helper\CustomerNoteDataHelper;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use SprykerTest\Shared\User\Helper\UserDataHelper;
use SprykerTest\Zed\Company\Helper\CompanyHelper;
use SprykerTest\Zed\CompanyBusinessUnit\Helper\CompanyBusinessUnitHelper;
use SprykerTest\Zed\CompanyRole\Helper\CompanyRoleHelper;

/**
 * Fixture arrangement for the CustomerExperienceManagement Backend API test lanes: seeding
 * companies, customers, notes, company users and company roles, and reading rows back for
 * assertions.
 *
 * Stateless request building for the resources the feature ships lives in the module's own
 * {@see \SprykerFeatureTest\Glue\CustomerExperienceManagement\Helper\CustomerExperienceManagementBackendApiHelper},
 * which this suite enables alongside this one; Codeception merges both onto the same actor.
 */
class CustomerExperienceManagementBackendApiHelper extends Module
{
    use LocatorHelperTrait;

    public const string RESOURCE_COMPANY_ROLES = 'company-roles';

    public const string RESOURCE_COMPANY_ROLE_PERMISSIONS = 'company-role-permissions';

    public const string LISTED_FIRST_NAME_FIRST = 'Aaron';

    public const string LISTED_FIRST_NAME_SECOND = 'Zoe';

    protected const string ADDRESSEE_LAST_NAME = 'CxmAddressee';

    public function clearCompanyBusinessUnitUuid(string $uuid): void
    {
        SpyCompanyBusinessUnitQuery::create()->filterByUuid($uuid)->update(['Uuid' => null]);
    }

    /**
     * @see static::clearCompanyBusinessUnitUuid()
     */
    public function clearCompanyUnitAddressUuid(string $uuid): void
    {
        SpyCompanyUnitAddressQuery::create()->filterByUuid($uuid)->update(['Uuid' => null]);
    }

    public function findCustomerIdByEmail(string $email): ?int
    {
        $customerEntity = SpyCustomerQuery::create()->filterByEmail($email)->findOne();

        return $customerEntity?->getIdCustomer();
    }

    /**
     * @return array<\Generated\Shared\Transfer\CustomerTransfer>
     */
    public function haveTwoListedCustomers(): array
    {
        $listedLastName = uniqid(BackendApiRequestHelper::LISTED_LAST_NAME_PREFIX);

        return [
            $this->haveListedCustomer(static::LISTED_FIRST_NAME_FIRST, $listedLastName),
            $this->haveListedCustomer(static::LISTED_FIRST_NAME_SECOND, $listedLastName),
        ];
    }

    /**
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: \Generated\Shared\Transfer\AddressTransfer, 2: \Generated\Shared\Transfer\AddressTransfer}
     */
    public function haveCustomerWithTwoAddresses(): array
    {
        $customerTransfer = $this->haveAddresseeCustomer();

        return [
            $customerTransfer,
            $this->haveCustomerAddressFor($customerTransfer, [
                AddressTransfer::FIRST_NAME => static::LISTED_FIRST_NAME_FIRST,
                AddressTransfer::CITY => BackendApiRequestHelper::CITY_FIRST_ADDRESS,
                AddressTransfer::ZIP_CODE => BackendApiRequestHelper::ZIP_CODE_FIRST_ADDRESS,
            ]),
            $this->haveCustomerAddressFor($customerTransfer, [
                AddressTransfer::FIRST_NAME => static::LISTED_FIRST_NAME_SECOND,
                AddressTransfer::CITY => BackendApiRequestHelper::CITY_SECOND_ADDRESS,
                AddressTransfer::ZIP_CODE => BackendApiRequestHelper::ZIP_CODE_SECOND_ADDRESS,
            ]),
        ];
    }

    public function haveAddresseeCustomer(): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([
            CustomerTransfer::EMAIL => uniqid('cxm.address.', true) . '@spryker.local',
            CustomerTransfer::LAST_NAME => static::ADDRESSEE_LAST_NAME,
        ]);
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveCustomerAddressFor(CustomerTransfer $customerTransfer, array $seed = []): AddressTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomerAddress($seed + [
            AddressTransfer::FK_CUSTOMER => $customerTransfer->getIdCustomerOrFail(),
            AddressTransfer::ISO2_CODE => BackendApiRequestHelper::ISO2_CODE,
            AddressTransfer::LAST_NAME => static::ADDRESSEE_LAST_NAME,
            AddressTransfer::ADDRESS1 => BackendApiRequestHelper::ADDRESS1,
            AddressTransfer::ADDRESS2 => BackendApiRequestHelper::ADDRESS2,
        ]);
    }

    /**
     * @return array{0: \Generated\Shared\Transfer\CustomerTransfer, 1: \Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer, 2: \Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer}
     */
    public function haveCustomerWithTwoNotes(UserTransfer $userTransfer): array
    {
        $customerTransfer = $this->haveNotedCustomer();

        return [
            $customerTransfer,
            $this->haveCustomerNoteFor($userTransfer, $customerTransfer),
            $this->haveCustomerNoteFor($userTransfer, $customerTransfer),
        ];
    }

    public function haveNotedCustomer(): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([
            CustomerTransfer::EMAIL => uniqid('cxm.note.', true) . '@spryker.local',
            CustomerTransfer::LAST_NAME => 'CxmNoted',
        ]);
    }

    public function haveCustomerNoteFor(
        UserTransfer $userTransfer,
        CustomerTransfer $customerTransfer,
    ): SpyCustomerNoteEntityTransfer {
        return $this->getCustomerNoteDataHelper()->haveCustomerNote(
            $userTransfer->getIdUserOrFail(),
            $customerTransfer->getIdCustomerOrFail(),
        );
    }

    public function haveNoteAuthor(): UserTransfer
    {
        return $this->getUserDataHelper()->haveUser();
    }

    /**
     * @return array{company: \Generated\Shared\Transfer\CompanyTransfer, businessUnit: \Generated\Shared\Transfer\CompanyBusinessUnitTransfer, role: \Generated\Shared\Transfer\CompanyRoleTransfer}
     */
    public function haveCompanyContext(): array
    {
        // haveCompany() rather than haveActiveCompany(): approving a company fires
        // SendCompanyStatusChangePlugin, and CompanyMailConnector resolves its mail facade from the
        // bare 'FACADE_MAIL' key that CustomerDataHelper has already registered a Customer-specific
        // bridge under — a TypeError as soon as a customer was created earlier in the same test.
        // No company-user write checks the company status; the pre-check plugin only checks that it
        // exists.
        $companyTransfer = $this->getCompanyHelper()->haveCompany();
        $idCompany = $companyTransfer->getIdCompanyOrFail();

        return [
            'company' => $companyTransfer,
            'businessUnit' => $this->haveBusinessUnitFor($idCompany),
            'role' => $this->haveRoleFor($idCompany),
        ];
    }

    public function haveBusinessUnitFor(int $idCompany): CompanyBusinessUnitTransfer
    {
        return $this->getCompanyBusinessUnitHelper()->haveCompanyBusinessUnit([
            CompanyBusinessUnitTransfer::FK_COMPANY => $idCompany,
        ]);
    }

    public function haveRoleFor(int $idCompany): CompanyRoleTransfer
    {
        return $this->getCompanyRoleHelper()->haveCompanyRole([
            CompanyRoleTransfer::FK_COMPANY => $idCompany,
        ]);
    }

    /**
     * @return array{0: \Generated\Shared\Transfer\CompanyUserTransfer, 1: \Generated\Shared\Transfer\CompanyUserTransfer}
     */
    public function haveTwoListedCompanyUsers(
        CompanyTransfer $companyTransfer,
        CompanyBusinessUnitTransfer $companyBusinessUnitTransfer,
        CompanyRoleTransfer $companyRoleTransfer,
        string $listedLastName,
    ): array {
        $secondBusinessUnitTransfer = $this->haveBusinessUnitFor($companyTransfer->getIdCompanyOrFail());

        return [
            $this->haveCompanyUserFor(
                $companyTransfer,
                $companyBusinessUnitTransfer,
                $companyRoleTransfer,
                static::LISTED_FIRST_NAME_FIRST,
                $listedLastName,
            ),
            $this->haveCompanyUserFor(
                $companyTransfer,
                $secondBusinessUnitTransfer,
                $companyRoleTransfer,
                static::LISTED_FIRST_NAME_SECOND,
                $listedLastName,
            ),
        ];
    }

    public function haveCompanyUserFor(
        CompanyTransfer $companyTransfer,
        CompanyBusinessUnitTransfer $companyBusinessUnitTransfer,
        CompanyRoleTransfer $companyRoleTransfer,
        string $firstName = self::LISTED_FIRST_NAME_FIRST,
        ?string $lastName = null,
    ): CompanyUserTransfer {
        $customerTransfer = $this->haveCompanyUserCustomer($firstName, $lastName ?? $this->getRequestHelper()->getListedCompanyUserLastName());

        $companyUserTransfer = (new CompanyUserTransfer())
            ->setCustomer($customerTransfer)
            ->setFkCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCompany($companyTransfer)
            ->setFkCompany($companyTransfer->getIdCompanyOrFail())
            ->setCompanyBusinessUnit($companyBusinessUnitTransfer)
            ->setFkCompanyBusinessUnit($companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail())
            ->setCompanyRoleCollection(
                (new CompanyRoleCollectionTransfer())->addRole($companyRoleTransfer),
            );

        $companyUserResponseTransfer = $this->getCompanyUserFacade()->create($companyUserTransfer);

        if (!$companyUserResponseTransfer->getIsSuccessful()) {
            $this->fail(sprintf(
                'Could not provision the company user under test: %s',
                implode('; ', array_map(
                    static fn ($responseMessageTransfer): string => (string)$responseMessageTransfer->getText(),
                    $companyUserResponseTransfer->getMessages()->getArrayCopy(),
                )),
            ));
        }

        return $companyUserResponseTransfer->getCompanyUserOrFail();
    }

    public function haveCompanyUserCustomer(string $firstName, string $lastName): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([
            CustomerTransfer::EMAIL => sprintf('%s.%s@spryker.local', strtolower($firstName), strtolower($lastName)),
            CustomerTransfer::FIRST_NAME => $firstName,
            CustomerTransfer::LAST_NAME => $lastName,
        ]);
    }

    protected function getCompanyUserFacade(): CompanyUserFacadeInterface
    {
        return $this->getLocator()->companyUser()->facade();
    }

    protected function getCompanyHelper(): CompanyHelper
    {
        /** @var \SprykerTest\Zed\Company\Helper\CompanyHelper $companyHelper */
        $companyHelper = $this->getModule('\\' . CompanyHelper::class);

        return $companyHelper;
    }

    protected function getCompanyBusinessUnitHelper(): CompanyBusinessUnitHelper
    {
        /** @var \SprykerTest\Zed\CompanyBusinessUnit\Helper\CompanyBusinessUnitHelper $companyBusinessUnitHelper */
        $companyBusinessUnitHelper = $this->getModule('\\' . CompanyBusinessUnitHelper::class);

        return $companyBusinessUnitHelper;
    }

    protected function getCompanyRoleHelper(): CompanyRoleHelper
    {
        /** @var \SprykerTest\Zed\CompanyRole\Helper\CompanyRoleHelper $companyRoleHelper */
        $companyRoleHelper = $this->getModule('\\' . CompanyRoleHelper::class);

        return $companyRoleHelper;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function buildRequestBody(string $type, array $attributes, ?string $id = null): string
    {
        $data = [
            'type' => $type,
            'attributes' => $attributes,
        ];

        if ($id !== null) {
            $data['id'] = $id;
        }

        return (string)json_encode(['data' => $data]);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function formatQuery(array $query): string
    {
        if ($query === []) {
            return '';
        }

        return '?' . http_build_query($query);
    }

    protected function haveListedCustomer(string $firstName, string $lastName): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([
            CustomerTransfer::EMAIL => sprintf('%s.%s@spryker.local', strtolower($firstName), $lastName),
            CustomerTransfer::FIRST_NAME => $firstName,
            CustomerTransfer::LAST_NAME => $lastName,
        ]);
    }

    protected function getCustomerDataHelper(): CustomerDataHelper
    {
        /** @var \SprykerTest\Shared\Customer\Helper\CustomerDataHelper $customerDataHelper */
        $customerDataHelper = $this->getModule('\\' . CustomerDataHelper::class);

        return $customerDataHelper;
    }

    protected function getCustomerNoteDataHelper(): CustomerNoteDataHelper
    {
        /** @var \SprykerTest\Shared\CustomerNote\Helper\CustomerNoteDataHelper $customerNoteDataHelper */
        $customerNoteDataHelper = $this->getModule('\\' . CustomerNoteDataHelper::class);

        return $customerNoteDataHelper;
    }

    protected function getUserDataHelper(): UserDataHelper
    {
        /** @var \SprykerTest\Shared\User\Helper\UserDataHelper $userDataHelper */
        $userDataHelper = $this->getModule('\\' . UserDataHelper::class);

        return $userDataHelper;
    }

    /**
     * @return array<int, string>
     */
    public function getAssignedCustomerReferences(string $uuid): array
    {
        $customerReferences = SpyCustomerQuery::create()
            ->useSpyCustomerGroupToCustomerQuery()
                ->useCustomerGroupQuery()
                    ->filterByUuid($uuid)
                ->endUse()
            ->endUse()
            ->orderByCustomerReference()
            ->select([SpyCustomerTableMap::COL_CUSTOMER_REFERENCE])
            ->find()
            ->toArray();

        return array_map('strval', $customerReferences);
    }

    /**
     * @throws \RuntimeException
     */
    public function setCustomerGroupCreatedAt(string $uuid, string $createdAt): void
    {
        $customerGroupEntity = SpyCustomerGroupQuery::create()->findOneByUuid($uuid);

        if ($customerGroupEntity === null) {
            throw new RuntimeException(sprintf('No customer group with uuid "%s".', $uuid));
        }

        $customerGroupEntity->setCreatedAt($createdAt)->save();
    }

    public function countCustomerGroupsWithName(string $name): int
    {
        return SpyCustomerGroupQuery::create()->filterByName($name)->count();
    }

    public function countUnauthenticatedCustomerAccessRows(): int
    {
        return SpyUnauthenticatedCustomerAccessQuery::create()->count();
    }

    /**
     * @return array<string, bool>
     */
    public function getUnauthenticatedCustomerAccessState(): array
    {
        $state = [];

        foreach (SpyUnauthenticatedCustomerAccessQuery::create()->orderByIdUnauthenticatedCustomerAccess()->find() as $entity) {
            $state[$entity->getContentType()] = (bool)$entity->getIsRestricted();
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildCompanyRoleRequestBody(array $attributes, ?string $uuid = null): string
    {
        return $this->buildRequestBody(static::RESOURCE_COMPANY_ROLES, $attributes, $uuid);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCompanyRoleCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_COMPANY_ROLES) . $this->formatQuery($query);
    }

    public function getCompanyRoleUrl(string $uuid): string
    {
        return sprintf('/%s/%s', static::RESOURCE_COMPANY_ROLES, $uuid);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCompanyRolePermissionCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_COMPANY_ROLE_PERMISSIONS) . $this->formatQuery($query);
    }

    /**
     * @throws \RuntimeException
     */
    public function getDefaultCompanyRoleUuid(int $idCompany): string
    {
        $companyRoleEntity = SpyCompanyRoleQuery::create()
            ->filterByFkCompany($idCompany)
            ->filterByIsDefault(true)
            ->findOne();

        if ($companyRoleEntity === null) {
            throw new RuntimeException(sprintf('Company %d has no default company role.', $idCompany));
        }

        return (string)$companyRoleEntity->getUuid();
    }

    public function isCompanyRoleDefault(string $uuid): bool
    {
        return (bool)SpyCompanyRoleQuery::create()->findOneByUuid($uuid)?->getIsDefault();
    }

    public function hasCompanyRole(string $uuid): bool
    {
        return SpyCompanyRoleQuery::create()->filterByUuid($uuid)->exists();
    }

    public function countDefaultCompanyRoles(int $idCompany): int
    {
        return SpyCompanyRoleQuery::create()
            ->filterByFkCompany($idCompany)
            ->filterByIsDefault(true)
            ->count();
    }

    protected function getRequestHelper(): BackendApiRequestHelper
    {
        /** @var \SprykerFeatureTest\Glue\CustomerExperienceManagement\Helper\CustomerExperienceManagementBackendApiHelper $requestHelper */
        $requestHelper = $this->getModule('\\' . BackendApiRequestHelper::class);

        return $requestHelper;
    }
}
