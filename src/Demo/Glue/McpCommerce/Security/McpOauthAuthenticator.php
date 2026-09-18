<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Security;

use Demo\Glue\McpCommerce\McpCommerceConfig;
use Spryker\ApiPlatform\Security\ApiUserProvider;
use Spryker\ApiPlatform\Security\OauthAuthenticator;
use Spryker\Client\Oauth\OauthClientInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The core authenticator claims every request that carries an `Authorization` header, except the two
 * hardcoded shop token endpoints. An MCP access token is an opaque, independently revocable credential
 * that is deliberately not a Spryker JWT, so the core authenticator would reject it with a 401 body
 * that carries no `WWW-Authenticate` header — failing the MCP discovery contract before the MCP
 * controller ever runs.
 *
 * This override keeps the core behaviour for every Storefront API resource and only steps aside for the
 * paths owned by the MCP Commerce Server, which validate their own credentials.
 */
class McpOauthAuthenticator extends OauthAuthenticator
{
    public function __construct(
        OauthClientInterface $oauthClient,
        ApiUserProvider $apiUserProvider,
        protected readonly McpCommerceConfig $mcpCommerceConfig,
    ) {
        parent::__construct($oauthClient, $apiUserProvider);
    }

    public function supports(Request $request): ?bool
    {
        if (in_array($request->getPathInfo(), $this->mcpCommerceConfig->getUnauthenticatedPaths(), true)) {
            return false;
        }

        return parent::supports($request);
    }
}
