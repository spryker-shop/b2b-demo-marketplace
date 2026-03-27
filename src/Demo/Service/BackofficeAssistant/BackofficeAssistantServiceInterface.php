<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Service\BackofficeAssistant;

/**
 * @method \Demo\Service\BackofficeAssistant\BackofficeAssistantServiceFactory getFactory()
 */
interface BackofficeAssistantServiceInterface
{
    /**
     * Specification:
     * - Generates a server-side unique conversation reference in format: userReference:timestamp:random.
     *
     * @api
     *
     * @param string $userReference
     *
     * @return string
     */
    public function generateConversationReference(string $userReference): string;
}
