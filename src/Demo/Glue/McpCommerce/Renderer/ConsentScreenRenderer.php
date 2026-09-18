<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Demo\Glue\McpCommerce\Renderer;

use Demo\Shared\McpCommerce\McpCommerceConstants;

/**
 * Renders the `/authorize` login and consent screen as self-contained HTML.
 *
 * The Storefront API application registers `TwigBundle` but configures no template path, so adding a
 * Twig template here would mean adding template-path configuration to a shared config file for a
 * single two-field form. Emitting the markup directly keeps the change contained; the trade-off is
 * revisited if the screen grows beyond login plus approval.
 *
 * Every interpolated value passes through `htmlspecialchars` because the authorization request
 * parameters are attacker-controlled query input that is echoed back into hidden form fields.
 */
class ConsentScreenRenderer implements ConsentScreenRendererInterface
{
    /**
     * @var string
     */
    protected const FIELD_EMAIL = 'email';

    /**
     * @var string
     */
    protected const FIELD_PASSWORD = 'password';

    /**
     * @var string
     */
    protected const FIELD_APPROVE = 'approve';

    /**
     * @var string
     */
    protected const APPROVE_VALUE_YES = 'yes';

    /**
     * @var string
     */
    protected const PAGE_TITLE = 'Authorize application access';

    /**
     * @var string
     */
    protected const STYLES = 'body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;'
        . 'background:#f4f5f7;margin:0;padding:2.5rem 1rem;color:#1c1f23}'
        . 'main{max-width:26rem;margin:0 auto;background:#fff;border-radius:.5rem;'
        . 'box-shadow:0 1px 3px rgba(0,0,0,.12);padding:1.75rem}'
        . 'h1{font-size:1.25rem;margin:0 0 .5rem}p{margin:0 0 1.25rem;line-height:1.5;color:#4a5057}'
        . 'label{display:block;font-size:.8125rem;font-weight:600;margin-bottom:.25rem}'
        . 'input[type=email],input[type=password]{width:100%;box-sizing:border-box;padding:.5rem .625rem;'
        . 'margin-bottom:1rem;border:1px solid #c7ccd1;border-radius:.25rem;font-size:.9375rem}'
        . 'button{width:100%;padding:.625rem;border:0;border-radius:.25rem;background:#1b6ac9;color:#fff;'
        . 'font-size:.9375rem;font-weight:600;cursor:pointer}button:hover{background:#155aab}'
        . '.error{background:#fdecec;border:1px solid #f3b7b7;color:#8c1c1c;padding:.625rem .75rem;'
        . 'border-radius:.25rem;margin-bottom:1.25rem;font-size:.875rem}'
        . '.scope{background:#f4f5f7;border-radius:.25rem;padding:.75rem;margin-bottom:1.25rem;font-size:.875rem}'
        . '.scope ul{margin:.5rem 0 0;padding-left:1.25rem}';

    /**
     * @param string $clientName
     * @param array<string, string> $authorizationRequestParameters
     * @param string|null $errorMessage
     *
     * @return string
     */
    public function renderConsentScreen(
        string $clientName,
        array $authorizationRequestParameters,
        ?string $errorMessage = null,
    ): string {
        $body = sprintf(
            '<h1>%s wants to access your account</h1>'
            . '<p>Sign in with your shop credentials to let this application search the catalogue, '
            . 'manage your cart and place orders on your behalf.</p>'
            . '%s'
            . '<div class="scope"><strong>Requested access</strong><ul>'
            . '<li>Search products and read product details</li>'
            . '<li>Add items to your cart</li>'
            . '<li>Place orders and read your order history</li>'
            . '</ul></div>'
            . '<form method="post" action="%s">%s'
            . '<label for="mcp-email">Email</label>'
            . '<input id="mcp-email" type="email" name="%s" autocomplete="username" required>'
            . '<label for="mcp-password">Password</label>'
            . '<input id="mcp-password" type="password" name="%s" autocomplete="current-password" required>'
            . '<input type="hidden" name="%s" value="%s">'
            . '<button type="submit">Sign in and approve</button>'
            . '</form>',
            $this->escape($clientName),
            $this->renderErrorMessage($errorMessage),
            $this->escape(McpCommerceConstants::PATH_AUTHORIZE),
            $this->renderHiddenFields($authorizationRequestParameters),
            $this->escape(static::FIELD_EMAIL),
            $this->escape(static::FIELD_PASSWORD),
            $this->escape(static::FIELD_APPROVE),
            $this->escape(static::APPROVE_VALUE_YES),
        );

        return $this->renderPage($body);
    }

    public function renderErrorScreen(string $errorMessage): string
    {
        return $this->renderPage(
            sprintf(
                '<h1>Authorization request cannot be completed</h1><div class="error">%s</div>',
                $this->escape($errorMessage),
            ),
        );
    }

    protected function renderPage(string $body): string
    {
        return sprintf(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>%s</title><style>%s</style></head><body><main>%s</main></body></html>',
            $this->escape(static::PAGE_TITLE),
            static::STYLES,
            $body,
        );
    }

    protected function renderErrorMessage(?string $errorMessage): string
    {
        if ($errorMessage === null || $errorMessage === '') {
            return '';
        }

        return sprintf('<div class="error">%s</div>', $this->escape($errorMessage));
    }

    /**
     * @param array<string, string> $authorizationRequestParameters
     */
    protected function renderHiddenFields(array $authorizationRequestParameters): string
    {
        $hiddenFields = '';

        foreach ($authorizationRequestParameters as $parameterName => $parameterValue) {
            $hiddenFields .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                $this->escape($parameterName),
                $this->escape($parameterValue),
            );
        }

        return $hiddenFields;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
