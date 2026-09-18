<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Security;

use Generated\Shared\Transfer\McpIdentityTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationRequestTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use JsonException;
use Spryker\Client\AuthRestApi\AuthRestApiClientInterface;
use Spryker\Client\Oauth\OauthClientInterface;

/**
 * Turns shop email-and-password credentials into the minimal identity claims an MCP session needs.
 *
 * The existing storefront password grant does the credential check, which keeps password handling in
 * one place. The shop access token it mints is used **only** to read the `customer_reference` and
 * `id_customer` claims out of its `sub` payload, and is then dropped along with the refresh token:
 * neither value is stored, logged, returned to the caller, or forwarded anywhere. Everything
 * downstream of this class works from the two claims alone, which is what keeps the customer's shop
 * session structurally unreachable for the AI client.
 */
class McpCustomerIdentityReader implements McpCustomerIdentityReaderInterface
{
    /**
     * @uses \Spryker\Glue\AuthRestApi\AuthRestApiConfig::CLIENT_GRANT_PASSWORD
     *
     * @var string
     */
    protected const GRANT_TYPE_PASSWORD = 'password';

    /**
     * @var string
     */
    protected const TOKEN_TYPE_BEARER = 'Bearer';

    /**
     * @var string
     */
    protected const CLAIM_CUSTOMER_REFERENCE = 'customer_reference';

    /**
     * @var string
     */
    protected const CLAIM_ID_CUSTOMER = 'id_customer';

    public function __construct(
        protected readonly AuthRestApiClientInterface $authRestApiClient,
        protected readonly OauthClientInterface $oauthClient,
    ) {
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return \Generated\Shared\Transfer\McpIdentityTransfer|null
     */
    public function findIdentityByCredentials(string $email, string $password): ?McpIdentityTransfer
    {
        if (trim($email) === '' || $password === '') {
            return null;
        }

        $oauthResponseTransfer = $this->authRestApiClient->createAccessToken(
            (new OauthRequestTransfer())
                ->setGrantType(static::GRANT_TYPE_PASSWORD)
                ->setUsername($email)
                ->setPassword($password),
        );

        if ($oauthResponseTransfer->getIsValid() !== true) {
            return null;
        }

        $identityClaims = $this->readIdentityClaims((string)$oauthResponseTransfer->getAccessToken());

        if ($identityClaims === null) {
            return null;
        }

        return $this->createMcpIdentityTransfer($identityClaims);
    }

    /**
     * The freshly minted shop token is consumed here and nowhere else. Only the two identity claims
     * survive this method; the token string itself goes out of scope with the local variable.
     *
     * @return array<string, mixed>|null
     */
    protected function readIdentityClaims(string $shopAccessToken): ?array
    {
        if ($shopAccessToken === '') {
            return null;
        }

        $oauthAccessTokenValidationResponseTransfer = $this->oauthClient->validateOauthAccessToken(
            (new OauthAccessTokenValidationRequestTransfer())
                ->setAccessToken($shopAccessToken)
                ->setType(static::TOKEN_TYPE_BEARER),
        );

        if ($oauthAccessTokenValidationResponseTransfer->getIsValid() !== true) {
            return null;
        }

        $oauthUserId = (string)$oauthAccessTokenValidationResponseTransfer->getOauthUserId();

        if ($oauthUserId === '') {
            return null;
        }

        try {
            $identityClaims = json_decode($oauthUserId, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($identityClaims) ? $identityClaims : null;
    }

    /**
     * @param array<string, mixed> $identityClaims
     */
    protected function createMcpIdentityTransfer(array $identityClaims): ?McpIdentityTransfer
    {
        $customerReference = $identityClaims[static::CLAIM_CUSTOMER_REFERENCE] ?? null;

        if (!is_string($customerReference) || trim($customerReference) !== $customerReference || $customerReference === '') {
            return null;
        }

        $idCustomer = $identityClaims[static::CLAIM_ID_CUSTOMER] ?? null;

        if (!is_numeric($idCustomer) || (int)$idCustomer <= 0) {
            return null;
        }

        return (new McpIdentityTransfer())
            ->setCustomerReference($customerReference)
            ->setIdCustomer((int)$idCustomer);
    }
}
