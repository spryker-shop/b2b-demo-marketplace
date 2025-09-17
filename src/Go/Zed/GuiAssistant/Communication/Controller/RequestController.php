<?php
namespace Go\Zed\GuiAssistant\Communication\Controller;

use Go\Zed\GuiAssistant\Business\GuiAssistantFacade;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method GuiAssistantFacade getFacade()
 */
class RequestController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $result = null;

        $formData = [
            'httpMethod' => $request->request->get('httpMethod', ''),
            'schemaPath' => $request->request->get('schemaPath', ''),
            'queryParams' => $request->request->get('queryParams', '{}'),
            'pathParams' => $request->request->get('pathParams', '{}'),
            'payload' => $request->request->get('payload', '{}'),
        ];

        if ($request->getMethod() === 'POST') {
            $httpMethod = $formData['httpMethod'];
            $schemaPath = $formData['schemaPath'];
            $queryParams = json_decode($formData['queryParams'] ?: '{}', true) ?: [];
            $pathParams = json_decode($formData['pathParams'] ?: '{}', true) ?: [];
            $payload = json_decode($formData['payload'] ?: '{}', true) ?: [];
            $result = $this->getFacade()->routeEndpoint(
                $httpMethod,
                $schemaPath,
                $queryParams,
                $pathParams,
                $payload
            );
        }

        return $this->viewResponse([
            'result' => $result,
            'formData' => $formData,
        ]);
    }
}
