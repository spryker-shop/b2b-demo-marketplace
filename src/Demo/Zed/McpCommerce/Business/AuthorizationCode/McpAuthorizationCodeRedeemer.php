<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\AuthorizationCode;

use DateTimeImmutable;
use Demo\Zed\McpCommerce\Business\Pkce\PkceVerifierInterface;
use Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface;
use Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpIdentityTransfer;

class McpAuthorizationCodeRedeemer implements McpAuthorizationCodeRedeemerInterface
{
    /**
     * @var string
     */
    public const ERROR_CODE_INVALID_GRANT = 'invalid_grant';

    /**
     * @var string
     */
    public const ERROR_CODE_INVALID_REQUEST = 'invalid_request';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNKNOWN_CODE = 'The authorization code is unknown.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_CLIENT_MISMATCH = 'The authorization code was not issued to this client.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_REDIRECT_URI_MISMATCH = 'The redirect URI does not match the authorization request.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_CODE_ALREADY_USED = 'The authorization code has already been redeemed.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_CODE_EXPIRED = 'The authorization code has expired.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_PKCE_FAILED = 'The PKCE code verifier does not match the code challenge.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_UNSUPPORTED_CHALLENGE_METHOD = 'Only the S256 PKCE code challenge method is supported.';

    /**
     * @var \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface
     */
    protected McpCommerceRepositoryInterface $mcpCommerceRepository;

    /**
     * @var \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface
     */
    protected McpCommerceEntityManagerInterface $mcpCommerceEntityManager;

    /**
     * @var \Demo\Zed\McpCommerce\Business\Pkce\PkceVerifierInterface
     */
    protected PkceVerifierInterface $pkceVerifier;

