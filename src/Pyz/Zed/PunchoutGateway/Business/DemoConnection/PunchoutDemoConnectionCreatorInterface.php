<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Zed\PunchoutGateway\Business\DemoConnection;

use Generated\Shared\Transfer\PunchoutConnectionCollectionTransfer;

interface PunchoutDemoConnectionCreatorInterface
{
    public function createDemoPunchoutConnections(
        PunchoutConnectionCollectionTransfer $punchoutConnectionCollectionTransfer,
    ): PunchoutConnectionCollectionTransfer;
}
