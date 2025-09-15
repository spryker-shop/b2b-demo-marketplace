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

    protected const MAX_CONTENT_LENGTH = 500;

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
//        // Hack to get kernel instance
//        $kernel = null;
//
//        // Try global kernel first
//        if (isset($GLOBALS['kernel'])) {
//            $kernel = $GLOBALS['kernel'];
//        }
//
//        // Try to get from application container
//        if (!$kernel && class_exists('\Spryker\Shared\Kernel\Container\GlobalContainer')) {
//            try {
//                $container = new \Spryker\Shared\Kernel\Container\GlobalContainer();
//                if ($container->has('kernel')) {
//                    $kernel = $container->get('kernel');
//                }
//            } catch (\Exception $e) {
//                // Ignore
//            }
//        }
//
//        // Try to get from request attributes (sometimes set by framework)
//        if (!$kernel && $request->attributes->has('kernel')) {
//            $kernel = $request->attributes->get('kernel');
//        }
//
//        // Last resort: try to get from $_SERVER
//        if (!$kernel && isset($_SERVER['KERNEL'])) {
//            $kernel = $_SERVER['KERNEL'];
//        }
//
//        if (!$kernel) {
//            return $this->jsonResponse(['error' => 'Kernel instance not available']);
//        }

        $tools = [
           // [ "type" => "web_search_2025_08_26" ],
//            [
//                "type"=> "function",
//                "name"=> "create_product",
//                "description"=> "Assist and execute on a new Product creation in the Spryker Catalog",
//                "parameters"=> [
//                        "type"=> "object",
//                        "properties"=> [
//                            "location"=> [
//                                "type"=> "string",
//                                "description"=> "The city and state, e.g. San Francisco, CA",
//                            ],
//                            "unit"=> [
//                                "type"=> "string",
//                                "enum" => ["celsius", "fahrenheit"]
//                            ],
//                        ],
//                        "required"=> ["location", "unit"],
//                ]
//            ],
        ];

       $messages = $this->mapMessagesToAi($request);
//
//        $agent = new OpenApiAgent(new SymfonyKernelToolExecutor($kernel));
//
//        [$answer] = $agent->converse(end($messages)['content'] ?? '');
//
//        return $this->jsonResponse(['answer' => $answer]);
//

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
        $allowedRoles = ['user', 'assistant'];
        $inputMessages = array_slice($data['messages'] ?? [], -static::MAX_MESSAGES);

        foreach ($inputMessages as $message) {
            $role = $message['role'] ?? 'user';
            $role = in_array($role, $allowedRoles) ? $role : 'user';
            $content = mb_substr($message['content'] ?? '', 0, static::MAX_CONTENT_LENGTH);
            $messages[] = ['role' => $role, 'content' => $content];
        }

        return $messages;
    }

}
