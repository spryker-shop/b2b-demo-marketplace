<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Pyz\Yves\Configurator\WaterTreatmentConfigurator;

use Generated\Shared\Transfer\ProductConfiguratorPageResponseTransfer;
use Spryker\ChecksumGenerator\Checksum\CrcOpenSslChecksumGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ConfiguratorPage
{
    /**
     * @var string
     */
    protected const REQUEST_PARAMETER_TOKEN = 'token';

    /**
     * @var string
     */
    protected const REQUEST_PARAMETER_GET_CONFIGURATION_BY_TOKEN = 'getConfigurationByToken';

    /**
     * @var string
     */
    protected const REQUEST_PARAMETER_PREPARER_CONFIGURATION = 'prepareConfiguration';

    /**
     * @var string
     */
    protected const CONFIGURATOR_SESSION_KEY = 'CONFIGURATOR_SESSION_KEY';

    /**
     * @var string
     */
    protected const MESSAGE_CANNOT_START_SESSION = 'Can\'t start session.';

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $session;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    public function __construct()
    {
        $this->session = new Session();
        $this->request = Request::createFromGlobals();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|string
     */
    public function render()
    {
        if ($this->request->isMethod(Request::METHOD_GET) && $this->request->query->has(static::REQUEST_PARAMETER_GET_CONFIGURATION_BY_TOKEN)) {
            return $this->getRequestDataByTokenAction();
        }

        if ($this->request->isMethod(Request::METHOD_POST) && $this->request->query->has(static::REQUEST_PARAMETER_PREPARER_CONFIGURATION)) {
            return $this->prepareConfigurationResponseAction();
        }

        if ($this->request->isMethod(Request::METHOD_POST)) {
            return $this->createTokenAction();
        }

        return $this->renderHtmlPageAction();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function createTokenAction(): Response
    {
        $productConfiguratorPageResponseTransfer = new ProductConfiguratorPageResponseTransfer();

        if (!$this->session->start()) {
            $productConfiguratorPageResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage(static::MESSAGE_CANNOT_START_SESSION);

            return new JsonResponse($productConfiguratorPageResponseTransfer->toArray());
        }

        $this->session->set(
            static::CONFIGURATOR_SESSION_KEY,
            json_decode($this->request->getContent(), true) ?? [],
        );

        $productConfiguratorPageResponseTransfer
            ->setIsSuccessful(true)
            ->setConfiguratorRedirectUrl($this->createConfiguratorRedirectUrl());

        return new JsonResponse($productConfiguratorPageResponseTransfer->toArray(), Response::HTTP_OK);
    }

    /**
     * @return string
     */
    protected function renderHtmlPageAction(): string
    {
        return file_get_contents(
            __DIR__ . DIRECTORY_SEPARATOR . 'Theme' . DIRECTORY_SEPARATOR . 'index.html',
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getRequestDataByTokenAction(): Response
    {
        return new JsonResponse(
            ['data' => $this->getDataFromSession()],
            Response::HTTP_OK,
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function prepareConfigurationResponseAction(): Response
    {
        $productConfiguration = $this->request->request->all() ?: [];
        $checkSum = (new CrcOpenSslChecksumGenerator(
            getenv('SPRYKER_PRODUCT_CONFIGURATOR_HEX_INITIALIZATION_VECTOR') ?: '',
        ))->generateChecksum(
            $productConfiguration,
            getenv('SPRYKER_PRODUCT_CONFIGURATOR_ENCRYPTION_KEY') ?: '',
        );

        return new JsonResponse(
            array_merge($productConfiguration, ['checkSum' => $checkSum, 'timestamp' => time()]),
            Response::HTTP_OK,
        );
    }

    /**
     * @return array
     */
    protected function getDataFromSession(): array
    {
        $this->session->setId($this->request->get(static::REQUEST_PARAMETER_GET_CONFIGURATION_BY_TOKEN));
        $this->session->start();

        return $this->session->get(static::CONFIGURATOR_SESSION_KEY, []);
    }

    /**
     * @return string
     */
    protected function createConfiguratorRedirectUrl(): string
    {
        return sprintf(
            '%s://%s?token=%s',
            getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_PORT') === '443' ? 'https' : 'http',
            getenv('SPRYKER_WATER_TREATMENT_CONFIGURATOR_HOST') ?: '',
            htmlspecialchars($this->session->getId()),
        );
    }
}
