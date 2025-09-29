<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Client\OpenAi\Reader;

interface ModelResponseInterface
{
    public function create(array $messages, ?string $instructions = null, array $tools = []): array;

    public function createForAgent(array $messages): array;
}
