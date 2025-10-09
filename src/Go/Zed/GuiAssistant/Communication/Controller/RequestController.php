<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Go\Zed\GuiAssistant\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Go\Zed\GuiAssistant\Business\GuiAssistantFacade getFacade()
 */
class RequestController extends AbstractController
{
    public function indexAction(Request $request): array
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
            $queryParams = json_decode($formData['queryParams'] ?: '{}', true);
            $pathParams = json_decode($formData['pathParams'] ?: '{}', true);
            $payload = json_decode($formData['payload'] ?: '{}', true);
            $result = $this->getFacade()->routeEndpoint(
                $httpMethod,
                $schemaPath,
                $queryParams,
                $pathParams,
                $payload,
            );
        }

        return $this->viewResponse([
            'result' => $result,
            'formData' => $formData,
        ]);
    }
}
