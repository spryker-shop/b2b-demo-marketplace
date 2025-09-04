<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\TenantOnboarding\Persistence;

use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenantRegistration;
use Orm\Zed\TenantOnboarding\Persistence\PyzTenant;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Pyz\Zed\TenantOnboarding\Persistence\TenantOnboardingPersistenceFactory getFactory()
 */
class TenantOnboardingEntityManager extends AbstractEntityManager implements TenantOnboardingEntityManagerInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer
     */
    public function createTenantRegistration(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationTransfer
    {
        $entity = new PyzTenantRegistration();
        $entity->fromArray($tenantRegistrationTransfer->toArray());
        $entity->save();

        $tenantRegistrationTransfer->setIdTenantRegistration($entity->getIdTenantRegistration());
        $tenantRegistrationTransfer->setCreatedAt($entity->getCreatedAt('Y-m-d H:i:s'));
        $tenantRegistrationTransfer->setUpdatedAt($entity->getUpdatedAt('Y-m-d H:i:s'));

        return $tenantRegistrationTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationTransfer $tenantRegistrationTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer
     */
    public function updateTenantRegistration(TenantRegistrationTransfer $tenantRegistrationTransfer): TenantRegistrationTransfer
    {
        $entity = $this->getFactory()
            ->createTenantRegistrationQuery()
            ->filterByIdTenantRegistration($tenantRegistrationTransfer->getIdTenantRegistration())
            ->findOne();

        if (!$entity) {
            return $tenantRegistrationTransfer;
        }

        $entity->fromArray($tenantRegistrationTransfer->toArray(false));

        if ($tenantRegistrationTransfer->getTenant() && $tenantRegistrationTransfer->getTenant()->getIdTenant()) {
            $entity->setFkTenant($tenantRegistrationTransfer->getTenant()->getIdTenant());
        }

        $entity->save();

        $tenantRegistrationTransfer->setUpdatedAt($entity->getUpdatedAt('Y-m-d H:i:s'));

        return $tenantRegistrationTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function createTenant(TenantTransfer $tenantTransfer): TenantTransfer
    {
        $entity = new PyzTenant();
        $entity->fromArray($tenantTransfer->toArray());
        $entity->save();

        $tenantTransfer->setIdTenant($entity->getIdTenant());
        $tenantTransfer->setCreatedAt($entity->getCreatedAt('Y-m-d H:i:s'));
        $tenantTransfer->setUpdatedAt($entity->getUpdatedAt('Y-m-d H:i:s'));

        return $tenantTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\TenantTransfer $tenantTransfer
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function updateTenant(TenantTransfer $tenantTransfer): TenantTransfer
    {
        $entity = $this->getFactory()
            ->createTenantQuery()
            ->filterByIdTenant($tenantTransfer->getIdTenant())
            ->findOne();

        if (!$entity) {
            return $tenantTransfer;
        }

        $entity->fromArray($tenantTransfer->toArray());
        $entity->save();

        $tenantTransfer->setUpdatedAt($entity->getUpdatedAt('Y-m-d H:i:s'));

        return $tenantTransfer;
    }

    /**
     * @param int $idTenant
     *
     * @return void
     */
    public function deleteTenant(int $idTenant): void
    {
        $entity = $this->getFactory()
            ->createTenantQuery()
            ->filterByIdTenant($idTenant)
            ->findOne();

        if ($entity) {
            $entity->delete();
        }
    }
}
