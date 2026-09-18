<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Client\McpCommerce\Zed;

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

interface McpCommerceZedStubInterface
{
    /**
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    public function registerClient(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
    ): McpClientRegistrationResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\McpClientRequestTransfer $mcpClientRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientResponseTransfer
     */
    public function findClient(McpClientRequestTransfer $mcpClientRequestTransfer): McpClientResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function issueAuthorizationCode(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer;

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer
     */
    public function redeemAuthorizationCode(
        McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer,
    ): McpAuthorizationCodeRedemptionResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\McpIdentityTransfer $mcpIdentityTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function issueAccessToken(McpIdentityTransfer $mcpIdentityTransfer): McpAccessTokenTransfer;

    /**
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validateAccessToken(
        McpAccessTokenTransfer $mcpAccessTokenTransfer,
    ): McpAccessTokenValidationResponseTransfer;
}
