<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Business\Client;

use Demo\Zed\McpCommerce\Business\Generator\OpaqueIdentifierGeneratorInterface;
use Demo\Zed\McpCommerce\McpCommerceConfig;
use Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface;
use Generated\Shared\Transfer\McpClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\McpClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\McpClientTransfer;

class McpClientRegistrar implements McpClientRegistrarInterface
{
    /**
     * @var string
     */
    public const ERROR_CODE_INVALID_CLIENT_METADATA = 'invalid_client_metadata';

    /**
     * @var string
     */
    public const ERROR_CODE_INVALID_REDIRECT_URI = 'invalid_redirect_uri';

    /**
     * @var string
     */
    public const FIELD_REDIRECT_URIS = 'redirect_uris';

    /**
     * @var string
     */
    public const FIELD_CLIENT_NAME = 'client_name';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_MISSING_REDIRECT_URIS = 'At least one redirect URI must be provided in redirect_uris.';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_REDIRECT_URI_NOT_ALLOWED = 'The redirect URI is not covered by the configured allow-list.';

    /**
     * @var string
     */
    protected const DEFAULT_CLIENT_NAME = 'MCP client';

    public function __construct(
        protected McpCommerceEntityManagerInterface $mcpCommerceEntityManager,
        protected OpaqueIdentifierGeneratorInterface $opaqueIdentifierGenerator,
        protected McpCommerceConfig $mcpCommerceConfig,
    ) {
    }

    /**
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    public function register(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
    ): McpClientRegistrationResponseTransfer {
        $redirectUris = $this->filterRedirectUris($mcpClientRegistrationRequestTransfer->getRedirectUris());

        if ($redirectUris === []) {
            return $this->createErrorResponse(
                static::ERROR_CODE_INVALID_REDIRECT_URI,
                static::ERROR_MESSAGE_MISSING_REDIRECT_URIS,
                static::FIELD_REDIRECT_URIS,
            );
        }

        if (!$this->areRedirectUrisAllowed($redirectUris)) {
            return $this->createErrorResponse(
                static::ERROR_CODE_INVALID_REDIRECT_URI,
                static::ERROR_MESSAGE_REDIRECT_URI_NOT_ALLOWED,
                static::FIELD_REDIRECT_URIS,
            );
        }

        $mcpClientTransfer = $this->mcpCommerceEntityManager->createMcpClient(
            $this->createMcpClientTransfer($mcpClientRegistrationRequestTransfer, $redirectUris[0]),
        );

        return (new McpClientRegistrationResponseTransfer())
            ->setIsSuccessful(true)
            ->setMcpClient($mcpClientTransfer);
    }

    /**
     * @param array<mixed> $redirectUris
     *
     * @return array<int, string>
     */
    protected function filterRedirectUris(array $redirectUris): array
    {
        $filteredRedirectUris = [];

        foreach ($redirectUris as $redirectUri) {
            if (!is_string($redirectUri) || trim($redirectUri) === '') {
                continue;
            }

            $filteredRedirectUris[] = trim($redirectUri);
        }

        return $filteredRedirectUris;
    }

    /**
     * Every submitted redirect URI must pass the allow-list: accepting a registration because only
     * one of several URIs matches would let an unlisted URI be used at authorization time.
     *
     * @param array<int, string> $redirectUris
     *
     * @return bool
     */
    protected function areRedirectUrisAllowed(array $redirectUris): bool
    {
        foreach ($redirectUris as $redirectUri) {
            if (!$this->isRedirectUriAllowed($redirectUri)) {
                return false;
            }
        }

        return true;
    }

    protected function isRedirectUriAllowed(string $redirectUri): bool
    {
        foreach ($this->mcpCommerceConfig->getAllowedRedirectUriPatterns() as $allowedRedirectUriPattern) {
            if (preg_match($allowedRedirectUriPattern, $redirectUri) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Generated\Shared\Transfer\McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer
     * @param string $redirectUri
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer
     */
    protected function createMcpClientTransfer(
        McpClientRegistrationRequestTransfer $mcpClientRegistrationRequestTransfer,
        string $redirectUri,
    ): McpClientTransfer {
        $clientName = trim((string)$mcpClientRegistrationRequestTransfer->getClientName());

        return (new McpClientTransfer())
            ->setIdentifier($this->createClientIdentifier())
            ->setClientName($clientName === '' ? static::DEFAULT_CLIENT_NAME : $clientName)
            ->setRedirectUri($redirectUri)
            ->setIsConfidential(false)
            ->setIsPkceRequired(true);
    }

    protected function createClientIdentifier(): string
    {
        return $this->mcpCommerceConfig->getClientIdentifierPrefix() . $this->opaqueIdentifierGenerator->generate();
    }

    /**
     * @param string $errorCode
     * @param string $errorMessage
     * @param string $invalidField
     *
     * @return \Generated\Shared\Transfer\McpClientRegistrationResponseTransfer
     */
    protected function createErrorResponse(
        string $errorCode,
        string $errorMessage,
        string $invalidField,
    ): McpClientRegistrationResponseTransfer {
        return (new McpClientRegistrationResponseTransfer())
            ->setIsSuccessful(false)
            ->setErrorCode($errorCode)
            ->setErrorMessage($errorMessage)
            ->setInvalidField($invalidField);
    }
}
