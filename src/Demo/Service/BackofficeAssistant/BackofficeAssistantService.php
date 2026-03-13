<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Service\BackofficeAssistant;

use Spryker\Service\Kernel\AbstractService;

/**
 * @method \Demo\Service\BackofficeAssistant\BackofficeAssistantServiceFactory getFactory()
 */
class BackofficeAssistantService extends AbstractService implements BackofficeAssistantServiceInterface
{
    public function generateConversationReference(string $userReference): string
    {
        return $this->getFactory()
            ->createConversationReferenceGenerator()
            ->generate($userReference);
    }
}
