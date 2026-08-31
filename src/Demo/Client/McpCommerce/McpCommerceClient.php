<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\McpCommerce;

use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\McpClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\McpClientRequestTransfer;
use Generated\Shared\Transfer\McpClientResponseTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * The Storefront API application has no database connection of its own, so every MCP authorization
 * store operation has to travel to Zed over the ZedRequest transport rather than calling the
 * McpCommerce facade in-process.
 *
 * @method \Demo\Client\McpCommerce\McpCommerceFactory getFactory()
 */
class McpCommerceClient extends AbstractClient implements McpCommerceClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    public function registerClient(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
    ): McpClientRegistrationResponseTransfer {
        return $this->getFactory()
            ->createMcpCommerceZedStub()
            ->registerClient($mcpClientRegistrationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpClientRequestTransfer $mcpClientRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientResponseTransfer
     */
    public function findClient(McpClientRequestTransfer $mcpClientRequestTransfer): McpClientResponseTransfer
    {
        return $this->getFactory()
            ->createMcpCommerceZedStub()
            ->findClient($mcpClientRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function issueAuthorizationCode(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer {
        return $this->getFactory()
            ->createMcpCommerceZedStub()
            ->issueAuthorizationCode($mcpAuthorizationCodeTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer
     */
    public function redeemAuthorizationCode(
        McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer,
    ): McpAuthorizationCodeRedemptionResponseTransfer {
        return $this->getFactory()
            ->createMcpCommerceZedStub()
            ->redeemAuthorizationCode($mcpAuthorizationCodeRedemptionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpIdentityTransfer $mcpIdentityTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function issueAccessToken(McpIdentityTransfer $mcpIdentityTransfer): McpAccessTokenTransfer
    {
        return $this->getFactory()
            ->createMcpCommerceZedStub()
            ->issueAccessToken($mcpIdentityTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validateAccessToken(
        McpAccessTokenTransfer $mcpAccessTokenTransfer,
    ): McpAccessTokenValidationResponseTransfer {
        return $this->getFactory()
            ->createMcpCommerceZedStub()
            ->validateAccessToken($mcpAccessTokenTransfer);
    }
}
