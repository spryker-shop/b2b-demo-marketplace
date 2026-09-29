<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Persistence;

use Generated\Shared\Transfer\McpAccessTokenTransfer;
use Generated\Shared\Transfer\McpAuthorizationCodeTransfer;
use Generated\Shared\Transfer\McpClientTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommercePersistenceFactory getFactory()
 */
class McpCommerceEntityManager extends AbstractEntityManager implements McpCommerceEntityManagerInterface
{
    /**
     * @param \Generated\Shared\Transfer\McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer
     */
    public function createMcpAuthorizationCode(
        McpAuthorizationCodeTransfer $mcpAuthorizationCodeTransfer,
    ): McpAuthorizationCodeTransfer {
        $mcpCommerceMapper = $this->getFactory()->createMcpCommerceMapper();

        $pyzMcpAuthCodeEntity = $mcpCommerceMapper->mapMcpAuthorizationCodeTransferToPyzMcpAuthCodeEntity(
            $mcpAuthorizationCodeTransfer,
            $this->getFactory()->createMcpAuthCodeEntity(),
        );
        $pyzMcpAuthCodeEntity->save();

        return $mcpCommerceMapper->mapPyzMcpAuthCodeEntityToMcpAuthorizationCodeTransfer(
            $pyzMcpAuthCodeEntity,
            new McpAuthorizationCodeTransfer(),
        );
    }

    /**
     * @param string $code
     *
     * @return int
     */
    public function markMcpAuthorizationCodeAsUsed(string $code): int
    {
        return $this->getFactory()
            ->createMcpAuthCodeQuery()
            ->filterByCode($code)
            ->filterByIsUsed(false)
            ->update(['IsUsed' => true]);
    }

    /**
     * @param \Generated\Shared\Transfer\McpAccessTokenTransfer $mcpAccessTokenTransfer
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer
     */
    public function createMcpAccessToken(McpAccessTokenTransfer $mcpAccessTokenTransfer): McpAccessTokenTransfer
    {
        $mcpCommerceMapper = $this->getFactory()->createMcpCommerceMapper();

        $pyzMcpAccessTokenEntity = $mcpCommerceMapper->mapMcpAccessTokenTransferToPyzMcpAccessTokenEntity(
            $mcpAccessTokenTransfer,
            $this->getFactory()->createMcpAccessTokenEntity(),
        );
        $pyzMcpAccessTokenEntity->save();

        return $mcpCommerceMapper->mapPyzMcpAccessTokenEntityToMcpAccessTokenTransfer(
            $pyzMcpAccessTokenEntity,
            new McpAccessTokenTransfer(),
        );
    }

    /**
     * @param string $identifier
     *
     * @return int
     */
    public function revokeMcpAccessToken(string $identifier): int
    {
        return $this->getFactory()
            ->createMcpAccessTokenQuery()
            ->filterByIdentifier($identifier)
            ->filterByIsRevoked(false)
            ->update(['IsRevoked' => true]);
    }

    /**
     * @param string $expiresBefore
     *
     * @return int
     */
    public function deleteExpiredMcpAuthorizationCodes(string $expiresBefore): int
    {
        return $this->getFactory()
            ->createMcpAuthCodeQuery()
            ->filterByExpiresAt($expiresBefore, '<')
            ->delete();
    }

    /**
     * @param \Generated\Shared\Transfer\McpClientTransfer $mcpClientTransfer
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer
     */
    public function createMcpClient(McpClientTransfer $mcpClientTransfer): McpClientTransfer
    {
        $mcpCommerceMapper = $this->getFactory()->createMcpCommerceMapper();

        $spyOauthClientEntity = $mcpCommerceMapper->mapMcpClientTransferToSpyOauthClientEntity(
            $mcpClientTransfer,
            $this->getFactory()->createOauthClientEntity(),
        );
        $spyOauthClientEntity->save();

        return $mcpCommerceMapper->mapSpyOauthClientEntityToMcpClientTransfer(
            $spyOauthClientEntity,
            new McpClientTransfer(),
        );
    }
}
