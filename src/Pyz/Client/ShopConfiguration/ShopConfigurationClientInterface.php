<?php

declare(strict_types=1);

namespace Pyz\Client\ShopConfiguration;

interface ShopConfigurationClientInterface
{
    /**
     * @param string|null $locale
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(?string $locale = null): array;

    /**
     * @param string $configKey
     * @param mixed $default
     * @param string|null $locale
     *
     * @return mixed
     */
    public function get(string $configKey, $default = null, ?string $locale = null);

    /**
     * @param string $configKey
     * @param string|null $locale
     *
     * @return bool
     */
    public function has(string $configKey, ?string $locale = null): bool;

    /**
     * @param string $module
     * @param string|null $locale
     *
     * @return array<string, mixed>
     */
    public function getModuleConfiguration(string $module, ?string $locale = null): array;

    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return bool
     */
    public function isConfigurationAvailable(string $store, ?string $locale = null): bool;

    public function getConfig(string $key): string|int|array|bool|null;

    public function resolveDomainByHost(string $host): ?array;
}
