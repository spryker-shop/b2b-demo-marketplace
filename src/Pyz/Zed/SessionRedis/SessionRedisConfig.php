<?php

namespace Pyz\Zed\SessionRedis;

use Generated\Shared\Transfer\RedisCredentialsTransfer;
use Spryker\Shared\SessionRedis\SessionRedisConstants;

class SessionRedisConfig extends \Spryker\Zed\SessionRedis\SessionRedisConfig
{

    /**
     * @return \Generated\Shared\Transfer\RedisCredentialsTransfer
     */
    protected function getZedConnectionCredentials(): RedisCredentialsTransfer
    {
        return parent::getZedConnectionCredentials()
            ->setSsl($this->get(SessionRedisConstants::ZED_SESSION_REDIS_CLIENT_OPTIONS, [])['ssl'] ?? []);
    }
}
