<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\ExampleWorkflow;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class ExampleWorkflowDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_CUSTOMER = 'FACADE_CUSTOMER';

    /**
     * @var string
     */
    public const FACADE_WORKFLOW = 'FACADE_WORKFLOW';

    /**
     * @var string
     */
    public const FACADE_SYMFONY_MAILER = 'FACADE_SYMFONY_MAILER';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);

        $container->set(static::FACADE_CUSTOMER, static function (Container $container) {
            return $container->getLocator()->customer()->facade();
        });

        $container->set(static::FACADE_WORKFLOW, static function (Container $container) {
            return $container->getLocator()->workflow()->facade();
        });

        $container->set(static::FACADE_SYMFONY_MAILER, static function (Container $container) {
            return $container->getLocator()->symfonyMailer()->facade();
        });

        return $container;
    }
}
