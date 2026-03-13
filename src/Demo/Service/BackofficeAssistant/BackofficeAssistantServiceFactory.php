<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Service\BackofficeAssistant;

use Demo\Service\BackofficeAssistant\Generator\ConversationReferenceGenerator;
use Demo\Service\BackofficeAssistant\Generator\ConversationReferenceGeneratorInterface;
use Spryker\Service\Kernel\AbstractServiceFactory;

class BackofficeAssistantServiceFactory extends AbstractServiceFactory
{
    public function createConversationReferenceGenerator(): ConversationReferenceGeneratorInterface
    {
        return new ConversationReferenceGenerator();
    }
}