    /**
     * @param \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface $mcpCommerceRepository
     * @param \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface $mcpCommerceEntityManager
     * @param \Demo\Zed\McpCommerce\Business\Pkce\PkceVerifierInterface $pkceVerifier
     */
    public function __construct(
        McpCommerceRepositoryInterface $mcpCommerceRepository,
        McpCommerceEntityManagerInterface $mcpCommerceEntityManager,
        PkceVerifierInterface $pkceVerifier,
    ) {
        $this->mcpCommerceRepository = $mcpCommerceRepository;
        $this->mcpCommerceEntityManager = $mcpCommerceEntityManager;
        $this->pkceVerifier = $pkceVerifier;
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer
     */
    public function redeem(
        McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer,
    ): McpAuthorizationCodeRedemptionResponseTransfer {
        $code = (string)$mcpAuthorizationCodeRedemptionRequestTransfer->getCode();
        $codeVerifier = (string)$mcpAuthorizationCodeRedemptionRequestTransfer->getCodeVerifier();

        if ($code === '' || $codeVerifier === '') {
            return $this->createErrorResponse(static::ERROR_CODE_INVALID_REQUEST, static::ERROR_MESSAGE_UNKNOWN_CODE);
        }

        $mcpAuthorizationCodeTransfer = $this->mcpCommerceRepository->findMcpAuthorizationCodeByCode($code);

        if ($mcpAuthorizationCodeTransfer === null) {
            return $this->createErrorResponse(static::ERROR_CODE_INVALID_GRANT, static::ERROR_MESSAGE_UNKNOWN_CODE);
        }

        $errorResponseTransfer = $this->validate(
            $mcpAuthorizationCodeTransfer,
            $mcpAuthorizationCodeRedemptionRequestTransfer,
        );

        if ($errorResponseTransfer !== null) {
            return $errorResponseTransfer;
        }

        if ($this->mcpCommerceEntityManager->markMcpAuthorizationCodeAsUsed($code) === 0) {
            return $this->createErrorResponse(
                static::ERROR_CODE_INVALID_GRANT,
                static::ERROR_MESSAGE_CODE_ALREADY_USED,
            );
        }

        return (new McpAuthorizationCodeRedemptionResponseTransfer())
            ->setIsSuccessful(true)
            ->setMcpAuthorizationCode($mcpAuthorizationCodeTransfer->setIsUsed(true))
            ->setMcpIdentity($this->createMcpIdentityTransfer($mcpAuthorizationCodeTransfer));
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer|null
     */
    protected function validate(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
        McpAuthorizationCodeRedemptionRequestTransfer $mcpAuthorizationCodeRedemptionRequestTransfer,
    ): ?McpAuthorizationCodeRedemptionResponseTransfer {
        if ($mcpAuthorizationCodeTransfer->getIsUsed() === true) {
            return $this->createErrorResponse(
                static::ERROR_CODE_INVALID_GRANT,
                static::ERROR_MESSAGE_CODE_ALREADY_USED,
            );
        }

        if ($this->isExpired($mcpAuthorizationCodeTransfer)) {
            return $this->createErrorResponse(static::ERROR_CODE_INVALID_GRANT, static::ERROR_MESSAGE_CODE_EXPIRED);
        }

        $clientIdentifier = $mcpAuthorizationCodeRedemptionRequestTransfer->getClientIdentifier();

        if ($clientIdentifier !== null && $clientIdentifier !== $mcpAuthorizationCodeTransfer->getClientIdentifier()) {
            return $this->createErrorResponse(static::ERROR_CODE_INVALID_GRANT, static::ERROR_MESSAGE_CLIENT_MISMATCH);
        }

        $redirectUri = $mcpAuthorizationCodeRedemptionRequestTransfer->getRedirectUri();

        // Compared unconditionally: an absent redirect URI must fail the binding check rather than
        // skip it, otherwise omitting the parameter bypasses OAuth 2.1's redirect-URI binding.
        if ($redirectUri !== $mcpAuthorizationCodeTransfer->getRedirectUri()) {
            return $this->createErrorResponse(
                static::ERROR_CODE_INVALID_GRANT,
                static::ERROR_MESSAGE_REDIRECT_URI_MISMATCH,
            );
        }

        $codeChallengeMethod = (string)$mcpAuthorizationCodeTransfer->getCodeChallengeMethod();

        if (!$this->pkceVerifier->isSupportedCodeChallengeMethod($codeChallengeMethod)) {
            return $this->createErrorResponse(
                static::ERROR_CODE_INVALID_GRANT,
                static::ERROR_MESSAGE_UNSUPPORTED_CHALLENGE_METHOD,
            );
        }

        $isPkceVerified = $this->pkceVerifier->verify(
            (string)$mcpAuthorizationCodeRedemptionRequestTransfer->getCodeVerifier(),
            (string)$mcpAuthorizationCodeTransfer->getCodeChallenge(),
            $codeChallengeMethod,
        );

        if (!$isPkceVerified) {
            return $this->createErrorResponse(static::ERROR_CODE_INVALID_GRANT, static::ERROR_MESSAGE_PKCE_FAILED);
        }

        return null;
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return bool
     */
    protected function isExpired(McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer): bool
    {
        $expiresAt = $mcpAuthorizationCodeTransfer->getExpiresAt();

        if ($expiresAt === null) {
            return true;
        }

        return new DateTimeImmutable($expiresAt) <= new DateTimeImmutable();
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpIdentityTransfer
     */
    protected function createMcpIdentityTransfer(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpIdentityTransfer {
        return (new McpIdentityTransfer())
            ->setCustomerReference($mcpAuthorizationCodeTransfer->getCustomerReference())
            ->setIdCustomer($mcpAuthorizationCodeTransfer->getIdCustomer())
            ->setClientIdentifier($mcpAuthorizationCodeTransfer->getClientIdentifier())
            ->setScopes($mcpAuthorizationCodeTransfer->getScopes());
    }

    /**
     * @param string $errorCode
     * @param string $errorMessage
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeRedemptionResponseTransfer
     */
    protected function createErrorResponse(
        string $errorCode,
        string $errorMessage,
    ): McpAuthorizationCodeRedemptionResponseTransfer {
        return (new McpAuthorizationCodeRedemptionResponseTransfer())
            ->setIsSuccessful(false)
            ->setErrorCode($errorCode)
            ->setErrorMessage($errorMessage);
    }
}
