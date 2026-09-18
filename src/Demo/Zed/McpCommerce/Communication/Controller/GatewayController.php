<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Communication\Controller;

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
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;

/**
 * Exposes the MCP authorization store to the Storefront API application, which has no database
 * connection of its own and therefore reaches this module over the ZedRequest transport.
 *
 * @method \Demo\Zed\McpCommerce\Business\McpCommerceFacadeInterface getFacade()
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface getRepository()
 * @method \Demo\Zed\McpCommerce\Communication\McpCommerceCommunicationFactory getFactory()
 */
class GatewayController extends AbstractGatewayController
{
    /**
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    public function registerClientAction(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
    ): McpClientRegistrationResponseTransfer {
        return $this->getFacade()->registerClient($mcpClientRegistrationRequestTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\McpClientRequestTransfer $mcpClientRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientResponseTransfer
     */
    public function findClientAction(McpClientRequestTransfer $mcpClientRequestTransfer): McpClientResponseTransfer
    {
        $mcpClientTransfer = $this->getFacade()->findClientByIdentifier(
            (string)$mcpClientRequestTransfer->getClientIdentifier(),
        );

        return (new McpClientResponseTransfer())->setMcpClient($mcpClientTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function issueAuthorizationCodeAction(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer {
        return $this->getFacade()->issueAuthorizationCode($mcpAuthorizationCodeTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer
     */
    public function redeemAuthorizationCodeAction(
        McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer,
    ): McpAuthorizationCodeRedemptionResponseTransfer {
        return $this->getFacade()->redeemAuthorizationCode($mcpAuthorizationCodeRedemptionRequestTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\McpIdentityTransfer $mcpIdentityTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function issueAccessTokenAction(McpIdentityTransfer $mcpIdentityTransfer): McpAccessTokenTransfer
    {
        return $this->getFacade()->issueAccessToken($mcpIdentityTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validateAccessTokenAction(
        McpAccessTokenTransfer $mcpAccessTokenTransfer,
    ): McpAccessTokenValidationResponseTransfer {
        return $this->getFacade()->validateAccessToken(
            (string)$mcpAccessTokenTransfer->getIdentifier(),
        );
    }
}
