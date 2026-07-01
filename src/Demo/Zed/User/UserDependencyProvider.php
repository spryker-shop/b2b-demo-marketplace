<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\User;

use Pyz\Zed\User\UserDependencyProvider as PyzUserDependencyProvider;
use SprykerEco\Zed\AmazonQuicksight\Communication\Plugin\User\DeleteQuicksightUserPostUpdatePlugin;
use SprykerEco\Zed\AmazonQuicksight\Communication\Plugin\User\QuicksightUserExpanderPlugin;

class UserDependencyProvider extends PyzUserDependencyProvider
{
    /**
     * @return list<\Spryker\Zed\UserExtension\Dependency\Plugin\UserExpanderPluginInterface>
     */
    protected function getUserExpanderPlugins(): array
    {
        return [
            new QuicksightUserExpanderPlugin(),
        ];
    }

    /**
     * @return list<\Spryker\Zed\UserExtension\Dependency\Plugin\UserPostUpdatePluginInterface>
     */
    protected function getUserPostUpdatePlugins(): array
    {
        return [
            new DeleteQuicksightUserPostUpdatePlugin(),
        ];
    }
}
