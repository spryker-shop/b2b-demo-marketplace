<?php

declare(strict_types = 1);

namespace Pyz\Zed\Queue\Business\SystemResources;

interface SystemResourcesManagerInterface
{
    /**
     * @param bool $shouldIgnore
     *
     * @return bool
     */
    public function enoughResources(bool $shouldIgnore = false): bool;

    /**
     * Result is in MB
     *
     * @return int
     */
    public function getFreeMemory(): int;
}
