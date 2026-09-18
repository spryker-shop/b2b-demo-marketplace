<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\Pkce;

interface PkceVerifierInterface
{
    /**
     * Specification:
     * - Verifies a PKCE code verifier against a stored code challenge.
     * - Accepts the S256 challenge method only, `plain` is always rejected.
     *
     * @param string $codeVerifier
     * @param string $codeChallenge
     * @param string $codeChallengeMethod
     *
     * @return bool
     */
    public function verify(string $codeVerifier, string $codeChallenge, string $codeChallengeMethod): bool;

    /**
     * Specification:
     * - Checks whether the given code challenge method is supported.
     *
     * @param string $codeChallengeMethod
     *
     * @return bool
     */
    public function isSupportedCodeChallengeMethod(string $codeChallengeMethod): bool;
}
