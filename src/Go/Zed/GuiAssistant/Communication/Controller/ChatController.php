<?php
namespace Go\Zed\GuiAssistant\Communication\Controller;

use Go\Client\OpenAi\Reader\ModelResponse;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \Go\Zed\GuiAssistant\Communication\GuiAssistantCommunicationFactory getFactory()
 */
class ChatController extends AbstractController
{
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

        $aiResponse = $this->getFactory()->getOpenAiClient()->createResponseForAgent($messages);

        $response = [
            'answer' => $aiResponse[ModelResponse::OPEN_AI_RESULT_SIMPLE_TEXT] ?? 'No answer',
        ];

        return $this->jsonResponse($response);
    }

    protected function mapMessagesToAi(Request $request): array {
        if ($request->getMethod() === 'GET' && $request->query->has('messages')) {
            $data = ['messages' => json_decode($request->query->get('messages'), true)];
        } else {
            $data = json_decode($request->getContent(), true);
        }

        $messages = [];
        $inputMessages = array_slice($data['messages'] ?? [], -static::MAX_MESSAGES);

        foreach ($inputMessages as $message) {

            switch($message['role'] ?? '') {
                case 'image':
                    $messages[] = ['role' => 'user', 'content' => [['type' => 'input_image', 'image_url' => trim($message['content']) ?? '']]];
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

}
