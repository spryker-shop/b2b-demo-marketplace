<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\McpCommerce\Persistence;

use Demo\Zed\McpCommerce\Persistence\Propel\Mapper\McpCommerceMapper;
use Orm\Zed\McpCommerce\Persistence\PyzMcpAccessToken;
use Orm\Zed\McpCommerce\Persistence\PyzMcpAccessTokenQuery;
use Orm\Zed\McpCommerce\Persistence\PyzMcpAuthCode;
use Orm\Zed\McpCommerce\Persistence\PyzMcpAuthCodeQuery;
use Orm\Zed\Oauth\Persistence\SpyOauthClient;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceRepositoryInterface getRepository()
 * @method \Demo\Zed\McpCommerce\Persistence\McpCommerceEntityManagerInterface getEntityManager()
 * @method \Demo\Zed\McpCommerce\McpCommerceConfig getConfig()
 */
class McpCommercePersistenceFactory extends AbstractPersistenceFactory
{
    public function createMcpAuthCodeQuery(): PyzMcpAuthCodeQuery
    {
        return PyzMcpAuthCodeQuery::create();
    }

    public function createMcpAccessTokenQuery(): PyzMcpAccessTokenQuery
    {
        return PyzMcpAccessTokenQuery::create();
    }

    public function createMcpAuthCodeEntity(): PyzMcpAuthCode
    {
        return new PyzMcpAuthCode();
    }

    public function createMcpAccessTokenEntity(): PyzMcpAccessToken
    {
        return new PyzMcpAccessToken();
    }

    public function createMcpCommerceMapper(): McpCommerceMapper
    {
        return new McpCommerceMapper();
    }

    public function createOauthClientQuery(): SpyOauthClientQuery
    {
        return SpyOauthClientQuery::create();
    }

    public function createOauthClientEntity(): SpyOauthClient
    {
        return new SpyOauthClient();
    }
}
