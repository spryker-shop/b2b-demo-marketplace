<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\DataImport\Business\Model\SalesOrder;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Orm\Zed\CompanyUser\Persistence\SpyCompanyUserQuery;
use Orm\Zed\PurchasingControl\Persistence\SpyBudgetQuery;
use Orm\Zed\PurchasingControl\Persistence\SpyCostCenterQuery;
use Pyz\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Spryker\Shared\Price\PriceConfig;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Currency\Business\CurrencyFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;

class QuoteBuilder
{
    public function __construct(
        protected CustomerFacadeInterface $customerFacade,
        protected CompanyUserFacadeInterface $companyUserFacade,
        protected StoreFacadeInterface $storeFacade,
        protected CurrencyFacadeInterface $currencyFacade,
        protected AddressResolver $addressResolver,
    ) {
    }

    public function buildQuote(DataSetInterface $dataSet): QuoteTransfer
    {
        $customerTransfer = $this->getCustomer($dataSet[SalesOrderDataSetInterface::COLUMN_CUSTOMER_REFERENCE]);

        $quoteTransfer = (new QuoteTransfer())
            ->setStore($this->storeFacade->getStoreByName($dataSet[SalesOrderDataSetInterface::COLUMN_STORE]))
            ->setCurrency($this->currencyFacade->fromIsoCode($dataSet[SalesOrderDataSetInterface::COLUMN_CURRENCY]))
            ->setPriceMode(PriceConfig::PRICE_MODE_GROSS)
            ->setOrderReference($dataSet[SalesOrderDataSetInterface::COLUMN_ORDER_REFERENCE])
            ->setUuid('demo-order-' . $dataSet[SalesOrderDataSetInterface::COLUMN_ORDER_REFERENCE]);

        if (!empty($dataSet[SalesOrderDataSetInterface::COLUMN_COMPANY_USER_KEY])) {
            $companyUserTransfer = $this->getCompanyUser($dataSet[SalesOrderDataSetInterface::COLUMN_COMPANY_USER_KEY]);
            $customerTransfer->setCompanyUserTransfer($companyUserTransfer);
            $quoteTransfer->setCompanyUserId($companyUserTransfer->getIdCompanyUser());
        }

        if (!empty($dataSet[SalesOrderDataSetInterface::COLUMN_COST_CENTER_KEY])) {
            $this->addCostCenterWithBudget(
                $quoteTransfer,
                $dataSet[SalesOrderDataSetInterface::COLUMN_COST_CENTER_KEY],
                $dataSet[SalesOrderDataSetInterface::COLUMN_BUDGET_NAME] ?? null,
            );
        }

        $billingAddressTransfer = $this->addressResolver->resolveBillingAddress($customerTransfer);
        $shippingAddressTransfer = $this->addressResolver->resolveShippingAddress($customerTransfer, $billingAddressTransfer);

        return $quoteTransfer
            ->setCustomer($customerTransfer)
            ->setCustomerReference($customerTransfer->getCustomerReference())
            ->setBillingAddress($billingAddressTransfer)
            ->setShippingAddress($shippingAddressTransfer);
    }

    protected function getCustomer(string $customerReference): CustomerTransfer
    {
        $customerTransfer = $this->customerFacade->findByReference($customerReference);

        if (!$customerTransfer) {
            throw new EntityNotFoundException(sprintf('Customer with reference "%s" is not found.', $customerReference));
        }

        return $customerTransfer;
    }

    protected function addCostCenterWithBudget(QuoteTransfer $quoteTransfer, string $costCenterKey, ?string $budgetName): void
    {
        $costCenterEntity = SpyCostCenterQuery::create()->findOneByUuid($costCenterKey);

        if (!$costCenterEntity) {
            throw new EntityNotFoundException(sprintf('Cost center with key "%s" is not found.', $costCenterKey));
        }

        $quoteTransfer->setIdCostCenter($costCenterEntity->getIdCostCenter());

        if (!$budgetName) {
            return;
        }

        $budgetEntity = SpyBudgetQuery::create()
            ->filterByFkCostCenter($costCenterEntity->getIdCostCenter())
            ->findOneByName($budgetName);

        if (!$budgetEntity) {
            throw new EntityNotFoundException(sprintf(
                'Budget with name "%s" is not found for cost center "%s".',
                $budgetName,
                $costCenterKey,
            ));
        }

        $quoteTransfer->setIdBudget($budgetEntity->getIdBudget());
    }

    protected function getCompanyUser(string $companyUserKey): CompanyUserTransfer
    {
        $companyUserEntity = SpyCompanyUserQuery::create()
            ->findOneByKey($companyUserKey);

        if (!$companyUserEntity) {
            throw new EntityNotFoundException(sprintf('Company user with key "%s" is not found.', $companyUserKey));
        }

        return $this->companyUserFacade->getCompanyUserById($companyUserEntity->getIdCompanyUser());
    }
}
