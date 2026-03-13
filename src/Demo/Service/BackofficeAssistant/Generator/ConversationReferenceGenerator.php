<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Service\BackofficeAssistant\Generator;

class ConversationReferenceGenerator implements ConversationReferenceGeneratorInterface
{
    public function generate(string $userReference): string
    {
        return sprintf('%s:%d:%s', $userReference, time(), bin2hex(random_bytes(8)));
    }
}
