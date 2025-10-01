<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Go\Zed\TenantOnboarding\Persistence;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\TenantCollectionTransfer;
use Generated\Shared\Transfer\TenantCriteriaTransfer;
use Generated\Shared\Transfer\TenantRegistrationCollectionTransfer;
use Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer;
use Generated\Shared\Transfer\TenantRegistrationTransfer;
use Generated\Shared\Transfer\TenantTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Go\Zed\TenantOnboarding\Persistence\TenantOnboardingPersistenceFactory getFactory()
 */
class TenantOnboardingRepository extends AbstractRepository implements TenantOnboardingRepositoryInterface
{
    /**
     * @param \Generated\Shared\Transfer\TenantRegistrationCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationCollectionTransfer
     */
    public function getTenantRegistrations(TenantRegistrationCriteriaTransfer $criteriaTransfer): TenantRegistrationCollectionTransfer
    {
        $query = $this->getFactory()->createTenantRegistrationQuery()
            ->leftJoinPyzTenant();

        if ($criteriaTransfer->getStatus()) {
            $query->filterByStatus($criteriaTransfer->getStatus());
        }

        if ($criteriaTransfer->getEmail()) {
            $query->filterByEmail($criteriaTransfer->getEmail());
        }

        if ($criteriaTransfer->getTenantName()) {
            $query->filterByTenantName($criteriaTransfer->getTenantName());
        }

        $total = $query->count();

        if ($criteriaTransfer->getLimit()) {
            $query->limit($criteriaTransfer->getLimit());
        }

        if ($criteriaTransfer->getOffset()) {
            $query->offset($criteriaTransfer->getOffset());
        }

        $entities = $query->find();

        $collectionTransfer = new TenantRegistrationCollectionTransfer();

        foreach ($entities as $entity) {
            $tenantRegistrationTransfer = new TenantRegistrationTransfer();
            $tenantRegistrationTransfer->fromArray($entity->toArray(), true);

            if ($entity->getFkTenant() && $entity->getPyzTenant()) {
                $tenantTransfer = new TenantTransfer();
                $tenantTransfer->fromArray($entity->getPyzTenant()->toArray(), true);
                $tenantRegistrationTransfer->setTenant($tenantTransfer);
            }

            $collectionTransfer->addTenantRegistration($tenantRegistrationTransfer);
        }

        $paginationTransfer = new PaginationTransfer();
        $paginationTransfer->setNbResults($total);
        $paginationTransfer->setMaxPerPage($criteriaTransfer->getLimit() ?: $total);
        $paginationTransfer->setPage($criteriaTransfer->getOffset() ? ($criteriaTransfer->getOffset() / ($criteriaTransfer->getLimit() ?: 1)) + 1 : 1);

        $collectionTransfer->setPagination($paginationTransfer);

        return $collectionTransfer;
    }

