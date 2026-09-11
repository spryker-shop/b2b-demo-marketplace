<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\CustomerExperienceManagement\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SpyCustomerNoteEntityTransfer;
use Generated\Shared\Transfer\UserTransfer;
use SprykerTest\Shared\Customer\Helper\CustomerDataHelper;
use SprykerTest\Shared\CustomerNote\Helper\CustomerNoteDataHelper;
use SprykerTest\Shared\User\Helper\UserDataHelper;

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
    public const string RESOURCE_CUSTOMERS = 'customers';

    public const string RESOURCE_ADDRESSES = 'addresses';

    public const string RESOURCE_NOTES = 'notes';

    /**
     * Distinguishes the customers of one test method from every other row in the database, so a
     * free-text search can isolate exactly them. The suite rolls its writes back, but the database
     * it runs against is the shared development one and already holds demo customers.
     */
    protected const string LISTED_LAST_NAME_PREFIX = 'CxmListed';

    protected const string LISTED_FIRST_NAME_FIRST = 'Aaron';

    protected const string LISTED_FIRST_NAME_SECOND = 'Zoe';

    protected const string ISO2_CODE = 'DE';

    protected const string COUNTRY_NAME = 'Germany';

    protected const string ADDRESSEE_LAST_NAME = 'CxmAddressee';

    protected const string ADDRESS1 = 'Julie-Wolfthorn-Strasse';

    protected const string ADDRESS2 = '1';

    protected const string CITY_FIRST_ADDRESS = 'Aachen';

    protected const string CITY_SECOND_ADDRESS = 'Berlin';

    protected const string ZIP_CODE_FIRST_ADDRESS = '52062';

    protected const string ZIP_CODE_SECOND_ADDRESS = '10115';

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
        ];
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
