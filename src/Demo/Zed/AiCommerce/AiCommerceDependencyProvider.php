<?php

namespace Demo\Zed\AiCommerce;

use SprykerFeature\Zed\AiCommerce\AiCommerceDependencyProvider as SprykerFeatureAiCommerceDependencyProvider;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent\DiscountManagementAgentPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent\GeneralPurposeAgentPlugin;
use SprykerFeature\Zed\AiCommerce\Communication\Plugin\Agent\OrderManagementAgentPlugin;

class AiCommerceDependencyProvider extends SprykerFeatureAiCommerceDependencyProvider
{
    /**
     * @return array<\SprykerFeature\Zed\AiCommerce\Dependency\BackofficeAssistant\BackofficeAssistantAgentPluginInterface>
     */
    protected function getBackofficeAssistantAgentPlugins(): array
    {
        return [
            new GeneralPurposeAgentPlugin(),
            new OrderManagementAgentPlugin(),
            new DiscountManagementAgentPlugin(),
        ];
    }
}