    /**
     * @param int $idTenantRegistration
     *
     * @return \Generated\Shared\Transfer\TenantRegistrationTransfer|null
     */
    public function findTenantRegistrationById(int $idTenantRegistration): ?TenantRegistrationTransfer
    {
        $entity = $this->getFactory()
            ->createTenantRegistrationQuery()
            ->filterByIdTenantRegistration($idTenantRegistration)
            ->leftJoinPyzTenant()
            ->findOne();

        if (!$entity) {
            return null;
        }

        $tenantRegistrationTransfer = new TenantRegistrationTransfer();
        $tenantRegistrationTransfer->fromArray($entity->toArray(), true);

        if ($entity->getFkTenant() && $entity->getPyzTenant()) {
            $tenantTransfer = new TenantTransfer();
            $tenantTransfer->fromArray($entity->getPyzTenant()->toArray(), true);
            $tenantRegistrationTransfer->setTenant($tenantTransfer);
        }

        return $tenantRegistrationTransfer;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isEmailAvailable(string $email): bool
    {
        $count = $this->getFactory()
            ->createTenantRegistrationQuery()
            ->filterByEmail($email)
            ->count();

        return $count === 0;
    }

    /**
     * @param string $tenantName
     *
     * @return bool
     */
    public function isTenantNameAvailable(string $tenantName): bool
    {
        $count = $this->getFactory()
            ->createTenantRegistrationQuery()
            ->filterByTenantName($tenantName)
            ->count();

        return $count === 0;
    }

    /**
     * @param \Generated\Shared\Transfer\TenantCriteriaTransfer $criteriaTransfer
     *
     * @return \Generated\Shared\Transfer\TenantCollectionTransfer
     */
    public function getTenants(TenantCriteriaTransfer $criteriaTransfer): TenantCollectionTransfer
    {
        $query = $this->getFactory()->createTenantQuery();

        if ($criteriaTransfer->getIdentifier()) {
            $query->filterByIdentifier($criteriaTransfer->getIdentifier());
        }

        if ($criteriaTransfer->getTenantHost()) {
            $query->filterByTenantHost($criteriaTransfer->getTenantHost());
        }

        if ($criteriaTransfer->getMerchantPortalHost()) {
            $query->filterByMerchantPortalHost($criteriaTransfer->getMerchantPortalHostOrFail());
        }

        $total = $query->count();

        if ($criteriaTransfer->getLimit()) {
            $query->limit($criteriaTransfer->getLimit());
        }

        if ($criteriaTransfer->getOffset()) {
            $query->offset($criteriaTransfer->getOffset());
        }

        $entities = $query->find();

        $collectionTransfer = new TenantCollectionTransfer();

        foreach ($entities as $entity) {
            $tenantTransfer = $this->getTenantTransfer($entity);
            $collectionTransfer->addTenant($tenantTransfer);
        }

        $paginationTransfer = new PaginationTransfer();
        $paginationTransfer->setNbResults($total);
        $paginationTransfer->setMaxPerPage($criteriaTransfer->getLimit() ?: $total);
        $paginationTransfer->setPage($criteriaTransfer->getOffset() ? ($criteriaTransfer->getOffset() / ($criteriaTransfer->getLimit() ?: 1)) + 1 : 1);

        $collectionTransfer->setPagination($paginationTransfer);

        return $collectionTransfer;
    }

    /**
     * @param int $idTenant
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantById(int $idTenant): ?TenantTransfer
    {
        $entity = $this->getFactory()
            ->createTenantQuery()
            ->filterByIdTenant($idTenant)
            ->findOne();

        if (!$entity) {
            return null;
        }

        $tenantTransfer = $this->getTenantTransfer($entity);

        return $tenantTransfer;
    }

    /**
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantByIdentifier(string $identifier): ?TenantTransfer
    {
        $entity = $this->getFactory()
            ->createTenantQuery()
            ->filterByIdentifier($identifier)
            ->findOne();

        if (!$entity) {
            return null;
        }

        $tenantTransfer = $this->getTenantTransfer($entity);

        return $tenantTransfer;
    }

    /**
     * @param string $tenantHost
     *
     * @return \Generated\Shared\Transfer\TenantTransfer|null
     */
    public function findTenantByHost(string $tenantHost): ?TenantTransfer
    {
        $entity = $this->getFactory()
            ->createTenantQuery()
            ->filterByTenantHost($tenantHost)
            ->findOne();

        if (!$entity) {
            return null;
        }

        $tenantTransfer = $this->getTenantTransfer($entity);

        return $tenantTransfer;
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function isTenantIdentifierAvailable(string $identifier): bool
    {
        $count = $this->getFactory()
            ->createTenantQuery()
            ->filterByIdentifier($identifier)
            ->count();

        return $count === 0;
    }

    /**
     * @param string $tenantHost
     *
     * @return bool
     */
    public function isTenantHostAvailable(string $tenantHost): bool
    {
        $count = $this->getFactory()
            ->createTenantQuery()
            ->filterByTenantHost($tenantHost)
            ->count();

        return $count === 0;
    }

    /**
     * @param mixed $entity
     *
     * @return \Generated\Shared\Transfer\TenantTransfer
     */
    public function getTenantTransfer(mixed $entity): TenantTransfer
    {
        $tenantTransfer = new TenantTransfer();
        $data = $entity->toArray();
        $data['data'] = json_encode($data['data']);
        $tenantTransfer->fromArray($data, true);

        return $tenantTransfer;
    }
}
