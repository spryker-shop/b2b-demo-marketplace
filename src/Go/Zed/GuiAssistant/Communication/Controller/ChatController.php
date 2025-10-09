<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Communication\Controller;

use Exception;
use Go\Client\OpenAi\Reader\ModelResponse;
use Go\Zed\GuiAssistant\Business\GuiAssistantFacade;
use GuzzleHttp\Exception\TransferException;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * @method \Go\Zed\GuiAssistant\Communication\GuiAssistantCommunicationFactory getFactory()
 * @method \Go\Zed\GuiAssistant\Business\GuiAssistantFacade getFacade()
 */
class ChatController extends AbstractController
{
    use LoggerTrait;

    protected const MAX_MESSAGES = 50;

    protected const MAX_CONTENT_LENGTH = 5000;

    /**
     * Expects a JSON payload with the following structure:
     *  [
     *    { "role": "user"|"assistant", "content": "string (max length)" },
     *    ...
     *  ]
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Containing the conversation history as 'messages'.
     *
     * @return \Symfony\Component\HttpFoundation\Response JSON response with the answer { 'answer' => string }.
     */
    public function sendAction(Request $request): Response
    {
        $messages = $this->mapMessagesToAi($request);
        $messages = $this->rag($messages, $this->ragAddStoreInfo());
        $messages = $this->rag($messages, $this->ragAddOrderSchema($messages));

        try {
            $aiResponse = $this->getFactory()->getOpenAiClient()->createResponseForAgent($messages);

            $response = [
                'answer' => !empty($aiResponse) ? $aiResponse : 'No answer',
            ];
        } catch (TransferException $e) {
            $this->getLogger()->error('OpenAI API connection error: ' . $e->getMessage());
            $response = ['error' => 'Connection error'];
        } catch (Exception $e) {
            $this->getLogger()->error('OpenAI API unexpected error: ' . $e->getMessage());
            $response = ['error' => 'Unexpected error'];
        }

        return $this->jsonResponse($response);
    }

    protected function rag(array $messages, array $insert): array
    {
        if (count($insert) < 1) {
            return $messages;
        }

        $rolesOnMessages = array_map(fn ($m) => $m['role'] ?? '', $messages);
        $lastAssistantMessageIndex = array_search('assistant', array_reverse($rolesOnMessages), true);
        $lastAssistantMessageIndex = $lastAssistantMessageIndex === false ? 0 : (count($rolesOnMessages) - $lastAssistantMessageIndex);

        array_splice($messages, $lastAssistantMessageIndex, 0, $insert);

        return $messages;
    }

    protected function mapMessagesToAi(Request $request): array
    {
        if ($request->getMethod() === 'GET' && $request->query->has('messages')) {
            $data = ['messages' => json_decode($request->query->get('messages'), true)];
        } else {
            $data = json_decode($request->getContent(), true);
        }

        $messages = [];
        $inputMessages = array_slice($data['messages'] ?? [], -static::MAX_MESSAGES);

        foreach ($inputMessages as $message) {
            switch ($message['role'] ?? '') {
                case 'image':
                    $messages[] = ['role' => 'user', 'content' => [['type' => 'input_image', 'image_url' => trim($message['content']) ?? '']]];

                    break;
                case 'pdf':
                    $messages[] = ['role' => 'user', 'content' => [['type' => 'input_file', 'filename' => 'uploaded.pdf', 'file_data' => trim($message['content']) ?? '']]];

                    break;
                case 'txt':
                    $messages[] = ['role' => 'user', 'content' => "UPLOADED FILE:\n\n" . (trim($message['content']) ?? ''), 'type' => 'message'];

                    break;
                case 'assistant':
                    $content = mb_substr($message['content'] ?? '', 0, static::MAX_CONTENT_LENGTH);

                    $messages[] = ['role' => 'assistant', 'content' => $content, 'type' => 'message'];

                    break;
                case 'user':
                    $content = mb_substr($message['content'] ?? '', 0, static::MAX_CONTENT_LENGTH);
                    $messages[] = ['role' => 'user', 'content' => $content, 'type' => 'message'];

                    break;
                default:
            }
        }

        return $messages;
    }

    protected function ragAddStoreInfo(): array
    {
        $storeInfo = $this->getFacade()->getStores('GET', '/stores', [], [], []);

        $content = "RAG - Store Information retrieve from GET /stores):\n\n";
        foreach ($storeInfo['result'] ?? [] as $num => $store) {
            $content .= "$num. Store:\n";
            foreach ($store as $key => $value) {
                $content .= '- ' . $key . ': ' . (is_array($value) ? implode(', ', $value) : $value) . "\n";
            }
        }

        $message = [
            'role' => 'assistant',
            'type' => 'message',
            'content' => $content,
        ];

        return [$message];
    }

    protected function ragAddOrderSchema(array $messages): array
    {
        $hasFileUpload = false;
        foreach ($messages as $message) {
            if (!(is_array($message['content'] ?? null)) && !(str_starts_with($message['content'] ?? '', 'UPLOADED FILE:'))) {
                continue;
            }

            $hasFileUpload = true;
        }
        if (!$hasFileUpload) {
            return [];
        }

        $content = "RAG - POST /orders endpoint OpenAPI Schema:\n\n";

        $yaml = Yaml::parseFile(GuiAssistantFacade::OPENAPI_LOCATION);
        $ordersNode = $yaml['paths']['/orders'] ?? null;

        $message = [
            'role' => 'assistant',
            'type' => 'message',
            'content' => $content . Yaml::dump($ordersNode, 12, 2),
        ];

        return [$message];
    }
}
