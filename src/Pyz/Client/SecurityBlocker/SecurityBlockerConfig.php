<?php

namespace Pyz\Client\SecurityBlocker;

use Generated\Shared\Transfer\RedisCredentialsTransfer;
use Spryker\Shared\SecurityBlocker\SecurityBlockerConstants;

class SecurityBlockerConfig extends \Spryker\Client\SecurityBlocker\SecurityBlockerConfig
{
    /**
     * @return \Generated\Shared\Transfer\RedisCredentialsTransfer
     */
    protected function getConnectionCredentials(): RedisCredentialsTransfer
    {
        return (new RedisCredentialsTransfer())
            ->setScheme($this->getZedSessionScheme())
            ->setHost($this->get(SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_HOST))
            ->setPort($this->get(SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PORT))
            ->setDatabase($this->get(SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_DATABASE))
            ->setPassword($this->get(SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PASSWORD, false))
            ->setIsPersistent($this->get(SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PERSISTENT_CONNECTION, false))
            ->setSsl($this->get(SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_CONNECTION_OPTIONS, [])['ssl'] ?? []);
    }
}
