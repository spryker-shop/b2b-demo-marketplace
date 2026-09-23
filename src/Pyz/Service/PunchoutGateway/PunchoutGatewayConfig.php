<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Service\PunchoutGateway;

use SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig as SprykerPunchoutGatewayConfig;

class PunchoutGatewayConfig extends SprykerPunchoutGatewayConfig
{
    /**
     * @api
     *
     * @return array<string>
     */
    public function getNonAutocompleteTransferFieldPrefixes(): array
    {
        return ['spy', 'pyz'];
    }
}
