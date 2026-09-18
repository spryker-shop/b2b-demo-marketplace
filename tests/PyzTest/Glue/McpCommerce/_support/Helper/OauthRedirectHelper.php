<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Glue\McpCommerce\Helper;

use Codeception\Module;
use Codeception\Module\PhpBrowser;
use Throwable;

/**
 * Gives the OAuth tests access to the URL the browser actually landed on.
 *
 * Redirect following cannot be disabled per request in this suite: `GlueRest::prepareHeaders()` calls
 * `startFollowingRedirects()` before every single call, so `stopFollowingRedirects()` in a test is
 * always undone. The authorization endpoint's 302 is therefore followed automatically, and the `code`
 * and `state` the assertions need live in the query string of the landing URL rather than in a
 * readable `Location` header. `PhpBrowser::_getCurrentUri()` is the public accessor for that URL, but
 * it is not part of the generated actor, so this helper exposes it.
 */
class OauthRedirectHelper extends Module
{
    /**
     * Returns the URI the browser currently sits on, including its query string.
     *
     * @return string
     */
    public function grabCurrentUri(): string
    {
        try {
            return $this->getPhpBrowserModule()->_getCurrentUri();
        } catch (Throwable $throwable) {
            // No navigable URI exists when the request was answered directly rather than redirected —
            // exactly the case the negative authorization tests exercise. An empty string lets the
            // caller conclude "no redirect happened" instead of erroring the test.
            return '';
        }
    }

    /**
     * @return \Codeception\Module\PhpBrowser
     */
    protected function getPhpBrowserModule(): PhpBrowser
    {
        /** @var \Codeception\Module\PhpBrowser $phpBrowserModule */
        $phpBrowserModule = $this->getModule('PhpBrowser');

        return $phpBrowserModule;
    }
}
