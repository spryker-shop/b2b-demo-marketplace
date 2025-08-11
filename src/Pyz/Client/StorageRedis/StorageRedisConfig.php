<?php

namespace Pyz\Client\StorageRedis;

use Generated\Shared\Transfer\RedisCredentialsTransfer;
use Spryker\Shared\SessionRedis\SessionRedisConstants;

class StorageRedisConfig extends \Spryker\Client\StorageRedis\StorageRedisConfig
{

    /**
     * @return \Generated\Shared\Transfer\RedisCredentialsTransfer
     */
    protected function getConnectionCredentials(): RedisCredentialsTransfer
    {
        return parent::getConnectionCredentials()
            ->setSsl($this->get(SessionRedisConstants::YVES_SESSION_REDIS_CLIENT_OPTIONS, [])['ssl'] ?? []);
    }
}
