<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\Provider;

use Generated\Shared\Transfer\ShopConfigurationOptionTransfer;
use Go\Zed\ShopConfiguration\Dependency\Plugin\ConfigOptionExpanderPluginInterface;
use Go\Zed\ShopConfiguration\Dependency\Plugin\ConfigOptionProviderPluginInterface;

class ConfigAggregator
{
    /** @var array<ConfigOptionProviderPluginInterface> */
    protected array $providers;

    /** @var array<ConfigOptionExpanderPluginInterface> */
    protected array $expanders;

    /** @param array<ConfigOptionProviderPluginInterface> $providers */
    /** @param array<ConfigOptionExpanderPluginInterface> $expanders */
    public function __construct(array $providers, array $expanders)
    {
        $this->providers = $providers;
        $this->expanders = $expanders;
    }

    /**
     * @return array<string, ShopConfigurationOptionTransfer>
     */
    public function collectOptions(): array
    {
        $options = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->provideOptions() as $option) {
                $key = $this->buildKey($option);
                $options[$key] = $option;
            }
        }

        foreach ($this->expanders as $expander) {
            $options = $expander->expand($options);
        }

        return $options;
    }

    protected function buildKey(ShopConfigurationOptionTransfer $transfer): string
    {
        return sprintf('%s.%s', $transfer->getModule(), $transfer->getKey());
    }
}
