<?php

declare(strict_types=1);

namespace Pyz\Shared\ShopConfiguration;

class ShopConfigurationKeyBuilder
{
    /**
     * @param string $store
     * @param string|null $locale
     *
     * @return string
     */
    public static function buildKey(string $store, ?string $locale = null): string
    {
        if ($locale === null) {
            return str_replace('{store}', $store, ShopConfigurationConstants::REDIS_KEY_PATTERN_STORE);
        }

        return str_replace(
            ['{store}', '{locale}'],
            [$store, $locale],
            ShopConfigurationConstants::REDIS_KEY_PATTERN_STORE_LOCALE
        );
    }

    /**
     * @param string $store
     *
     * @return string
     */
    public static function buildStoreKey(string $store): string
    {
        return self::buildKey($store);
    }

    /**
     * @param string $store
     * @param string $locale
     *
     * @return string
     */
    public static function buildStoreLocaleKey(string $store, string $locale): string
    {
        return self::buildKey($store, $locale);
    }
}
