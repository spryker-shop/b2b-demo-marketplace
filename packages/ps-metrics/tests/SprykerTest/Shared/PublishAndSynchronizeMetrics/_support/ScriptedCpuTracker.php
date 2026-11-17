<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics;

use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\CpuTracker;

/**
 * Replaces `getrusage()` with counters a test sets, so CPU deltas are exact.
 */
class ScriptedCpuTracker extends CpuTracker
{
    public int $userMicroseconds = 0;

    public int $systemMicroseconds = 0;

    public bool $isReadable = true;

    /**
     * @param int $userMicroseconds
     * @param int $systemMicroseconds
     *
     * @return void
     */
    public function spend(int $userMicroseconds, int $systemMicroseconds): void
    {
        $this->userMicroseconds += $userMicroseconds;
        $this->systemMicroseconds += $systemMicroseconds;
    }

    /**
     * @return array<string, int>|null
     */
    protected function readUsage(): ?array
    {
        if (!$this->isReadable) {
            return null;
        }

        return [
            static::USAGE_USER_US => $this->userMicroseconds,
            static::USAGE_SYS_US => $this->systemMicroseconds,
        ];
    }
}
