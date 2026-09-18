<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AccessToken;

use DateTimeImmutable;
use Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface;
use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;

class McpAccessTokenValidator implements McpAccessTokenValidatorInterface
{
    /**
     * @var string
     */
    public const ERROR_CODE_INVALID_TOKEN = 'invalid_token';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNKNOWN_TOKEN = 'The access token is unknown.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_REVOKED_TOKEN = 'The access token has been revoked.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_EXPIRED_TOKEN = 'The access token has expired.';

    /**
     * @var \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface
     */
    protected McpCommerceRepositoryInterface $mcpCommerceRepository;

    /**
     * @param \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface $mcpCommerceRepository
     */
    public function __construct(McpCommerceRepositoryInterface $mcpCommerceRepository)
    {
        $this->mcpCommerceRepository = $mcpCommerceRepository;
    }

    /**
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    public function validate(string $identifier): McpAccessTokenValidationResponseTransfer
    {
        if ($identifier === '') {
            return $this->createErrorResponse(static::ERROR_MESSAGE_UNKNOWN_TOKEN);
        }

        $mcpAccessTokenTransfer = $this->mcpCommerceRepository->findMcpAccessTokenByIdentifier($identifier);

        if ($mcpAccessTokenTransfer === null) {
            return $this->createErrorResponse(static::ERROR_MESSAGE_UNKNOWN_TOKEN);
        }

        if ($mcpAccessTokenTransfer->getIsRevoked() === true) {
            return $this->createErrorResponse(static::ERROR_MESSAGE_REVOKED_TOKEN);
        }

        if ($this->isExpired($mcpAccessTokenTransfer)) {
            return $this->createErrorResponse(static::ERROR_MESSAGE_EXPIRED_TOKEN);
        }

        return (new McpAccessTokenValidationResponseTransfer())
            ->setIsValid(true)
            ->setMcpIdentity($this->createMcpIdentityTransfer($mcpAccessTokenTransfer));
    }

    /**
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return bool
     */
    protected function isExpired(McpAccessTokenTransfer $mcpAccessTokenTransfer): bool
    {
        $expiresAt = $mcpAccessTokenTransfer->getExpiresAt();

        if ($expiresAt === null) {
            return true;
        }

        return new DateTimeImmutable($expiresAt) <= new DateTimeImmutable();
    }

    /**
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpIdentityTransfer
     */
    protected function createMcpIdentityTransfer(McpAccessTokenTransfer $mcpAccessTokenTransfer): McpIdentityTransfer
    {
        return (new McpIdentityTransfer())
            ->setCustomerReference($mcpAccessTokenTransfer->getCustomerReference())
            ->setIdCustomer($mcpAccessTokenTransfer->getIdCustomer())
            ->setClientIdentifier($mcpAccessTokenTransfer->getClientIdentifier())
            ->setScopes($mcpAccessTokenTransfer->getScopes());
    }

    /**
     * @param string $errorMessage
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenValidationResponseTransfer
     */
    protected function createErrorResponse(string $errorMessage): McpAccessTokenValidationResponseTransfer
    {
        return (new McpAccessTokenValidationResponseTransfer())
            ->setIsValid(false)
            ->setErrorCode(static::ERROR_CODE_INVALID_TOKEN)
            ->setErrorMessage($errorMessage);
    }
}
