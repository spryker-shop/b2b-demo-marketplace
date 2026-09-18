<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\EventSubscriber;

use Demo\Glue\McpCommerce\Controller\TokenController;
use Demo\Shared\McpCommerce\McpCommerceConstants;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Claims `POST /token` for the MCP authorization-code grant before the Storefront API does.
 *
 * `Spryker\Glue\AuthRestApi\Api\Storefront\EventSubscriber\TokenRequestSubscriber` already answers
 * **every** `POST /token` from a `KernelEvents::REQUEST` listener at priority 100, above the router.
 * A route for the MCP token endpoint can therefore never be reached: the MCP client would receive the
 * shop password-grant's `invalid_grant` error instead of the MCP one. Subclassing that service does
 * not help either, because the API Platform auto-discovery compiler pass re-registers the core class
 * under its own service id and wins.
 *
 * Running one priority higher and answering only `grant_type=authorization_code` solves it: setting a
 * response on the event stops propagation, so the core subscriber never sees an MCP token request,
 * while every other grant type reaches it untouched and existing consumers are unaffected.
 */
class McpTokenRequestSubscriber implements EventSubscriberInterface
{
    /**
     * One above `TokenRequestSubscriber::PRIORITY_BEFORE_ROUTER`, which is 100.
     *
     * @var int
     */
    protected const PRIORITY_BEFORE_STOREFRONT_TOKEN_SUBSCRIBER = 101;

    /**
     * @var string
     */
    protected const PARAMETER_GRANT_TYPE = 'grant_type';

    /**
     * @var string
     */
    protected const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';

    public function __construct(
        protected readonly TokenController $tokenController,
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', static::PRIORITY_BEFORE_STOREFRONT_TOKEN_SUBSCRIBER],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->isMcpTokenRequest($request)) {
            return;
        }

        $event->setResponse(($this->tokenController)($request));
    }

    protected function isMcpTokenRequest(Request $request): bool
    {
        if ($request->getMethod() !== Request::METHOD_POST) {
            return false;
        }

        if (rtrim($request->getPathInfo(), '/') !== McpCommerceConstants::PATH_TOKEN) {
            return false;
        }

        return $request->request->get(static::PARAMETER_GRANT_TYPE) === static::GRANT_TYPE_AUTHORIZATION_CODE;
    }
}
