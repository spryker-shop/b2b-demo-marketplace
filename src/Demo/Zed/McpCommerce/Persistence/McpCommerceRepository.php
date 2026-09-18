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
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommercePersistenceFactory getFactory()
 */
class McpCommerceRepository extends AbstractRepository implements McpCommerceRepositoryInterface
{
    /**
     * @param string $code
     *
     * @return \Generated\Shared\Transfer\McpAuthorizationCodeTransfer|null
     */
    public function findMcpAuthorizationCodeByCode(string $code): ?McpAuthorizationCodeTransfer
    {
        $pyzMcpAuthCodeEntity = $this->getFactory()
            ->createMcpAuthCodeQuery()
            ->filterByCode($code)
            ->findOne();

        if ($pyzMcpAuthCodeEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createMcpCommerceMapper()
            ->mapPyzMcpAuthCodeEntityToMcpAuthorizationCodeTransfer(
                $pyzMcpAuthCodeEntity,
                new McpAuthorizationCodeTransfer(),
            );
    }

    /**
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\McpAccessTokenTransfer|null
     */
    public function findMcpAccessTokenByIdentifier(string $identifier): ?McpAccessTokenTransfer
    {
        $pyzMcpAccessTokenEntity = $this->getFactory()
            ->createMcpAccessTokenQuery()
            ->filterByIdentifier($identifier)
            ->findOne();

        if ($pyzMcpAccessTokenEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createMcpCommerceMapper()
            ->mapPyzMcpAccessTokenEntityToMcpAccessTokenTransfer(
                $pyzMcpAccessTokenEntity,
                new McpAccessTokenTransfer(),
            );
    }

    /**
     * @param string $identifier
     *
     * @return \Generated\Shared\Transfer\McpClientTransfer|null
     */
    public function findMcpClientByIdentifier(string $identifier): ?McpClientTransfer
    {
        $spyOauthClientEntity = $this->getFactory()
            ->createOauthClientQuery()
            ->filterByIdentifier($identifier)
            ->findOne();

        if ($spyOauthClientEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createMcpCommerceMapper()
            ->mapSpyOauthClientEntityToMcpClientTransfer($spyOauthClientEntity, new McpClientTransfer());
    }
}
