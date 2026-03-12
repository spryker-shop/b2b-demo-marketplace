<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication\Controller;

use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Demo\Zed\BackofficeAssistant\Communication\BackofficeAssistantCommunicationFactory getFactory()
 * @method \Demo\Zed\BackofficeAssistant\Business\BackofficeAssistantFacadeInterface getFacade()
 */
class ConversationHistoriesController extends AbstractController
{
    public function indexAction(): JsonResponse
    {
        $idUser = (int)$this->getFactory()->getUserFacade()->getCurrentUser()->getIdUser();
        $histories = $this->getFacade()->getConversationHistoriesByFkUser($idUser);

        return $this->jsonResponse(
            array_map(
                fn ($history) => [
                    'conversation_reference' => $history->getConversationReference(),
                    'name' => $history->getName(),
                ],
                $histories,
            ),
        );
    }

    public function detailAction(Request $request): JsonResponse
    {
        $conversationReference = (string)$request->query->get('conversationReference', '');

        if (!$conversationReference) {
            return $this->jsonResponse(['error' => 'Missing conversationReference'], 400);
        }

        $idUser = (int)$this->getFactory()->getUserFacade()->getCurrentUser()->getIdUser();

        if (!$this->getFacade()->hasConversationHistoryForUser($idUser, $conversationReference)) {
            return $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        $criteria = (new ConversationHistoryCriteriaTransfer())
            ->setConversationHistoryConditions(
                (new ConversationHistoryConditionsTransfer())->addConversationReference($conversationReference),
            );

        $collection = $this->getFactory()->getAiFoundationFacade()->getConversationHistoryCollection($criteria);
        $conversationHistories = $collection->getConversationHistories();

        if ($conversationHistories->count() === 0) {
            return $this->jsonResponse(['error' => 'Conversation not found'], 404);
        }

        return $this->jsonResponse($conversationHistories->offsetGet(0)->toArray(true, true));
    }
}
