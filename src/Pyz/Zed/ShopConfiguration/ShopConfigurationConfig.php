<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class ShopConfigurationConfig extends AbstractBundleConfig
{
    public const AWS_FILE_STORAGE_BUCKET = 'AWS_FILE_STORAGE_BUCKET';

    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return 'shop-configuration';
    }

    /**
     * @return array<string>
     */
    public function getSupportedFileExtensions(): array
    {
        return ['yml', 'yaml', 'xml', 'json'];
    }

    /**
     * @return array<string>
     */
    public function getDiscoveryPaths(): array
    {
        return [
            '{namespace}/Shared/{module}/ShopConfiguration/',
            '{namespace}/Zed/{module}/Communication/Resources/shop_configuration/',
        ];
    }

    /**
     * @return array<string>
     */
    public function getSensitiveFieldPatterns(): array
    {
        return [
            'api_key',
            'secret',
            'password',
            'token',
            'private_key',
            'merchant_id',
            'client_secret',
        ];
    }

    /**
     * @return bool
     */
    public function isEncryptionEnabled(): bool
    {
        return true;
    }

    /**
     * @return int
     */
    public function getCacheExpirationTime(): int
    {
        return 3600; // 1 hour
    }

    public function getStoreFrontHost(): string
    {
        return $this->get(ApplicationConstants::HOST_YVES);
    }

    public function getAwsFileStorageBucket()
    {
        return $this->get(static::AWS_FILE_STORAGE_BUCKET);
    }
}
