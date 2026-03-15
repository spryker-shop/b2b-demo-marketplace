<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Zed\BackofficeAssistant\Communication\Controller;

use ArrayObject;
use Demo\Shared\BackofficeAssistant\BackofficeAssistantConstants;
use Generated\Shared\Transfer\AttachmentTransfer;
use Generated\Shared\Transfer\BackofficeAssistantConversationHistoryTransfer;
use Generated\Shared\Transfer\BackofficeAssistantPromptRequestTransfer;
use Generated\Shared\Transfer\ConversationHistoryConditionsTransfer;
use Generated\Shared\Transfer\ConversationHistoryCriteriaTransfer;
use Generated\Shared\Transfer\ConversationHistoryTransfer;
use Generated\Shared\Transfer\IntentRouterResponseTransfer;
use Generated\Shared\Transfer\PromptMessageTransfer;
use Generated\Shared\Transfer\PromptRequestTransfer;
use Spryker\Shared\AiFoundation\AiFoundationConstants;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @method \Demo\Zed\BackofficeAssistant\Communication\BackofficeAssistantCommunicationFactory getFactory()
 * @method \Demo\Zed\BackofficeAssistant\Business\BackofficeAssistantFacadeInterface getFacade()
 */
class PromptController extends AbstractController
{
    use LoggerTrait;

    protected const int MAX_FILE_SIZE_BYTES = 5242880;

    protected const int MAX_TOTAL_ATTACHMENTS_BYTES = 10485760;

    protected const int MAX_ATTACHMENT_COUNT = 5;

    /**
     * @var array<string>
     */
    protected const array ALLOWED_MEDIA_TYPES = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'text/csv',
    ];

    protected const string INTENT_ROUTER_PROMPT_TEMPLATE = '
# You are Spryker Backoffice Assistant — an intent router that selects the appropriate agent to handle user requests.

## User Context
- Current page: %s

## Available Agents
%s

## Current Agent previously selected
%s

## Instructions
1. Analyze the user\'s latest message in the context of the conversation history below.
2. Select the appropriate agent based on the user\'s intent. The intent must be very close to agent\'s responsibilities to be considered a match.
3. If the user\'s intent does not match any agent\'s responsibilities (e.g., off-topic, personal questions, coding help, non-Spryker topics), select "Guardrail" as the agent and write a brief, friendly clarification in reasoningMessage explaining what you can help with.
4. For follow-up messages, consider the conversation context and previously discussed topics to maintain continuity.

## Restrictions
- Never say about your purposes and that you are intent router

## Response Format
- agent: Exactly one of %s
- reasoningMessage: One sentence explaining your routing decision, or a clarification message if Guardrail is selected

