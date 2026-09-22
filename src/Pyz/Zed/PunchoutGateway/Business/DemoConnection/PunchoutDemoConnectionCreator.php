<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PunchoutGateway\Business\DemoConnection;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;
use Generated\Shared\Transfer\PunchoutConnectionCriteriaTransfer;
use Generated\Shared\Transfer\PunchoutConnectionTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayEntityManagerInterface;
use SprykerEco\Zed\PunchoutGateway\Persistence\PunchoutGatewayRepositoryInterface;

class PunchoutDemoConnectionCreator implements PunchoutDemoConnectionCreatorInterface
{
    public function __construct(
        protected PunchoutGatewayRepositoryInterface $punchoutGatewayRepository,
        protected PunchoutGatewayEntityManagerInterface $punchoutGatewayEntityManager,
        protected StoreFacadeInterface $storeFacade,
        protected CustomerFacadeInterface $customerFacade,
    ) {
    }

    public function createDemoPunchoutConnections(
        PunchoutConnectionCollectionTransfer $punchoutConnectionCollectionTransfer,
    ): PunchoutConnectionCollectionTransfer {
        $createdPunchoutConnectionCollectionTransfer = new PunchoutConnectionCollectionTransfer();

        foreach ($punchoutConnectionCollectionTransfer->getPunchoutConnections() as $punchoutConnectionTransfer) {
            $punchoutConnectionTransfer->setIdStore(
                $this->storeFacade
                    ->getStoreByName($punchoutConnectionTransfer->getStoreNameOrFail())
                    ->getIdStoreOrFail(),
            );

            if ($this->hasPunchoutConnection($punchoutConnectionTransfer)) {
                continue;
            }

            $punchoutConnectionTransfer = $this->punchoutGatewayEntityManager->createPunchoutConnection($punchoutConnectionTransfer);
            $this->createPunchoutCredential($punchoutConnectionTransfer);

            $createdPunchoutConnectionCollectionTransfer->addPunchoutConnection($punchoutConnectionTransfer);
        }

        return $createdPunchoutConnectionCollectionTransfer;
    }

    protected function hasPunchoutConnection(PunchoutConnectionTransfer $punchoutConnectionTransfer): bool
    {
        $punchoutCxmlConfigurationTransfer = $punchoutConnectionTransfer->getCxmlConfiguration();

        if ($punchoutCxmlConfigurationTransfer?->getSenderIdentity() !== null) {
            return $this->punchoutGatewayRepository->findCxmlConnectionBySenderIdentity(
                $punchoutCxmlConfigurationTransfer->getSenderIdentity(),
            ) !== null;
        }

        if ($punchoutConnectionTransfer->getRequestUrl() === null) {
            return false;
        }

        $punchoutConnectionCriteriaTransfer = (new PunchoutConnectionCriteriaTransfer())
            ->setIdStore($punchoutConnectionTransfer->getIdStoreOrFail())
            ->setRequestUrl($punchoutConnectionTransfer->getRequestUrl());

        return $this->punchoutGatewayRepository
            ->getPunchoutConnectionCollection($punchoutConnectionCriteriaTransfer)
            ->getPunchoutConnections()
            ->count() !== 0;
    }

    protected function createPunchoutCredential(PunchoutConnectionTransfer $punchoutConnectionTransfer): void
    {
        $punchoutCredentialTransfer = $punchoutConnectionTransfer->getCredential();

        if ($punchoutCredentialTransfer === null) {
            return;
        }

        $customerEmail = $punchoutCredentialTransfer->getCustomerEmailOrFail();

        if (!$this->customerFacade->hasEmail($customerEmail)) {
            return;
        }

        $customerTransfer = $this->customerFacade->getCustomer(
            (new CustomerTransfer())->setEmail($customerEmail),
        );

        $punchoutCredentialTransfer
            ->setIdPunchoutConnection($punchoutConnectionTransfer->getIdPunchoutConnectionOrFail())
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setPasswordHash(password_hash($punchoutCredentialTransfer->getPasswordOrFail(), PASSWORD_DEFAULT));

        $this->punchoutGatewayEntityManager->createPunchoutCredential($punchoutCredentialTransfer);
    }
}
