<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Orm\Zed\CompanyUnitAddress\Persistence\SpyCompanyUnitAddressQuery;
use Pyz\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;

class AddressResolver
{
    public function __construct(protected CustomerFacadeInterface $customerFacade)
    {
    }

    public function resolveBillingAddress(CustomerTransfer $customerTransfer): AddressTransfer
    {
        return $this->resolveCustomerAddress($customerTransfer, $customerTransfer->getDefaultBillingAddress() ?? (int)$customerTransfer->getDefaultBillingAddress())
            ?? $this->buildCompanyBusinessUnitAddress($customerTransfer);
    }

    public function resolveShippingAddress(CustomerTransfer $customerTransfer, AddressTransfer $billingAddressTransfer): AddressTransfer
    {
        return $this->resolveCustomerAddress($customerTransfer, $customerTransfer->getDefaultShippingAddress() ?? (int)$customerTransfer->getDefaultShippingAddress())
            ?? (new AddressTransfer())->fromArray($billingAddressTransfer->toArray());
    }

    protected function resolveCustomerAddress(CustomerTransfer $customerTransfer, ?int $idDefaultAddress): ?AddressTransfer
    {
        $addressesTransfer = $this->customerFacade->getAddresses($customerTransfer);
        $fallbackAddressTransfer = null;

        foreach ($addressesTransfer->getAddresses() as $addressTransfer) {
            if ($idDefaultAddress !== null && $addressTransfer->getIdCustomerAddress() === (int)$idDefaultAddress) {
                return (new AddressTransfer())->fromArray($addressTransfer->toArray());
            }

            $fallbackAddressTransfer = $fallbackAddressTransfer ?? $addressTransfer;
        }

        if (!$fallbackAddressTransfer) {
            return null;
        }

        return (new AddressTransfer())->fromArray($fallbackAddressTransfer->toArray());
    }

    protected function buildCompanyBusinessUnitAddress(CustomerTransfer $customerTransfer): AddressTransfer
    {
        $idCompanyBusinessUnit = $customerTransfer->getCompanyUserTransfer()?->getFkCompanyBusinessUnit();

        if (!$idCompanyBusinessUnit) {
            throw new EntityNotFoundException(sprintf(
                'Customer "%s" has no address book entries and no company business unit to take an address from.',
                $customerTransfer->getCustomerReference(),
            ));
        }

        $companyUnitAddressEntity = SpyCompanyUnitAddressQuery::create()
            ->useSpyCompanyUnitAddressToCompanyBusinessUnitQuery()
                ->filterByFkCompanyBusinessUnit($idCompanyBusinessUnit)
            ->endUse()
            ->findOne();

        if (!$companyUnitAddressEntity) {
            throw new EntityNotFoundException(sprintf(
                'Customer "%s" has no address book entries and the company business unit has no address.',
                $customerTransfer->getCustomerReference(),
            ));
        }

        return (new AddressTransfer())
            ->setFirstName($customerTransfer->getFirstName())
            ->setLastName($customerTransfer->getLastName())
            ->setSalutation($customerTransfer->getSalutation())
            ->setEmail($customerTransfer->getEmail())
            ->setAddress1($companyUnitAddressEntity->getAddress1())
            ->setAddress2($companyUnitAddressEntity->getAddress2())
            ->setAddress3($companyUnitAddressEntity->getAddress3())
            ->setZipCode($companyUnitAddressEntity->getZipCode())
            ->setCity($companyUnitAddressEntity->getCity())
            ->setPhone($companyUnitAddressEntity->getPhone())
            ->setIso2Code($companyUnitAddressEntity->getCountry()->getIso2Code());
    }
}