## Conversation History
%s';

    public function indexAction(Request $request): StreamedResponse
    {
        return new StreamedResponse(function () use ($request): void {
            set_time_limit(0);
            ignore_user_abort(true);

            $data = json_decode($request->getContent(), true) ?? [];
            $prompt = $data['prompt'] ?? '';
            $context = $data['context'] ?? [];
            $attachments = $this->buildAttachmentTransfers($data['attachments'] ?? []);
            $conversationReference = $this->resolveConversationReference($prompt, $data['conversation_reference'] ?? '');
            $selectedAgent = $data['selected_agent'] ?? '';

            $this->handlePrompt($prompt, $context, $conversationReference, $attachments, $selectedAgent);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param string $prompt
     * @param array<string, mixed> $context
     * @param string $conversationReference
     * @param array<\Generated\Shared\Transfer\AttachmentTransfer> $attachments
     * @param string $selectedAgent
     *
     * @return void
     */
    protected function handlePrompt(string $prompt, array $context, string $conversationReference, array $attachments = [], string $selectedAgent = ''): void
    {
        if (!$prompt) {
            $this->emitEvent(['type' => 'error', 'message' => 'Prompt is required']);

            return;
        }

        $conversationHistory = $this->resolveConversationHistory($conversationReference);
        $previousAgent = $this->emitPreviousAgentEvent($conversationReference);

        $this->getFacade()->updateConversationUserSelectedAgent($conversationReference, $selectedAgent ?: null);

        if ($selectedAgent) {
            if ($previousAgent !== $selectedAgent) {
                $this->getFacade()->updateConversationAgent($conversationReference, $selectedAgent);
            }

            $this->emitEvent([
                'type' => 'agent_selected',
                'agent' => $selectedAgent,
                'conversation_reference' => $conversationReference,
            ]);

            $this->executeSelectedAgent($selectedAgent, $prompt, $conversationReference, $attachments);

            return;
        }

        $promptContent = $this->buildIntentRouterPrompt($conversationHistory, $prompt, $previousAgent, $context);
        $intentRouterResponse = $this->routeIntent($promptContent);

        if (!$intentRouterResponse) {
            return;
        }

        $selectedAgent = $intentRouterResponse->getAgent() ?: 'Guardrail';

        if ($selectedAgent !== 'Guardrail' && $previousAgent !== $selectedAgent) {
            $this->getFacade()->updateConversationAgent($conversationReference, $selectedAgent);
        }

        $this->emitEvent([
            'type' => 'agent_selected',
            'agent' => $selectedAgent,
            'conversation_reference' => $conversationReference,
        ]);

        if ($selectedAgent !== $previousAgent && $selectedAgent !== 'Guardrail') {
            $this->emitEvent([
                'type' => 'reasoning',
                'message' => $intentRouterResponse->getReasoningMessage(),
            ]);
        }

        if ($selectedAgent === 'Guardrail') {
            $this->emitEvent([
                'type' => 'ai_response',
                'message' => $intentRouterResponse->getReasoningMessage(),
                'conversation_reference' => $conversationReference,
            ]);

            return;
        }

        $this->executeSelectedAgent($selectedAgent, $prompt, $conversationReference, $attachments);
    }

    protected function resolveConversationHistory(string $conversationReference): ConversationHistoryTransfer
    {
        $criteria = (new ConversationHistoryCriteriaTransfer())
            ->setConversationHistoryConditions(
                (new ConversationHistoryConditionsTransfer())->addConversationReference($conversationReference),
            );

        $conversationHistories = $this->getFactory()->getAiFoundationFacade()
            ->getConversationHistoryCollection($criteria)
            ->getConversationHistories();

        if ($conversationHistories->count() > 0) {
            /** @var \Generated\Shared\Transfer\ConversationHistoryTransfer $conversationHistory */
            $conversationHistory = $conversationHistories->offsetGet(0);

            return $conversationHistory;
        }

        return new ConversationHistoryTransfer();
    }

    protected function emitPreviousAgentEvent(string $conversationReference): ?string
    {
        $previousAgent = $this->getFacade()->findAgentByConversationReference($conversationReference);

        if ($previousAgent) {
            $this->emitEvent([
                'type' => 'agent_selected',
                'agent' => $previousAgent,
                'conversation_reference' => $conversationReference,
            ]);
        }

        return $previousAgent;
    }

    /**
     * @param \Generated\Shared\Transfer\ConversationHistoryTransfer $conversationHistory
     * @param string $prompt
     * @param string|null $previousAgent
     * @param array<string, mixed> $context
     *
     * @return string
     */
    protected function buildIntentRouterPrompt(
        ConversationHistoryTransfer $conversationHistory,
        string $prompt,
        ?string $previousAgent,
        array $context,
    ): string {
        $conversationHistory->addMessage(
            (new PromptMessageTransfer())
                ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                ->setContent($prompt),
        );

        $historyLines = [];

        $messages = array_slice($conversationHistory->getMessages()->getArrayCopy(), -12);

        foreach ($messages as $message) {
            $historyLines[] = sprintf('%s: %s', ucfirst((string)$message->getType()), $message->getContent());
        }

        $formattedHistory = implode("\n", $historyLines);

        $previousAgentContext = $previousAgent
            ? sprintf('Previously selected agent: "%s". Consider whether the user\'s new message changes the intent or continues with the same agent.', $previousAgent)
            : 'No agent has been selected yet (new conversation).';

        $agentPlugins = $this->getFactory()->getBackofficeAssistantAgentPlugins();
        $agentLines = [];
        $agentNames = [];

        foreach ($agentPlugins as $agentPlugin) {
            $agentLines[] = sprintf('- "%s": %s', $agentPlugin->getName(), $agentPlugin->getDescription());
            $agentNames[] = sprintf('"%s"', $agentPlugin->getName());
        }

        $agentNames[] = '"Guardrail"';

        return sprintf(
            static::INTENT_ROUTER_PROMPT_TEMPLATE,
            $context['current_page'] ?? 'unknown',
            implode("\n", $agentLines),
            $previousAgentContext,
            implode(', ', $agentNames),
            $formattedHistory,
        );
    }

    protected function routeIntent(string $promptContent): ?IntentRouterResponseTransfer
    {
        $intentRouterRequest = (new PromptRequestTransfer())
            ->setAiConfigurationName(BackofficeAssistantConstants::AI_CONFIGURATION_INTENT_ROUTER)
            ->setStructuredMessage(new IntentRouterResponseTransfer())
            ->setMaxRetries(2)
            ->setPromptMessage(
                (new PromptMessageTransfer())
                    ->setType(AiFoundationConstants::MESSAGE_TYPE_USER)
                    ->setContent($promptContent),
            );

        $intentResponse = $this->getFactory()->getAiFoundationFacade()
            ->prompt($intentRouterRequest);

        if (!$intentResponse->getIsSuccessful()) {
            $this->emitEvent(['type' => 'error', 'message' => 'AI service unavailable']);
            $this->getLogger()->error('Intent Router error', $intentResponse->getErrors()->getArrayCopy());

            return null;
        }

        /** @var \Generated\Shared\Transfer\IntentRouterResponseTransfer */
        return $intentResponse->getStructuredMessage();
    }

    /**
     * @param string $selectedAgent
     * @param string $prompt
     * @param string $conversationReference
     * @param array<\Generated\Shared\Transfer\AttachmentTransfer> $attachments
     *
     * @return void
     */
    protected function executeSelectedAgent(string $selectedAgent, string $prompt, string $conversationReference, array $attachments = []): void
    {
        $agentRequest = (new BackofficeAssistantPromptRequestTransfer())
            ->setPrompt($prompt)
            ->setConversationReference($conversationReference)
            ->setAttachments(new ArrayObject($attachments));

        foreach ($this->getFactory()->getBackofficeAssistantAgentPlugins() as $agentPlugin) {
            if ($agentPlugin->getName() !== $selectedAgent) {
                continue;
            }

            $agentResponse = $agentPlugin->executeAgent($agentRequest);

            foreach ($agentResponse->getToolInvocations() as $toolInvocation) {
                $this->emitEvent([
                    'type' => 'tool_call',
                    'name' => $toolInvocation->getName(),
                    'arguments' => $toolInvocation->getArguments(),
                    'result' => $toolInvocation->getResult(),
                ]);
            }

            $this->emitEvent([
                'type' => 'ai_response',
                'message' => $agentResponse->getAiResponse(),
                'conversation_reference' => $conversationReference,
            ]);

            break;
        }
    }

    /**
     * @param array<int, array<string, string>> $rawAttachments
     *
     * @return array<\Generated\Shared\Transfer\AttachmentTransfer>
     */
    protected function buildAttachmentTransfers(array $rawAttachments): array
    {
        if (!$rawAttachments) {
            return [];
        }

        $attachments = [];
        $totalSize = 0;
        $count = 0;

        foreach ($rawAttachments as $rawAttachment) {
            if ($count >= static::MAX_ATTACHMENT_COUNT) {
                $this->getLogger()->warning('Maximum attachment count exceeded, skipping remaining files.');

                break;
            }

            $mediaType = $rawAttachment['mediaType'] ?? '';
            $content = $rawAttachment['content'] ?? '';

            if (!in_array($mediaType, static::ALLOWED_MEDIA_TYPES, true)) {
                $this->getLogger()->warning(sprintf('Rejected attachment with unsupported media type: %s', $mediaType));

                continue;
            }

            if (!$content) {
                continue;
            }

            $decodedContent = base64_decode($content, true);

            if ($decodedContent === false) {
                $this->getLogger()->warning('Rejected attachment with invalid base64 content.');

                continue;
            }

            $fileSize = strlen($decodedContent);

            if ($fileSize > static::MAX_FILE_SIZE_BYTES) {
                $this->getLogger()->warning(sprintf('Rejected attachment exceeding max file size: %d bytes.', $fileSize));

                continue;
            }

            $totalSize += $fileSize;

            if ($totalSize > static::MAX_TOTAL_ATTACHMENTS_BYTES) {
                $this->getLogger()->warning('Total attachments size exceeded, skipping remaining files.');

                break;
            }

            $attachments[] = (new AttachmentTransfer())
                ->setType($this->resolveAttachmentType($mediaType))
                ->setContent($content)
                ->setContentType(AiFoundationConstants::ATTACHMENT_CONTENT_TYPE_BASE64)
                ->setMediaType($mediaType);

            $count++;
        }

        return $attachments;
    }

    protected function resolveAttachmentType(string $mediaType): string
    {
        if (str_starts_with($mediaType, 'image/')) {
            return AiFoundationConstants::ATTACHMENT_TYPE_IMAGE;
        }

        return AiFoundationConstants::ATTACHMENT_TYPE_DOCUMENT;
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

        return (string)$this->getFacade()
            ->createConversationHistory(
                (new BackofficeAssistantConversationHistoryTransfer())
                    ->setFkUser($idUser)
                    ->setName(mb_substr($prompt, 0, 150)),
            )
            ->getConversationReference();
    }

    /**
     * @param array<mixed> $payload
     *
     * @return void
     */
    protected function emitEvent(array $payload): void
    {
        echo 'data: ' . json_encode($payload) . PHP_EOL . PHP_EOL;
        ob_flush();
        flush();
    }
}
