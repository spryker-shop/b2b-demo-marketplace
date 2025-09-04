<?php

namespace Pyz\Zed\User\Business;

use Pyz\Zed\User\Business\Model\User;
use Spryker\Zed\User\Business\Model\UserInterface;

class UserBusinessFactory extends \Spryker\Zed\User\Business\UserBusinessFactory
{
    public function createUserModel(): UserInterface
    {
        return new User(
            $this->getQueryContainer(),
            $this->getSessionClient(),
            $this->getConfig(),
            $this->getPostSavePlugins(),
            $this->getUserPreSavePlugins(),
            $this->getUserTransferExpanderPlugins(),
            $this->getUserExpanderPlugins(),
            $this->getUserPostCreatePlugins(),
            $this->getUserPostUpdatePlugins(),
        );
    }
}
