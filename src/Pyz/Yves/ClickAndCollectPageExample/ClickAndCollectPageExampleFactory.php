<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\ClickAndCollectPageExample;

use Pyz\Yves\ClickAndCollectPageExample\Form\ClickAndCollectServiceTypeSubForm;
use Spryker\Yves\Kernel\AbstractFactory;
use Symfony\Component\Form\FormTypeInterface;

class ClickAndCollectPageExampleFactory extends AbstractFactory
{
    /**
     * @return \Symfony\Component\Form\FormTypeInterface
     */
    public function createClickAndCollectServiceTypeSubForm(): FormTypeInterface
    {
        return new ClickAndCollectServiceTypeSubForm();
    }
}
