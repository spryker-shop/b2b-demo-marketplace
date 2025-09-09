<?php

declare(strict_types=1);

namespace Go\Zed\ShopConfiguration\Business\Security;

interface EncryptionServiceInterface
{
    /**
     * @param string $value
     * @param string $key
     *
     * @return string
     */
    public function encrypt(string $value, string $key): string;

    /**
     * @param string $encryptedValue
     * @param string $key
     *
     * @return string
     */
    public function decrypt(string $encryptedValue, string $key): string;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isSensitiveField(string $key): bool;
}
