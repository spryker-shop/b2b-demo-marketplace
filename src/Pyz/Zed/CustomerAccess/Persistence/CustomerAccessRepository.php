<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\CustomerAccess\Persistence;

use Generated\Shared\Transfer\ContentTypeAccessTransfer;
use Generated\Shared\Transfer\CustomerAccessTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Pyz\Zed\CustomerAccess\Persistence\CustomerAccessPersistenceFactory getFactory()
 */
class CustomerAccessRepository extends AbstractRepository implements CustomerAccessRepositoryInterface
{
    /**
     * @param string $contentType
     *
     * @return \Generated\Shared\Transfer\ContentTypeAccessTransfer|null
     */
    public function findCustomerAccessByContentType($contentType): ?ContentTypeAccessTransfer // phpcs:ignore
    {
        $customerAccessEntity = $this->getFactory()
            ->getUnauthenticatedCustomerAccessQuery()
            ->filterByContentType($contentType)
            ->findOne();

        if (!$customerAccessEntity) {
            return null;
        }

        return $this->getFactory()
            ->createCustomerAccessMapper()
            ->mapCustomerAccessEntityToContentTypeAccessTransfer($customerAccessEntity, new ContentTypeAccessTransfer());
    }

    /**
     * @return \Generated\Shared\Transfer\CustomerAccessTransfer
     */
    public function getUnrestrictedContentTypes(): CustomerAccessTransfer
    {
        /**
         * @var \Propel\Runtime\Collection\ObjectCollection $unauthenticatedCustomerAccessEntity
         */
        $unauthenticatedCustomerAccessEntity = $this->getFactory()
            ->getUnauthenticatedCustomerAccessQuery()
            ->filterByIsRestricted(false)
            ->find();

        return $this->getFactory()
            ->createCustomerAccessMapper()
            ->mapEntitiesToCustomerAccessTransfer($unauthenticatedCustomerAccessEntity, new CustomerAccessTransfer());
    }

    /**
     * @return \Generated\Shared\Transfer\CustomerAccessTransfer
     */
    public function getAllContentTypes(): CustomerAccessTransfer
    {
        /**
         * @var \Propel\Runtime\Collection\ObjectCollection $unauthenticatedCustomerAccessEntity
         */
        $unauthenticatedCustomerAccessEntity = $this->getFactory()
            ->getUnauthenticatedCustomerAccessQuery()
            ->orderByIdUnauthenticatedCustomerAccess()
            ->find();

        return $this->getFactory()
            ->createCustomerAccessMapper()
            ->mapEntitiesToCustomerAccessTransfer($unauthenticatedCustomerAccessEntity, new CustomerAccessTransfer());
    }

    /**
     * @return \Generated\Shared\Transfer\CustomerAccessTransfer
     */
    public function getRestrictedContentTypes(): CustomerAccessTransfer
    {
        /**
         * @var \Propel\Runtime\Collection\ObjectCollection $unauthenticatedCustomerAccessEntity
         */
        $unauthenticatedCustomerAccessEntity = $this->getFactory()
            ->getUnauthenticatedCustomerAccessQuery()
            ->filterByIsRestricted(true)
            ->find();

        return $this->getFactory()
            ->createCustomerAccessMapper()
            ->mapEntitiesToCustomerAccessTransfer($unauthenticatedCustomerAccessEntity, new CustomerAccessTransfer());
    }
}
