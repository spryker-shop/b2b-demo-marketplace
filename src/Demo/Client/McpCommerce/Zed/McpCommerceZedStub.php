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
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class McpCommerceZedStub implements McpCommerceZedStubInterface
{
    /**
     * @var string
     */
    protected const URL_REGISTER_CLIENT = '/mcp-commerce/gateway/register-client';

    /**
     * @var string
     */
    protected const URL_FIND_CLIENT = '/mcp-commerce/gateway/find-client';

    /**
     * @var string
     */
    protected const URL_ISSUE_AUTHORIZATION_CODE = '/mcp-commerce/gateway/issue-authorization-code';

    /**
     * @var string
     */
    protected const URL_REDEEM_AUTHORIZATION_CODE = '/mcp-commerce/gateway/redeem-authorization-code';

    /**
     * @var string
     */
    protected const URL_ISSUE_ACCESS_TOKEN = '/mcp-commerce/gateway/issue-access-token';

    /**
     * @var string
     */
    protected const URL_VALIDATE_ACCESS_TOKEN = '/mcp-commerce/gateway/validate-access-token';

    public function __construct(
        protected readonly ZedRequestClientInterface $zedRequestClient,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    public function registerClient(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
    ): McpClientRegistrationResponseTransfer {
        /** @var \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer $mcpClientRegistrationResponseTransfer */
        $mcpClientRegistrationResponseTransfer = $this->zedRequestClient->call(
            static::URL_REGISTER_CLIENT,
            $mcpClientRegistrationRequestTransfer,
        );

        return $mcpClientRegistrationResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\McpClientRequestTransfer $mcpClientRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientResponseTransfer
     */
    public function findClient(McpClientRequestTransfer $mcpClientRequestTransfer): McpClientResponseTransfer
    {
        /** @var \Generated\Shared\Transfer\McpClientResponseTransfer $mcpClientResponseTransfer */
        $mcpClientResponseTransfer = $this->zedRequestClient->call(
            static::URL_FIND_CLIENT,
            $mcpClientRequestTransfer,
        );

        return $mcpClientResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function issueAuthorizationCode(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer {
        /** @var \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $issuedMcpAuthorizationCodeTransfer */
        $issuedMcpAuthorizationCodeTransfer = $this->zedRequestClient->call(
            static::URL_ISSUE_AUTHORIZATION_CODE,
            $mcpAuthorizationCodeTransfer,
        );

        return $issuedMcpAuthorizationCodeTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer
     */
    public function redeemAuthorizationCode(
        McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer,
    ): McpAuthorizationCodeRedemptionResponseTransfer {
        /** @var \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer $mcpAuthorizationCodeRedemptionResponseTransfer */
        $mcpAuthorizationCodeRedemptionResponseTransfer = $this->zedRequestClient->call(
            static::URL_REDEEM_AUTHORIZATION_CODE,
            $mcpAuthorizationCodeRedemptionRequestTransfer,
        );

        return $mcpAuthorizationCodeRedemptionResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\McpIdentityTransfer $mcpIdentityTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function issueAccessToken(McpIdentityTransfer $mcpIdentityTransfer): McpAccessTokenTransfer
    {
        /** @var \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer */
        $mcpAccessTokenTransfer = $this->zedRequestClient->call(
            static::URL_ISSUE_ACCESS_TOKEN,
            $mcpIdentityTransfer,
        );

        return $mcpAccessTokenTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validateAccessToken(
        McpAccessTokenTransfer $mcpAccessTokenTransfer,
    ): McpAccessTokenValidationResponseTransfer {
        /** @var \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer $mcpAccessTokenValidationResponseTransfer */
        $mcpAccessTokenValidationResponseTransfer = $this->zedRequestClient->call(
            static::URL_VALIDATE_ACCESS_TOKEN,
            $mcpAccessTokenTransfer,
        );

        return $mcpAccessTokenValidationResponseTransfer;
    }
}
