<?php

declare(strict_types=1);

namespace Pyz\Zed\ShopConfiguration\Business\Security;

use Pyz\Zed\ShopConfiguration\ShopConfigurationConfig;

class EncryptionService implements EncryptionServiceInterface
{
    /**
     * @var \Pyz\Zed\ShopConfiguration\ShopConfigurationConfig
     */
    protected ShopConfigurationConfig $config;

    /**
     * @param \Pyz\Zed\ShopConfiguration\ShopConfigurationConfig $config
     */
    public function __construct(ShopConfigurationConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $value
     * @param string $key
     *
     * @return string
     */
    public function encrypt(string $value, string $key): string
    {
        if (!$this->config->isEncryptionEnabled() || !$this->isSensitiveField($key)) {
            return $value;
        }

        // Simple encryption for demo - in production use proper encryption
        // like OpenSSL with proper key management
        $encryptionKey = $this->getEncryptionKey();
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $encryptionKey, 0, $this->getIv());
        
        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($encrypted);
    }

    /**
     * @param string $encryptedValue
     * @param string $key
     *
     * @return string
     */
    public function decrypt(string $encryptedValue, string $key): string
    {
        if (!$this->config->isEncryptionEnabled() || !$this->isSensitiveField($key)) {
            return $encryptedValue;
        }

        $encryptionKey = $this->getEncryptionKey();
        $decoded = base64_decode($encryptedValue);
        
        if ($decoded === false) {
            return $encryptedValue; // Return as-is if not base64
        }

        $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $encryptionKey, 0, $this->getIv());
        
        if ($decrypted === false) {
            return $encryptedValue; // Return as-is if decryption fails
        }

        return $decrypted;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isSensitiveField(string $key): bool
    {
        $sensitivePatterns = $this->config->getSensitiveFieldPatterns();
        
        foreach ($sensitivePatterns as $pattern) {
            if (stripos($key, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getEncryptionKey(): string
    {
        // In production, this should come from environment variables or secure key management
        return hash('sha256', 'shop-configuration-encryption-key', true);
    }

    /**
     * @return string
     */
    protected function getIv(): string
    {
        // In production, use a proper random IV for each encryption
        // This is simplified for demo purposes
        return substr(hash('sha256', 'shop-configuration-iv', true), 0, 16);
    }
}
