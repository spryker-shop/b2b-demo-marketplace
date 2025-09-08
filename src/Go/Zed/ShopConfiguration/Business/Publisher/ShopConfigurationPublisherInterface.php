<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\Publisher;

interface ShopConfigurationPublisherInterface
{
    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return void
     */
    public function publishConfiguration(string $store, ?string $locale = null): void;

    /**
     * @param array<string> $stores
     * @param array<string> $locales
     *
     * @return void
     */
    public function publishConfigurationForStoresAndLocales(array $stores, array $locales = []): void;
}
