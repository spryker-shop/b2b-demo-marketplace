<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant;

use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\DiscountAgentPlugin;
use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\GeneralPurposeAgentPlugin;
use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\OrderAgentPlugin;
use Demo\Zed\BackofficeAssistant\Communication\Plugin\Agent\ProductAgentPlugin;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class BackofficeAssistantDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_AI_FOUNDATION = 'FACADE_AI_FOUNDATION';

    public const string FACADE_USER = 'FACADE_USER';

    public const string SERVICE_BACKOFFICE_ASSISTANT = 'SERVICE_BACKOFFICE_ASSISTANT';

    public const string PLUGINS_BACKOFFICE_ASSISTANT_AGENT = 'PLUGINS_BACKOFFICE_ASSISTANT_AGENT';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);

        $container->set(static::FACADE_AI_FOUNDATION, fn (Container $container) => $container->getLocator()->aiFoundation()->facade());

        $container->set(static::FACADE_USER, fn (Container $container) => $container->getLocator()->user()->facade());

        $container->set(static::PLUGINS_BACKOFFICE_ASSISTANT_AGENT, function (): array {
            return $this->getBackofficeAssistantAgentPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Demo\Zed\BackofficeAssistant\Dependency\BackofficeAssistantAgentPluginInterface>
     */
    protected function getBackofficeAssistantAgentPlugins(): array
    {
        return [
            new GeneralPurposeAgentPlugin(),
            new ProductAgentPlugin(),
            new DiscountAgentPlugin(),
            new OrderAgentPlugin(),
        ];
    }

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        // @phpstan-ignore method.notFound
        $container->set(static::SERVICE_BACKOFFICE_ASSISTANT, fn (Container $container) => $container->getLocator()->backofficeAssistant()->service());

        return $container;
    }
}
