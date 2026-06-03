<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\MoneyGui\Communication\Plugin\Form;

use Demo\Zed\MoneyGui\Communication\Form\Type\MoneyType;
use Spryker\Zed\MoneyGui\Communication\Plugin\Form\MoneyFormTypePlugin as SprykerMoneyFormTypePlugin;

/**
 * @method \Spryker\Zed\MoneyGui\MoneyGuiConfig getConfig()
 * @method \Spryker\Zed\MoneyGui\Communication\MoneyGuiCommunicationFactory getFactory()
 */
class MoneyFormTypePlugin extends SprykerMoneyFormTypePlugin
{
    public function getType(): string
    {
        return MoneyType::class;
    }
}
