<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business;

use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\McpClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\McpClientTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Demo\Zed\McpCommerce\Business\McpCommerceBusinessFactory getFactory()
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface getRepository()
 */
class McpCommerceFacade extends AbstractFacade implements McpCommerceFacadeInterface
{
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
            ->createMcpAuthorizationCodeWriter()
            ->issue($mcpAuthorizationCodeTransfer);
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
            ->createMcpAuthorizationCodeRedeemer()
            ->redeem($mcpAuthorizationCodeRedemptionRequestTransfer);
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
            ->createMcpAccessTokenWriter()
            ->issue($mcpIdentityTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $accessTokenIdentifier
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validateAccessToken(string $accessTokenIdentifier): McpAccessTokenValidationResponseTransfer
    {
        return $this->getFactory()
            ->createMcpAccessTokenValidator()
            ->validate($accessTokenIdentifier);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $accessTokenIdentifier
     *
     * @return bool
     */
    public function revokeAccessToken(string $accessTokenIdentifier): bool
    {
        return $this->getFactory()
            ->createMcpAccessTokenWriter()
            ->revoke($accessTokenIdentifier);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return int
     */
    public function deleteExpiredAuthorizationCodes(): int
    {
        return $this->getFactory()
            ->createMcpAuthorizationCodeCleaner()
            ->deleteExpired();
    }

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
            ->createMcpClientRegistrar()
            ->register($mcpClientRegistrationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $clientIdentifier
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer|null
     */
    public function findClientByIdentifier(string $clientIdentifier): ?McpClientTransfer
    {
        return $this->getRepository()->findMcpClientByIdentifier($clientIdentifier);
    }
}
