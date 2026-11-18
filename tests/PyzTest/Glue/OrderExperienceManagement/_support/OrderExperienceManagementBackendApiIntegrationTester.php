<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\OrderExperienceManagement;

use Codeception\Actor;

/**
 * Actor for the full-layer Backend API integration suite (BackendApiIntegration).
 *
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(\PyzTest\Glue\OrderExperienceManagement\PHPMD)
 */
class OrderExperienceManagementBackendApiIntegrationTester extends Actor
{
    use _generated\OrderExperienceManagementBackendApiIntegrationTesterActions;
}
