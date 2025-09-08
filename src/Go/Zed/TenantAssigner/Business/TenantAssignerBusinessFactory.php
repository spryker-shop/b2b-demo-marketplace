<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\TenantAssigner\Business;

use Go\Zed\TenantAssigner\Business\Service\TableDiscoveryService;
use Go\Zed\TenantAssigner\Business\Service\TableDiscoveryServiceInterface;
use Go\Zed\TenantAssigner\Business\Service\TableRowService;
use Go\Zed\TenantAssigner\Business\Service\TableRowServiceInterface;
use Go\Zed\TenantAssigner\Business\Service\TenantAssignmentService;
use Go\Zed\TenantAssigner\Business\Service\TenantAssignmentServiceInterface;
use Go\Zed\TenantAssigner\Business\Service\TenantDuplicationService;
use Go\Zed\TenantAssigner\Business\Service\TenantDuplicationServiceInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Go\Zed\TenantAssigner\TenantAssignerConfig getConfig()
 * @method \Go\Zed\TenantAssigner\Persistence\TenantAssignerRepositoryInterface getRepository()
 */
class TenantAssignerBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Go\Zed\TenantAssigner\Business\Service\TableDiscoveryServiceInterface
     */
    public function createTableDiscoveryService(): TableDiscoveryServiceInterface
    {
        return new TableDiscoveryService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Go\Zed\TenantAssigner\Business\Service\TableRowServiceInterface
     */
    public function createTableRowService(): TableRowServiceInterface
    {
        return new TableRowService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Go\Zed\TenantAssigner\Business\Service\TenantAssignmentServiceInterface
     */
    public function createTenantAssignmentService(): TenantAssignmentServiceInterface
    {
        return new TenantAssignmentService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Go\Zed\TenantAssigner\Business\Service\TenantDuplicationServiceInterface
     */
    public function createTenantDuplicationService(): TenantDuplicationServiceInterface
    {
        return new TenantDuplicationService(
            $this->getRepository(),
            $this->getConfig(),
        );
    }
}
