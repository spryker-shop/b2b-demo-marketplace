<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\PublishAndSynchronizeMetrics\Instrumentation;

use Codeception\Test\Unit;
use Spryker\Shared\PublishAndSynchronizeMetrics\Instrumentation\SettingsFile;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Shared
 * @group PublishAndSynchronizeMetrics
 * @group Instrumentation
 * @group SettingsFileTest
 * Add your own group annotations below this line
 */
class SettingsFileTest extends Unit
{
    protected const string VARIABLE = 'QUEUE_METRICS_SETTINGS_FILE_TEST';

    protected const float NOW = 1000.0;

    protected string $path;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('queue-metrics-settings-', true) . '.json';
        putenv(static::VARIABLE);
        putenv('OTHER_SETTINGS_FILE_TEST');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        putenv(static::VARIABLE);
        putenv('OTHER_SETTINGS_FILE_TEST');

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testValidFileSetsTheQueueMetricsVariables(): void
    {
        // Arrange
        $this->writeSettings(static::NOW + 60, [static::VARIABLE => '/data/out.jsonl']);

        // Act
        $applied = SettingsFile::apply($this->path, static::NOW);

        // Assert
        $this->assertSame(1, $applied);
        $this->assertSame('/data/out.jsonl', getenv(static::VARIABLE));
    }

    /**
     * @return void
     */
    public function testExpiredFileIsIgnored(): void
    {
        // Arrange
        $this->writeSettings(static::NOW - 1, [static::VARIABLE => '1']);

        // Act
        $applied = SettingsFile::apply($this->path, static::NOW);

        // Assert
        $this->assertSame(0, $applied);
        $this->assertFalse(getenv(static::VARIABLE));
    }

    /**
     * @return void
     */
    public function testFileWithoutExpiryIsIgnored(): void
    {
        // Arrange
        file_put_contents($this->path, json_encode(['env' => [static::VARIABLE => '1']]));

        // Act
        $applied = SettingsFile::apply($this->path, static::NOW);

        // Assert
        $this->assertSame(0, $applied);
        $this->assertFalse(getenv(static::VARIABLE));
    }

    /**
     * @return void
     */
    public function testVariablesOutsideTheQueueMetricsPrefixAreIgnored(): void
    {
        // Arrange
        $this->writeSettings(static::NOW + 60, ['OTHER_SETTINGS_FILE_TEST' => '1']);

        // Act
        $applied = SettingsFile::apply($this->path, static::NOW);

        // Assert
        $this->assertSame(0, $applied);
        $this->assertFalse(getenv('OTHER_SETTINGS_FILE_TEST'));
    }

    /**
     * @return void
     */
    public function testProcessEnvironmentWinsOverTheFile(): void
    {
        // Arrange
        putenv(static::VARIABLE . '=from-process');
        $this->writeSettings(static::NOW + 60, [static::VARIABLE => 'from-file']);

        // Act
        $applied = SettingsFile::apply($this->path, static::NOW);

        // Assert
        $this->assertSame(0, $applied);
        $this->assertSame('from-process', getenv(static::VARIABLE));
    }

    /**
     * @return void
     */
    public function testMissingFileAppliesNothing(): void
    {
        // Act
        $applied = SettingsFile::apply($this->path, static::NOW);

        // Assert
        $this->assertSame(0, $applied);
    }

    /**
     * @param float $expiresAt
     * @param array<string, string> $env
     *
     * @return void
     */
    protected function writeSettings(float $expiresAt, array $env): void
    {
        file_put_contents($this->path, json_encode(['expires_at' => $expiresAt, 'env' => $env]));
    }
}
