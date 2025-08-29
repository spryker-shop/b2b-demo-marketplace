<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\TenantAssigner\Business;

use Pyz\Zed\TenantAssigner\Business\Service\TableDiscoveryService;
use Pyz\Zed\TenantAssigner\Business\Service\TableDiscoveryServiceInterface;
use Pyz\Zed\TenantAssigner\Business\Service\TableRowService;
use Pyz\Zed\TenantAssigner\Business\Service\TableRowServiceInterface;
use Pyz\Zed\TenantAssigner\Business\Service\TenantAssignmentService;
use Pyz\Zed\TenantAssigner\Business\Service\TenantAssignmentServiceInterface;
use Pyz\Zed\TenantAssigner\Business\Service\TenantDuplicationService;
use Pyz\Zed\TenantAssigner\Business\Service\TenantDuplicationServiceInterface;
use Pyz\Zed\TenantAssigner\TenantAssignerDependencyProvider;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Pyz\Zed\TenantAssigner\TenantAssignerConfig getConfig()
 * @method \Pyz\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface getRepository()
 */
class TenantAssignerBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Pyz\Zed\TenantAssigner\Business\Service\TableDiscoveryServiceInterface
     */
    public function createTableDiscoveryService(): TableDiscoveryServiceInterface
    {
        return new TableDiscoveryService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Pyz\Zed\TenantAssigner\Business\Service\TableRowServiceInterface
     */
    public function createTableRowService(): TableRowServiceInterface
    {
        return new TableRowService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Pyz\Zed\TenantAssigner\Business\Service\TenantAssignmentServiceInterface
     */
    public function createTenantAssignmentService(): TenantAssignmentServiceInterface
    {
        return new TenantAssignmentService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Pyz\Zed\TenantAssigner\Business\Service\TenantDuplicationServiceInterface
     */
    public function createTenantDuplicationService(): TenantDuplicationServiceInterface
    {
        return new TenantDuplicationService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }
}
