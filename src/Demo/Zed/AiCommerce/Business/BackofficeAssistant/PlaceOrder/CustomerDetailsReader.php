<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\AiCommerce\Business\BackofficeAssistant\PlaceOrder;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;

class CustomerDetailsReader implements CustomerDetailsReaderInterface
{
    public function __construct(
        protected readonly CustomerFacadeInterface $customerFacade,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $arguments
     */
    public function getCustomerDetails(array $arguments): string
    {
        $customerReference = (string)($arguments['customerReference'] ?? '');

        if ($customerReference === '') {
            return json_encode(['error' => 'customerReference is required'], JSON_THROW_ON_ERROR);
        }

        $customerResponseTransfer = $this->customerFacade->findCustomerByReference($customerReference);

        if (!$customerResponseTransfer->getHasCustomer() || !$customerResponseTransfer->getCustomerTransfer()) {
            return json_encode(
                ['error' => sprintf('Customer with reference "%s" not found', $customerReference)],
                JSON_THROW_ON_ERROR,
            );
        }

        $customerTransfer = $customerResponseTransfer->getCustomerTransfer();

        return json_encode($this->mapCustomerToArray($customerTransfer), JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<\Generated\Shared\Transfer\AddressTransfer> $addressTransfers
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mapAddressesToArray(array $addressTransfers): array
    {
        $addresses = [];

        foreach ($addressTransfers as $addressTransfer) {
            $addresses[] = $this->mapAddressToArray($addressTransfer);
        }

        return $addresses;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapAddressToArray(AddressTransfer $addressTransfer): array
    {
        return [
            'idCustomerAddress' => $addressTransfer->getIdCustomerAddress(),
            'salutation' => $addressTransfer->getSalutation(),
            'firstName' => $addressTransfer->getFirstName(),
            'lastName' => $addressTransfer->getLastName(),
            'company' => $addressTransfer->getCompany(),
            'address1' => $addressTransfer->getAddress1(),
            'address2' => $addressTransfer->getAddress2(),
            'address3' => $addressTransfer->getAddress3(),
            'city' => $addressTransfer->getCity(),
            'zipCode' => $addressTransfer->getZipCode(),
            'iso2Code' => $addressTransfer->getIso2Code(),
            'phone' => $addressTransfer->getPhone(),
            'isDefaultBilling' => $addressTransfer->getIsDefaultBilling(),
            'isDefaultShipping' => $addressTransfer->getIsDefaultShipping(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapCustomerToArray(CustomerTransfer $customerTransfer): array
    {
        $addressesTransfer = $customerTransfer->getAddresses();

        return [
            'customerReference' => $customerTransfer->getCustomerReference(),
            'email' => $customerTransfer->getEmail(),
            'salutation' => $customerTransfer->getSalutation(),
            'firstName' => $customerTransfer->getFirstName(),
            'lastName' => $customerTransfer->getLastName(),
            'company' => $customerTransfer->getCompany(),
            'phone' => $customerTransfer->getPhone(),
            'dateOfBirth' => $customerTransfer->getDateOfBirth(),
            'addresses' => $addressesTransfer ? $this->mapAddressesToArray($addressesTransfer->getAddresses()->getArrayCopy()) : [],
        ];
    }
}
