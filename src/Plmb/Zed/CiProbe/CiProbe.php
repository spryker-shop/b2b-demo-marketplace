<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Plmb\Zed\CiProbe;

use Plmb\Zed\Stock\StockConfig;

/**
 * ============================================================================
 * DELIBERATELY BROKEN - CI PROBE. DELETE THIS FILE.
 * ============================================================================
 *
 * This class exists ONLY to prove that the static-analysis jobs in
 * .github/workflows/ci.yml actually fail the pipeline. It is not real code and
 * nothing references it.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS IS PROVEN ONE GATE PER RUN
 * ---------------------------------------------------------------------------
 * The job runs its 20 steps in order and STOPS AT THE FIRST FAILURE. So a single
 * red run only ever proves the FIRST gate it reaches; everything behind it never
 * executes. Proving four gates therefore takes four runs, each one clean up to
 * the gate under test:
 *
 * - step 12 "Run CodeStyle checks" = vendor/bin/console code:sniff:style
 * - step 13 "Run Architecture rules" = phpmd with the architecture-sniffer ruleset
 * - step 14 "Run Project Architecture rules" = phpmd with the project phpmd.xml
 * - step 17 "Run PHPStan" = phpstan analyze -l 6
 *
 * PROVEN SO FAR (all observed red on GitHub, this file the only one flagged):
 *
 * - stage 1, step 12, CS, run 30548532240: 45 errors affecting 38 lines
 * - stage 2, step 17, PHPStan, run 30549369253: 4 errors (listed below)
 * - stage 3, step 14, phpmd: InstanceResolvingRule, 1 violation (CURRENT)
 *
 * STILL UNPROVEN: step 13 (architecture-sniffer, priority 2) has only ever been
 * observed PASSING. Passing is not evidence that it gates.
 *
 * ---------------------------------------------------------------------------
 * CURRENT STAGE: 3 - fails at step 14, phpmd `phpmd.xml`.
 * ---------------------------------------------------------------------------
 * instantiatesAResolvableClassDirectly() trips InstanceResolvingRule. Steps 12
 * and 13 are deliberately CLEAN so the failure lands exactly on 14 (verified
 * locally: phpcs exit 0, architecture-sniffer exit 0, phpmd.xml 1 violation).
 *
 * Since 14 precedes 17, the PHPStan errors below are NOT reached in this stage.
 * That is fine - they were already proven in stage 2:
 *
 * - method.notFound: calls a method that does not exist
 * - return.type: declared int, returns string
 * - argument.type: passes string where int is declared
 * - missingType.iterableValue: `@return array` with no value type
 *
 * TO MOVE ON: delete instantiatesAResolvableClassDirectly() (and the now-unused
 * StockConfig import) and the failure hands back to PHPStan at step 17.
 *
 * NOTE ON WHAT CANNOT BE PROVEN HERE: the `missingType.parameter` and
 * `missingType.return` errors from stage 1 are gone by construction. Spryker CS
 * REQUIRES `@param`/`@return` doc blocks on every method, and those annotations
 * are exactly what PHPStan reads to infer the types - so a CS-clean file cannot
 * carry a missing-type error. The two checks genuinely overlap there; CS already
 * covers it.
 *
 * `php -l` still passes - the file is syntactically valid on purpose, so the
 * failure comes from the analyser rather than a parse error.
 *
 * WHEN ALL GATES ARE PROVEN: delete src/Plmb/Zed/CiProbe/ and confirm the whole
 * job goes green.
 */
class CiProbe
{
    /**
     * phpmd `phpmd.xml` InstanceResolvingRule (step 14, "Run Project Architecture rules"):
     * an automatically-resolved Spryker class must not be instantiated with `new` -
     * it should come from a Dependency Provider / resolver.
     *
     * STAGE 3 ONLY. This is the method that proves step 14 gates. Because step 14
     * runs BEFORE PHPStan (step 17), this method makes the job fail at 14 and the
     * PHPStan errors below are never reached - that is expected: PHPStan was already
     * proven red in run 30549369253 with all 4 errors.
     *
     * Delete this ONE method to hand the failure back to PHPStan.
     *
     * @return \Plmb\Zed\Stock\StockConfig
     */
    public function instantiatesAResolvableClassDirectly(): StockConfig
    {
        return new StockConfig();
    }

    /**
     * PHPStan L6: calls a method that does not exist on this class.
     *
     * Deliberately on `$this` rather than on a collaborator - see
     * instantiatesAResolvableClassDirectly() above for why a `new` here would
     * short-circuit the job at step 14 instead.
     *
     * @return string
     */
    public function callsAMethodThatDoesNotExist(): string
    {
        return $this->thisMethodDoesNotExistAnywhere();
    }

    /**
     * PHPStan L6: declared to return int, actually returns a string.
     *
     * @return int
     */
    public function returnsTheWrongType(): int
    {
        return 'definitely not an int';
    }

    /**
     * PHPStan L6: passes a string into a parameter declared as int.
     *
     * @return string
     */
    public function passesTheWrongArgumentType(): string
    {
        return $this->formatNumber('not an int');
    }

    /**
     * PHPStan L6: array return type with no value type specified.
     *
     * @return array
     */
    public function untypedArray(): array
    {
        return [1, 'two', 3.0];
    }

    /**
     * @param int $number
     *
     * @return string
     */
    protected function formatNumber(int $number): string
    {
        return (string)$number;
    }
}
