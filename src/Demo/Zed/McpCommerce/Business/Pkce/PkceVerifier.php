<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\Pkce;

use Demo\Zed\McpCommerce\McpCommerceConfig;

class PkceVerifier implements PkceVerifierInterface
{
    /**
     * @var string
     */
    protected const HASH_ALGORITHM = 'sha256';

    /**
     * @var \Demo\Zed\McpCommerce\McpCommerceConfig
     */
    protected McpCommerceConfig $mcpCommerceConfig;

    /**
     * @param \Demo\Zed\McpCommerce\McpCommerceConfig $mcpCommerceConfig
     */
    public function __construct(McpCommerceConfig $mcpCommerceConfig)
    {
        $this->mcpCommerceConfig = $mcpCommerceConfig;
    }

    /**
     * @param string $codeVerifier
     * @param string $codeChallenge
     * @param string $codeChallengeMethod
     *
     * @return bool
     */
    public function verify(string $codeVerifier, string $codeChallenge, string $codeChallengeMethod): bool
    {
        if (!$this->isSupportedCodeChallengeMethod($codeChallengeMethod)) {
            return false;
        }

        if ($codeVerifier === '' || $codeChallenge === '') {
            return false;
        }

        return hash_equals($codeChallenge, $this->createCodeChallenge($codeVerifier));
    }

    /**
     * @param string $codeChallengeMethod
     *
     * @return bool
     */
    public function isSupportedCodeChallengeMethod(string $codeChallengeMethod): bool
    {
        return $codeChallengeMethod === $this->mcpCommerceConfig->getSupportedCodeChallengeMethod();
    }

    /**
     * @param string $codeVerifier
     *
     * @return string
     */
    protected function createCodeChallenge(string $codeVerifier): string
    {
        $hash = hash(static::HASH_ALGORITHM, $codeVerifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }
}
