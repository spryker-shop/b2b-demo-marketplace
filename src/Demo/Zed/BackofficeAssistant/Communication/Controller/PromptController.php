<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication\Controller;

use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @method \Demo\Zed\BackofficeAssistant\Communication\BackofficeAssistantCommunicationFactory getFactory()
 * @method \Demo\Zed\BackofficeAssistant\Business\BackofficeAssistantFacadeInterface getFacade()
 */
class PromptController extends AbstractController
{
    public function indexAction(Request $request): StreamedResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $prompt = $data['prompt'] ?? '';
        $conversationReference = $this->resolveConversationReference($prompt, $data['conversation_reference'] ?? '');

        return new StreamedResponse(function () use ($prompt, $conversationReference): void {
            set_time_limit(0);
            ignore_user_abort(true);

            if (!$prompt) {
                $this->emitEvent(['error' => 'Prompt is required']);

                return;
            }

            $promptRequest = (new PromptRequestTransfer())
                ->setPromptMessage(
                    (new PromptMessageTransfer())
                        ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                        ->setContent($prompt),
                )
                ->setConversationReference($conversationReference);

            $response = $this->getFactory()->getAiFoundationFacade()->prompt($promptRequest);

            if (!$response->getIsSuccessful()) {
                $this->emitEvent(['error' => 'AI service unavailable']);

                return;
            }

            $message = $response->getMessage();
            $this->emitEvent([
                'ai_response' => $message?->getContent(),
                'conversation_reference' => $conversationReference,
            ]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    protected function resolveConversationReference(string $prompt, string $requestedRef): string
    {
        if (!$prompt) {
            return '';
        }

        $idUser = (int)$this->getFactory()->getUserFacade()->getCurrentUser()->getIdUser();

        if ($requestedRef && $this->getFacade()->hasConversationHistoryForUser($idUser, $requestedRef)) {
            return $requestedRef;
        }

        return $this->getFacade()
            ->createConversationHistory(
                (new BackofficeAssistantConversationHistoryTransfer())
                    ->setFkUser($idUser)
                    ->setName(mb_substr($prompt, 0, 150)),
            )
            ->getConversationReference();
    }

    /**
     * @param array<mixed> $payload
     */
    protected function emitEvent(array $payload): void
    {
        echo 'data: ' . json_encode($payload) . PHP_EOL . PHP_EOL;
        ob_flush();
        flush();
    }
}
