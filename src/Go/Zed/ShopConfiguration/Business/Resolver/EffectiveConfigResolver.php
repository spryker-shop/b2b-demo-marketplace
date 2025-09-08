<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\Resolver;

use Go\Zed\ShopConfiguration\Business\Provider\ConfigAggregator;
use Go\Zed\ShopConfiguration\Persistence\ShopConfigurationRepository;

class EffectiveConfigResolver
{
    public function __construct(
        protected ConfigAggregator $aggregator,
        protected ShopConfigurationRepository $repository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolveForStoreLocale(string $store, ?string $locale = null): array
    {
        $options = $this->aggregator->collectOptions();
        $defaults = [];
        foreach ($options as $key => $option) {
            $defaults[$key] = $option->getDefaultValue();
        }

        $overrides = $this->repository->getValuesMapForStoreLocale($store, $locale);

        return array_replace($defaults, $overrides);
    }
}
