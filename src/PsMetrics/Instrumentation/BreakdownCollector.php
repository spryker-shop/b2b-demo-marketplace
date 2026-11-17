<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation;

/**
 * Splits the time of an open span (a task run, a listener call) into database, key-value store,
 * search and broker time.
 *
 * A call is attributed to the innermost open span only. When a span closes, its totals are added to
 * the span around it, so a parent includes its children's time without any call ever walking the
 * stack. The per-call path therefore touches four integer slots and nothing else: the database hook
 * fires once per query, and anything heavier would distort the very spans it measures.
 *
 * Process-global for the same reason as the other collectors: hook callbacks have no constructor and
 * no container, so the hooks that time the calls and the hooks that open the spans can only meet
 * through a shared instance.
 */
class BreakdownCollector
{
    /**
     * @var int
     */
    public const int CATEGORY_DB = 0;

    /**
     * @var int
     */
    public const int CATEGORY_KV = 1;

    /**
     * @var int
     */
    public const int CATEGORY_SEARCH = 2;

    /**
     * @var int
     */
    public const int CATEGORY_BROKER = 3;

    /**
     * Record field names per category, in C3 order: milliseconds first, then calls.
     *
     * @var array<int, array{string, string}>
     */
    protected const array CATEGORY_FIELDS = [
        self::CATEGORY_DB => ['db_ms', 'db_calls'],
        self::CATEGORY_KV => ['kv_ms', 'kv_calls'],
        self::CATEGORY_SEARCH => ['search_ms', 'search_calls'],
        self::CATEGORY_BROKER => ['broker_ms', 'broker_calls'],
    ];

    /**
     * @var float
     */
    protected const float NANOSECONDS_PER_MILLISECOND = 1e6;

    protected static ?self $instance = null;

    protected bool $isEnabled = false;

    /**
     * @var array<int, bool>
     */
    protected array $installedCategories = [false, false, false, false];

    /**
     * Totals of the innermost open span, indexed by category.
     *
     * @var array<int, int>
     */
    protected array $nanoseconds = [0, 0, 0, 0];

    /**
     * @var array<int, int>
     */
    protected array $calls = [0, 0, 0, 0];

    /**
     * Start of the outermost in-flight call per category. A client that calls itself again, such as
     * Elastica retrying a request on the next connection, is one call, not two overlapping ones.
     *
     * @var array<int, int>
     */
    protected array $callStartedAt = [0, 0, 0, 0];

    /**
     * @var array<int, int>
     */
    protected array $callDepth = [0, 0, 0, 0];

    /**
     * The totals of each enclosing span, saved while a child span is open.
     *
     * @var array<int, array{array<int, int>, array<int, int>}>
     */
    protected array $parentTotals = [];

    protected int $spanDepth = 0;

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Exists so a test can assert against a clean collector; nothing in the runtime path calls it.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$instance = null;
    }

    /**
     * Sums two breakdowns field by field, for records that aggregate several spans such as a listener
     * group. A null breakdown adds nothing, and a null field stays null: a category that was not
     * measured in one span cannot be summed into a measured total.
     *
     * @param array<string, float|int|null>|null $total
     * @param array<string, float|int|null>|null $addition
     *
     * @return array<string, float|int|null>|null
     */
    public static function merge(?array $total, ?array $addition): ?array
    {
        if ($addition === null) {
            return $total;
        }

        if ($total === null) {
            return $addition;
        }

        foreach ($total as $field => $value) {
            $total[$field] = $value === null || !isset($addition[$field]) ? null : $value + $addition[$field];
        }

        return $total;
    }

    /**
     * @return void
     */
    public function enable(): void
    {
        $this->isEnabled = true;
    }

    /**
     * @param int $category
     *
     * @return void
     */
    public function markInstalled(int $category): void
    {
        $this->installedCategories[$category] = true;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @param int $category
     *
     * @return bool
     */
    public function isInstalled(int $category): bool
    {
        return $this->installedCategories[$category] ?? false;
    }

    /**
     * Opens a span and returns its token, or null when the breakdown is not enabled. The token goes
     * back into closeSpan(), which then returns null as well, and that null is the record's field.
     *
     * @return int|null
     */
    public function openSpan(): ?int
    {
        if (!$this->isEnabled) {
            return null;
        }

        $this->parentTotals[] = [$this->nanoseconds, $this->calls];
        $this->nanoseconds = [0, 0, 0, 0];
        $this->calls = [0, 0, 0, 0];

        return ++$this->spanDepth;
    }

    /**
     * Closes the span the token belongs to and returns its breakdown in the C3 shape.
     *
     * A span opened inside it and never closed, which only a hook that returned early can cause, is
     * folded into it first, so one missed close never shifts the attribution of every later span.
     *
     * @param int|null $token
     *
     * @return array<string, float|int|null>|null
     */
    public function closeSpan(?int $token): ?array
    {
        if ($token === null || $token < 1 || $token > $this->spanDepth) {
            return null;
        }

        while ($this->spanDepth > $token) {
            $this->foldIntoParent();
        }

        $breakdown = $this->buildBreakdown();
        $this->foldIntoParent();

        return $breakdown;
    }

    /**
     * Hot path: runs before every hooked call.
     *
     * @param int $category
     *
     * @return void
     */
    public function beginCall(int $category): void
    {
        // Nothing is recorded outside a span: there is no record it could belong to.
        if ($this->spanDepth === 0) {
            return;
        }

        if ($this->callDepth[$category]++ === 0) {
            $this->callStartedAt[$category] = hrtime(true);
        }
    }

    /**
     * Hot path: runs after every hooked call, including one that threw.
     *
     * @param int $category
     *
     * @return void
     */
    public function endCall(int $category): void
    {
        // Zero when the call began with no span open, so it was never started here.
        if ($this->callDepth[$category] === 0) {
            return;
        }

        if (--$this->callDepth[$category] !== 0) {
            return;
        }

        $this->nanoseconds[$category] += hrtime(true) - $this->callStartedAt[$category];
        $this->calls[$category]++;
    }

    /**
     * @return void
     */
    protected function foldIntoParent(): void
    {
        $parentTotals = array_pop($this->parentTotals);
        $this->spanDepth--;

        if ($parentTotals === null || $this->spanDepth === 0) {
            // The level below the outermost span belongs to no record, so it is not accumulated.
            $this->nanoseconds = [0, 0, 0, 0];
            $this->calls = [0, 0, 0, 0];
            $this->spanDepth = max(0, $this->spanDepth);

            return;
        }

        [$parentNanoseconds, $parentCalls] = $parentTotals;

        foreach ($this->nanoseconds as $category => $nanoseconds) {
            $parentNanoseconds[$category] += $nanoseconds;
            $parentCalls[$category] += $this->calls[$category];
        }

        $this->nanoseconds = $parentNanoseconds;
        $this->calls = $parentCalls;
    }

    /**
     * @return array<string, float|int|null>
     */
    protected function buildBreakdown(): array
    {
        $breakdown = [];

        foreach (static::CATEGORY_FIELDS as $category => [$millisecondsField, $callsField]) {
            $isInstalled = $this->installedCategories[$category];

            // Null, never 0: a category whose hook is missing was not measured, and a 0 would claim
            // that no time was spent there.
            $breakdown[$millisecondsField] = $isInstalled ? $this->nanoseconds[$category] / static::NANOSECONDS_PER_MILLISECOND : null;
            $breakdown[$callsField] = $isInstalled ? $this->calls[$category] : null;
        }

        return $breakdown;
    }
}
