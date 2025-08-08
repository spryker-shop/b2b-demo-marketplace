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
        return (new RedisCredentialsTransfer())
            ->setScheme($this->getZedScheme())
            ->setHost($this->get(SessionRedisConstants::ZED_SESSION_REDIS_HOST))
            ->setPort($this->get(SessionRedisConstants::ZED_SESSION_REDIS_PORT))
            ->setDatabase($this->get(SessionRedisConstants::ZED_SESSION_REDIS_DATABASE))
            ->setPassword($this->get(SessionRedisConstants::ZED_SESSION_REDIS_PASSWORD, false))
            ->setSsl($this->get(SessionRedisConstants::ZED_SESSION_REDIS_CLIENT_OPTIONS, [])['ssl'] ?? []);
    }
}
