<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow\Communication;

use Pyz\Zed\ExampleWorkflow\ExampleWorkflowDependencyProvider;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\SymfonyMailer\Business\SymfonyMailerFacadeInterface;
use Spryker\Zed\Workflow\Business\WorkflowFacadeInterface;

/**
 * @method \Pyz\Zed\ExampleWorkflow\ExampleWorkflowConfig getConfig()
 */
class ExampleWorkflowCommunicationFactory extends AbstractCommunicationFactory
{
    public function getCustomerFacade(): CustomerFacadeInterface
    {
        return $this->getProvidedDependency(ExampleWorkflowDependencyProvider::FACADE_CUSTOMER);
    }

    public function getWorkflowFacade(): WorkflowFacadeInterface
    {
        return $this->getProvidedDependency(ExampleWorkflowDependencyProvider::FACADE_WORKFLOW);
    }

    public function getSymfonyMailerFacade(): SymfonyMailerFacadeInterface
    {
        return $this->getProvidedDependency(ExampleWorkflowDependencyProvider::FACADE_SYMFONY_MAILER);
    }
}
