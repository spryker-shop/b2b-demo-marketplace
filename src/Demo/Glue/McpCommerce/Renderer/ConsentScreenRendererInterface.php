<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Renderer;

interface ConsentScreenRendererInterface
{
    /**
     * Specification:
     * - Renders the login and consent screen shown at the OAuth authorization endpoint.
     * - Escapes every dynamic value, including the client-supplied authorization request parameters.
     * - Never renders a submitted password back into the markup.
     *
     * @param string $clientName
     * @param array<string, string> $authorizationRequestParameters
     * @param string|null $errorMessage
     *
     * @return string
     */
    public function renderConsentScreen(
        string $clientName,
        array $authorizationRequestParameters,
        ?string $errorMessage = null,
    ): string;

    /**
     * Specification:
     * - Renders a standalone error page for an authorization request that cannot be continued.
     * - Used when no validated redirect URI is available to report the error to.
     *
     * @param string $errorMessage
     *
     * @return string
     */
    public function renderErrorScreen(string $errorMessage): string;
}
