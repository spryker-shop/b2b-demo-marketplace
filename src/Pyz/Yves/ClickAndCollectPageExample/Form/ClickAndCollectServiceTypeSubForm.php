<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\ClickAndCollectPageExample\Form;

use Pyz\Yves\ClickAndCollectPageExample\ClickAndCollectPageExampleConfig;
use SprykerShop\Yves\ClickAndCollectPageExample\Form\ClickAndCollectServiceTypeSubForm as SprykerClickAndCollectServiceTypeSubForm;

/**
 * @method \Pyz\Yves\ClickAndCollectPageExample\ClickAndCollectPageExampleConfig getConfig()
 */
class ClickAndCollectServiceTypeSubForm extends SprykerClickAndCollectServiceTypeSubForm
{
    /**
     * @var string
     */
    protected const SERVICE_TYPE_PICKUP = ClickAndCollectPageExampleConfig::SHIPMENT_TYPE_ON_SITE_SERVICE;
}
