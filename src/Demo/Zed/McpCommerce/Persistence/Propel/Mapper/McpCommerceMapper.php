<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Persistence\Propel\Mapper;

use DateTimeInterface;
use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientTransfer;
use Orm\Zed\McpCommerce\Persistence\PyzMcpAccessToken;
use Orm\Zed\McpCommerce\Persistence\PyzMcpAuthCode;
use Orm\Zed\Oauth\Persistence\SpyOauthClient;

class McpCommerceMapper
{
    /**
     * @var string
     */
    protected const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param \Orm\Zed\McpCommerce\Persistence\PyzMcpAuthCode $pyzMcpAuthCodeEntity
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function mapPyzMcpAuthCodeEntityToMcpAuthorizationCodeTransfer(
        PyzMcpAuthCode $pyzMcpAuthCodeEntity,
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer {
        return $mcpAuthorizationCodeTransfer
            ->setIdMcpAuthCode($pyzMcpAuthCodeEntity->getIdMcpAuthCode())
            ->setCode($pyzMcpAuthCodeEntity->getCode())
            ->setClientIdentifier($pyzMcpAuthCodeEntity->getClientIdentifier())
            ->setCustomerReference($pyzMcpAuthCodeEntity->getCustomerReference())
            ->setIdCustomer($pyzMcpAuthCodeEntity->getIdCustomer())
            ->setCodeChallenge($pyzMcpAuthCodeEntity->getCodeChallenge())
            ->setCodeChallengeMethod($pyzMcpAuthCodeEntity->getCodeChallengeMethod())
            ->setRedirectUri($pyzMcpAuthCodeEntity->getRedirectUri())
            ->setScopes($pyzMcpAuthCodeEntity->getScopes())
            ->setExpiresAt($this->formatDateTime($pyzMcpAuthCodeEntity->getExpiresAt()))
            ->setIsUsed((bool)$pyzMcpAuthCodeEntity->getIsUsed());
    }

    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     * @param \Orm\Zed\McpCommerce\Persistence\PyzMcpAuthCode $pyzMcpAuthCodeEntity
     *
     * @return \Orm\Zed\McpCommerce\Persistence\PyzMcpAuthCode
     */
    public function mapMcpAuthorizationCodeTransferToPyzMcpAuthCodeEntity(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
        PyzMcpAuthCode $pyzMcpAuthCodeEntity,
    ): PyzMcpAuthCode {
        return $pyzMcpAuthCodeEntity
            ->setCode($mcpAuthorizationCodeTransfer->getCodeOrFail())
            ->setClientIdentifier($mcpAuthorizationCodeTransfer->getClientIdentifierOrFail())
            ->setCustomerReference($mcpAuthorizationCodeTransfer->getCustomerReferenceOrFail())
            ->setIdCustomer($mcpAuthorizationCodeTransfer->getIdCustomerOrFail())
            ->setCodeChallenge($mcpAuthorizationCodeTransfer->getCodeChallengeOrFail())
            ->setCodeChallengeMethod($mcpAuthorizationCodeTransfer->getCodeChallengeMethodOrFail())
            ->setRedirectUri($mcpAuthorizationCodeTransfer->getRedirectUriOrFail())
            ->setScopes($mcpAuthorizationCodeTransfer->getScopes())
            ->setExpiresAt($mcpAuthorizationCodeTransfer->getExpiresAtOrFail())
            ->setIsUsed((bool)$mcpAuthorizationCodeTransfer->getIsUsed());
    }

    /**
     * @param \Orm\Zed\McpCommerce\Persistence\PyzMcpAccessToken $pyzMcpAccessTokenEntity
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function mapPyzMcpAccessTokenEntityToMcpAccessTokenTransfer(
        PyzMcpAccessToken $pyzMcpAccessTokenEntity,
        McpAccessTokenTransfer $mcpAccessTokenTransfer,
    ): McpAccessTokenTransfer {
        return $mcpAccessTokenTransfer
            ->setIdMcpAccessToken($pyzMcpAccessTokenEntity->getIdMcpAccessToken())
            ->setIdentifier($pyzMcpAccessTokenEntity->getIdentifier())
            ->setClientIdentifier($pyzMcpAccessTokenEntity->getClientIdentifier())
            ->setCustomerReference($pyzMcpAccessTokenEntity->getCustomerReference())
            ->setIdCustomer($pyzMcpAccessTokenEntity->getIdCustomer())
            ->setScopes($pyzMcpAccessTokenEntity->getScopes())
            ->setExpiresAt($this->formatDateTime($pyzMcpAccessTokenEntity->getExpiresAt()))
            ->setIsRevoked((bool)$pyzMcpAccessTokenEntity->getIsRevoked());
    }

    /**
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     * @param \Orm\Zed\McpCommerce\Persistence\PyzMcpAccessToken $pyzMcpAccessTokenEntity
     *
     * @return \Orm\Zed\McpCommerce\Persistence\PyzMcpAccessToken
     */
    public function mapMcpAccessTokenTransferToPyzMcpAccessTokenEntity(
        McpAccessTokenTransfer $mcpAccessTokenTransfer,
        PyzMcpAccessToken $pyzMcpAccessTokenEntity,
    ): PyzMcpAccessToken {
        return $pyzMcpAccessTokenEntity
            ->setIdentifier($mcpAccessTokenTransfer->getIdentifierOrFail())
            ->setClientIdentifier($mcpAccessTokenTransfer->getClientIdentifierOrFail())
            ->setCustomerReference($mcpAccessTokenTransfer->getCustomerReferenceOrFail())
            ->setIdCustomer($mcpAccessTokenTransfer->getIdCustomerOrFail())
            ->setScopes($mcpAccessTokenTransfer->getScopes())
            ->setExpiresAt($mcpAccessTokenTransfer->getExpiresAtOrFail())
            ->setIsRevoked((bool)$mcpAccessTokenTransfer->getIsRevoked());
    }

    /**
     * @param \Orm\Zed\Oauth\Persistence\SpyOauthClient $spyOauthClientEntity
     * @param \Generated\Shared\Transfer\McpClientTransfer $mcpClientTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer
     */
    public function mapSpyOauthClientEntityToMcpClientTransfer(
        SpyOauthClient $spyOauthClientEntity,
        McpClientTransfer $mcpClientTransfer,
    ): McpClientTransfer {
        $isConfidential = (bool)$spyOauthClientEntity->getIsConfidential();

        return $mcpClientTransfer
            ->setIdentifier($spyOauthClientEntity->getIdentifier())
            ->setClientName($spyOauthClientEntity->getName())
            ->setRedirectUri($spyOauthClientEntity->getRedirectUri())
            ->setIsConfidential($isConfidential)
            ->setIsPkceRequired(!$isConfidential);
    }

    /**
     * @param \Generated\Shared\Transfer\McpClientTransfer $mcpClientTransfer
     * @param \Orm\Zed\Oauth\Persistence\SpyOauthClient $spyOauthClientEntity
     *
     * @return \Orm\Zed\Oauth\Persistence\SpyOauthClient
     */
    public function mapMcpClientTransferToSpyOauthClientEntity(
        McpClientTransfer $mcpClientTransfer,
        SpyOauthClient $spyOauthClientEntity,
    ): SpyOauthClient {
        return $spyOauthClientEntity
            ->setIdentifier($mcpClientTransfer->getIdentifierOrFail())
            ->setName($mcpClientTransfer->getClientNameOrFail())
            ->setRedirectUri($mcpClientTransfer->getRedirectUriOrFail())
            ->setIsConfidential((bool)$mcpClientTransfer->getIsConfidential());
    }

    /**
     * @param \DateTimeInterface|string|null $dateTime
     *
     * @return string|null
     */
    protected function formatDateTime($dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        if ($dateTime instanceof DateTimeInterface) {
            return $dateTime->format(static::DATE_TIME_FORMAT);
        }

        return (string)$dateTime;
    }
}
