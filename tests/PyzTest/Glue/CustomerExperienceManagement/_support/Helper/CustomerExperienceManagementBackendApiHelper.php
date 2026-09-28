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
use Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery;
use Orm\Zed\Customer\Persistence\SpyCustomerQuery;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use SprykerTest\Shared\Customer\Helper\CustomerDataHelper;
use SprykerTest\Shared\CustomerNote\Helper\CustomerNoteDataHelper;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use SprykerTest\Shared\User\Helper\UserDataHelper;
use SprykerTest\Zed\Company\Helper\CompanyHelper;
use SprykerTest\Zed\CompanyBusinessUnit\Helper\CompanyBusinessUnitHelper;
use SprykerTest\Zed\CompanyRole\Helper\CompanyRoleHelper;

/**
 * Request building and fixture arrangement for every resource of the CustomerExperienceManagement
 * Backend API: customers, their addresses and their notes.
 *
 * One module rather than one per resource, because Codeception merges every enabled module into a
 * single actor and a method name defined in two of them would collide there.
 *
 * Routes are returned as paths with a leading slash, which is what
 * {@see \SprykerTest\ApiPlatform\Test\AbstractApiTestCase::handleApiRequest()} resolves against the
 * suite's base URL.
 */
class CustomerExperienceManagementBackendApiHelper extends Module
{
    use LocatorHelperTrait;

    public const string RESOURCE_CUSTOMERS = 'customers';

    public const string RESOURCE_COMPANY_USERS = 'company-users';

    public const string RESOURCE_ADDRESSES = 'addresses';

    public const string RESOURCE_NOTES = 'notes';

    public const string OPERATION_SET_STATUS = 'set-status';

    public const string OPERATION_SET_DEFAULT = 'set-default';

    public const string RESOURCE_COMPANIES = 'companies';

    /**
     * Distinguishes the customers of one test method from every other row in the database, so a
     * free-text search can isolate exactly them. The suite rolls its writes back, but the database
     * it runs against is the shared development one and already holds demo customers.
     */
    protected const string LISTED_LAST_NAME_PREFIX = 'CxmListed';

    protected const string LISTED_COMPANY_NAME_PREFIX = 'CxmListedCompany';

    protected const string LISTED_COMPANY_NAME_FIRST = 'Aaa';

    protected const string LISTED_COMPANY_NAME_SECOND = 'Zzz';

    public const string LISTED_FIRST_NAME_FIRST = 'Aaron';

    public const string LISTED_FIRST_NAME_SECOND = 'Zoe';

    protected const string ISO2_CODE = 'DE';

    protected const string STORE_NAME = 'DE';

    protected const string COUNTRY_NAME = 'Germany';

    protected const string ADDRESSEE_LAST_NAME = 'CxmAddressee';

    protected const string ADDRESS1 = 'Julie-Wolfthorn-Strasse';

    protected const string ADDRESS2 = '1';

    protected const string CITY_FIRST_ADDRESS = 'Aachen';

    protected const string CITY_SECOND_ADDRESS = 'Berlin';

    protected const string ZIP_CODE_FIRST_ADDRESS = '52062';

    protected const string ZIP_CODE_SECOND_ADDRESS = '10115';

    /**
     * Resource-only: the company user resource addresses its related entities by public reference
     * and uuid, none of which the CompanyUser transfer has a counterpart for.
     */
    protected const string ATTRIBUTE_COMPANY_UUID = 'companyUuid';

    protected const string ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID = 'companyBusinessUnitUuid';

    protected const string ATTRIBUTE_COMPANY_ROLE_UUIDS = 'companyRoleUuids';

    public function getIso2Code(): string
    {
        return static::ISO2_CODE;
    }

    public function getCountryName(): string
    {
        return static::COUNTRY_NAME;
    }

    public function getFirstAddressCity(): string
    {
        return static::CITY_FIRST_ADDRESS;
    }

    public function getSecondAddressCity(): string
    {
        return static::CITY_SECOND_ADDRESS;
    }

