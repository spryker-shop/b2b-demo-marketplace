<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PlmbTest\Shared\Example\Example;

use PlmbTest\Shared\Example\ExampleTester;

/**
 * Smoke test: proves the custom-namespace test infrastructure is wired and runnable.
 *
 * It asserts nothing about the project on purpose - see ProjectNamespaceCest for assertions
 * that can actually fail. Keep this one; a green run here isolates "the harness works" from
 * "the code is correct" when a suite starts erroring.
 */
class ExampleCest
{
    /**
     * @param \PlmbTest\Shared\Example\ExampleTester $i
     *
     * @return void
     */
    public function testCustomNamespaceTestInfrastructureIsRunnable(ExampleTester $i): void
    {
        $i->assertTrue(true);
    }
}
