<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\SymfonyMailer\Business;

use Pyz\Zed\SymfonyMailer\Dependency\External\SymfonyMailerToSymfonyMailerAdapter;
use Spryker\Zed\SymfonyMailer\Business\SymfonyMailerBusinessFactory as SprykerSymfonyMailerBusinessFactory;
use Spryker\Zed\SymfonyMailer\Dependency\External\SymfonyMailerToMailerInterface;

/**
 * @method \Spryker\Zed\SymfonyMailer\SymfonyMailerConfig getConfig()
 */
class SymfonyMailerBusinessFactory extends SprykerSymfonyMailerBusinessFactory
{
    /**
     * @return \Spryker\Zed\SymfonyMailer\Dependency\External\SymfonyMailerToMailerInterface
     */
    public function createSymfonyMailerAdapter(): SymfonyMailerToMailerInterface
    {
        return new SymfonyMailerToSymfonyMailerAdapter(
            $this->createRenderer(),
            $this->createTranslator(),
            $this->getConfig(),
        );
    }
}