    // ---------------------------------------------------------------- companies

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidCompanyAttributes(array $override = []): array
    {
        return $override + [
            CompanyTransfer::NAME => uniqid('CxmCompany', false),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildCompanyRequestBody(array $attributes, ?string $uuid = null): string
    {
        return $this->buildRequestBody(static::RESOURCE_COMPANIES, $attributes, $uuid);
    }

    /**
     * @return non-empty-string
     */
    public function getCompanyUrl(string $uuid): string
    {
        return sprintf('/%s/%s', static::RESOURCE_COMPANIES, $uuid);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCompanyCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_COMPANIES) . $this->formatQuery($query);
    }

    public function buildListedCompanyNameToken(): string
    {
        return uniqid(static::LISTED_COMPANY_NAME_PREFIX);
    }

    public function buildFirstListedCompanyName(string $token): string
    {
        return $token . static::LISTED_COMPANY_NAME_FIRST;
    }

    public function buildSecondListedCompanyName(string $token): string
    {
        return $token . static::LISTED_COMPANY_NAME_SECOND;
    }

    // ---------------------------------------------------------------- customers

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidCustomerAttributes(array $override = []): array
    {
        return $override + [
            CustomerTransfer::EMAIL => uniqid('cxm.backend.api.', true) . '@spryker.local',
            CustomerTransfer::SALUTATION => 'Mr',
            CustomerTransfer::FIRST_NAME => 'Created',
            CustomerTransfer::LAST_NAME => 'ViaBackendApi',
            CustomerTransfer::STORE_NAME => static::STORE_NAME,
        ];
    }

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
     * @param array<string, mixed> $attributes
     */
    public function buildCustomerRequestBody(array $attributes, ?string $customerReference = null): string
    {
        return $this->buildRequestBody(static::RESOURCE_CUSTOMERS, $attributes, $customerReference);
    }

    /**
     * @return non-empty-string
     */
    public function getCustomerUrl(string $customerReference): string
    {
        return sprintf('/%s/%s', static::RESOURCE_CUSTOMERS, $customerReference);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCustomerUrlWithQuery(string $customerReference, array $query = []): string
    {
        return $this->getCustomerUrl($customerReference) . $this->formatQuery($query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCustomerCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_CUSTOMERS) . $this->formatQuery($query);
    }

    /**
     * @return array<\Generated\Shared\Transfer\CustomerTransfer>
     */
    public function haveTwoListedCustomers(): array
    {
        $listedLastName = uniqid(static::LISTED_LAST_NAME_PREFIX);

        return [
            $this->haveListedCustomer(static::LISTED_FIRST_NAME_FIRST, $listedLastName),
            $this->haveListedCustomer(static::LISTED_FIRST_NAME_SECOND, $listedLastName),
        ];
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidCustomerAddressAttributes(array $override = []): array
    {
        return $override + [
            AddressTransfer::SALUTATION => 'Mr',
            AddressTransfer::FIRST_NAME => 'Created',
            AddressTransfer::LAST_NAME => 'ViaBackendApi',
            AddressTransfer::ADDRESS1 => static::ADDRESS1,
            AddressTransfer::ADDRESS2 => static::ADDRESS2,
            AddressTransfer::CITY => static::CITY_SECOND_ADDRESS,
            AddressTransfer::ZIP_CODE => static::ZIP_CODE_SECOND_ADDRESS,
            AddressTransfer::ISO2_CODE => static::ISO2_CODE,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildCustomerAddressRequestBody(array $attributes, ?string $uuid = null): string
    {
        return $this->buildRequestBody(static::RESOURCE_ADDRESSES, $attributes, $uuid);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCustomerAddressCollectionUrl(string $customerReference, array $query = []): string
    {
        return sprintf(
            '/%s/%s/%s',
            static::RESOURCE_CUSTOMERS,
            $customerReference,
            static::RESOURCE_ADDRESSES,
        ) . $this->formatQuery($query);
    }

    /**
     * @return non-empty-string
     */
    public function getCustomerAddressUrl(string $customerReference, string $uuid): string
    {
        return sprintf(
            '/%s/%s/%s/%s',
            static::RESOURCE_CUSTOMERS,
            $customerReference,
            static::RESOURCE_ADDRESSES,
            $uuid,
        );
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
                AddressTransfer::CITY => static::CITY_FIRST_ADDRESS,
                AddressTransfer::ZIP_CODE => static::ZIP_CODE_FIRST_ADDRESS,
            ]),
            $this->haveCustomerAddressFor($customerTransfer, [
                AddressTransfer::FIRST_NAME => static::LISTED_FIRST_NAME_SECOND,
                AddressTransfer::CITY => static::CITY_SECOND_ADDRESS,
                AddressTransfer::ZIP_CODE => static::ZIP_CODE_SECOND_ADDRESS,
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
            AddressTransfer::ISO2_CODE => static::ISO2_CODE,
            AddressTransfer::LAST_NAME => static::ADDRESSEE_LAST_NAME,
            AddressTransfer::ADDRESS1 => static::ADDRESS1,
            AddressTransfer::ADDRESS2 => static::ADDRESS2,
        ]);
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidCustomerNoteAttributes(array $override = []): array
    {
        return $override + [
            SpyCustomerNoteEntityTransfer::MESSAGE => 'Called the customer about invoice 4711.',
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildCustomerNoteRequestBody(array $attributes): string
    {
        return $this->buildRequestBody(static::RESOURCE_NOTES, $attributes);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function getCustomerNoteCollectionUrl(string $customerReference, array $query = []): string
    {
        return sprintf(
            '/%s/%s/%s',
            static::RESOURCE_CUSTOMERS,
            $customerReference,
            static::RESOURCE_NOTES,
        ) . $this->formatQuery($query);
    }

    /**
     * @return non-empty-string
     */
    public function getCustomerNoteUrl(string $customerReference, string $uuid): string
    {
        return sprintf(
            '/%s/%s/%s/%s',
            static::RESOURCE_CUSTOMERS,
            $customerReference,
            static::RESOURCE_NOTES,
            $uuid,
        );
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

    // ---------------------------------------------------------------- company users

    /**
     * @param array<string, mixed> $query
     */
    public function getCompanyUserCollectionUrl(array $query = []): string
    {
        return sprintf('/%s', static::RESOURCE_COMPANY_USERS) . $this->formatQuery($query);
    }

    /**
     * @return non-empty-string
     */
    public function getCompanyUserUrl(string $uuid): string
    {
        return sprintf('/%s/%s', static::RESOURCE_COMPANY_USERS, $uuid);
    }

    /**
     * @return non-empty-string
     */
    public function getCompanyUserSetStatusUrl(string $uuid): string
    {
        return sprintf('/%s/%s/%s', static::RESOURCE_COMPANY_USERS, $uuid, static::OPERATION_SET_STATUS);
    }

    /**
     * @return non-empty-string
     */
    public function getCompanyUserSetDefaultUrl(string $uuid): string
    {
        return sprintf('/%s/%s/%s', static::RESOURCE_COMPANY_USERS, $uuid, static::OPERATION_SET_DEFAULT);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function buildCompanyUserRequestBody(array $attributes): string
    {
        return $this->buildRequestBody(static::RESOURCE_COMPANY_USERS, $attributes);
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

    public function getListedCompanyUserLastName(): string
    {
        return uniqid(static::LISTED_LAST_NAME_PREFIX);
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
        $customerTransfer = $this->haveCompanyUserCustomer($firstName, $lastName ?? $this->getListedCompanyUserLastName());

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

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildValidCompanyUserAttributes(
        CompanyTransfer $companyTransfer,
        CompanyBusinessUnitTransfer $companyBusinessUnitTransfer,
        CompanyRoleTransfer $companyRoleTransfer,
        array $override = [],
    ): array {
        return $override + [
            static::ATTRIBUTE_COMPANY_UUID => $companyTransfer->getUuidOrFail(),
            static::ATTRIBUTE_COMPANY_BUSINESS_UNIT_UUID => $companyBusinessUnitTransfer->getUuidOrFail(),
            static::ATTRIBUTE_COMPANY_ROLE_UUIDS => [$companyRoleTransfer->getUuidOrFail()],
        ];
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    public function buildNewCustomerAttributes(array $override = []): array
    {
        return $override + [
            CustomerTransfer::EMAIL => uniqid('cxm.company.user.', true) . '@spryker.local',
            CustomerTransfer::SALUTATION => 'Mr',
            CustomerTransfer::FIRST_NAME => 'Created',
            CustomerTransfer::LAST_NAME => 'ViaBackendApi',
        ];
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
}
