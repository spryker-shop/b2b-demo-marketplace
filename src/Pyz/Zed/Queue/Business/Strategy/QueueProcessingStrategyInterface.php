<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\Strategy;

use Generator;

interface QueueProcessingStrategyInterface
{
    public function getNextQueue(): Generator;
}
