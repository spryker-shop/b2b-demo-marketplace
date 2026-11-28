<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace PyzTest\Zed\PriceProductDataImport\Business\Model;

use ArrayObject;
use Codeception\Test\Unit;
use Orm\Zed\PriceProduct\Persistence\SpyPriceType;
use PHPUnit\Framework\MockObject\MockObject;
use Pyz\Zed\PriceProductDataImport\Business\Model\PriceProductWriterStep;
use Pyz\Zed\PriceProductDataImport\PriceProductDataImportConfig;
use ReflectionMethod;

/**
 * Backport of the spryker/suite#1644 tests (CC-41867).
 *
 * Auto-generated group annotations
 *
 * @group PyzTest
 * @group Zed
 * @group PriceProductDataImport
 * @group Business
 * @group Model
 * @group PriceProductWriterStepTest
 * Add your own group annotations below this line
 */
class PriceProductWriterStepTest extends Unit
{
    /**
     * @var string
     */
    protected const PRICE_TYPE_DEFAULT = 'DEFAULT';

    /**
     * @var string
     */
    protected const PRICE_TYPE_ORIGINAL = 'ORIGINAL';

    /**
     * @var \ArrayObject<string, int>
     */
    protected ArrayObject $lookupCountByPriceTypeName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lookupCountByPriceTypeName = new ArrayObject();
    }

    public function testGetIdPriceTypeReadsAnExistingPriceTypeOnlyOnce(): void
    {
        // Arrange
        $priceProductWriterStep = $this->createPriceProductWriterStepWithPriceTypes([
            static::PRICE_TYPE_DEFAULT => [$this->createPriceTypeEntityMock(1, false)],
            static::PRICE_TYPE_ORIGINAL => [$this->createPriceTypeEntityMock(2, false)],
        ]);

        // Act
        $idPriceTypes = [];
        for ($row = 0; $row < 3; $row++) {
            $idPriceTypes[] = $this->callGetIdPriceType($priceProductWriterStep, static::PRICE_TYPE_DEFAULT);
            $idPriceTypes[] = $this->callGetIdPriceType($priceProductWriterStep, static::PRICE_TYPE_ORIGINAL);
        }

        // Assert
        $this->assertSame([1, 2, 1, 2, 1, 2], $idPriceTypes);
        $this->assertSame(
            [static::PRICE_TYPE_DEFAULT => 1, static::PRICE_TYPE_ORIGINAL => 1],
            $this->lookupCountByPriceTypeName->getArrayCopy(),
        );
    }

    public function testGetIdPriceTypeDoesNotCacheAPriceTypeCreatedInTheImport(): void
    {
        // Arrange
        $newPriceTypeEntityMock = $this->createPriceTypeEntityMock(3, true);
        $newPriceTypeEntityMock->expects($this->once())->method('save');

        $priceProductWriterStep = $this->createPriceProductWriterStepWithPriceTypes([
            static::PRICE_TYPE_DEFAULT => [$newPriceTypeEntityMock, $this->createPriceTypeEntityMock(3, false)],
        ]);

        // Act
        $idPriceTypes = [];
        for ($row = 0; $row < 3; $row++) {
            $idPriceTypes[] = $this->callGetIdPriceType($priceProductWriterStep, static::PRICE_TYPE_DEFAULT);
        }

        // Assert
        $this->assertSame([3, 3, 3], $idPriceTypes);
        $this->assertSame([static::PRICE_TYPE_DEFAULT => 3], $this->lookupCountByPriceTypeName->getArrayCopy());
    }

    public function testGetPriceProductDataImportBulkSizeReturnsOneThousandByDefault(): void
    {
        // Act
        $bulkSize = (new PriceProductDataImportConfig())->getPriceProductDataImportBulkSize();

        // Assert
        $this->assertSame(1000, $bulkSize);
    }

    /**
     * @param array<string, array<\Orm\Zed\PriceProduct\Persistence\SpyPriceType>> $priceTypeEntitiesByName Entities returned by consecutive lookups; the last one repeats.
     */
    protected function createPriceProductWriterStepWithPriceTypes(array $priceTypeEntitiesByName): PriceProductWriterStep
    {
        return new class ($priceTypeEntitiesByName, $this->lookupCountByPriceTypeName) extends PriceProductWriterStep {
            /**
             * @param array<string, array<\Orm\Zed\PriceProduct\Persistence\SpyPriceType>> $priceTypeEntitiesByName
             * @param \ArrayObject<string, int> $lookupCountByPriceTypeName
             */
            public function __construct(
                protected array $priceTypeEntitiesByName,
                protected ArrayObject $lookupCountByPriceTypeName,
            ) {
            }

            protected function findOrCreatePriceTypeEntity(string $priceTypeName): SpyPriceType
            {
                $this->lookupCountByPriceTypeName[$priceTypeName] = ($this->lookupCountByPriceTypeName[$priceTypeName] ?? 0) + 1;

                $priceTypeEntities = $this->priceTypeEntitiesByName[$priceTypeName];
                $lookupIndex = min($this->lookupCountByPriceTypeName[$priceTypeName], count($priceTypeEntities)) - 1;

                return $priceTypeEntities[$lookupIndex];
            }
        };
    }

    protected function createPriceTypeEntityMock(int $idPriceType, bool $isNew): SpyPriceType&MockObject
    {
        $priceTypeEntityMock = $this->getMockBuilder(SpyPriceType::class)
            ->onlyMethods(['isNew', 'isModified', 'save', 'getIdPriceType'])
            ->getMock();
        $priceTypeEntityMock->method('isNew')->willReturn($isNew);
        $priceTypeEntityMock->method('isModified')->willReturn($isNew);
        $priceTypeEntityMock->method('getIdPriceType')->willReturn($idPriceType);

        return $priceTypeEntityMock;
    }

    protected function callGetIdPriceType(PriceProductWriterStep $priceProductWriterStep, string $priceTypeName): int
    {
        return (new ReflectionMethod($priceProductWriterStep, 'getIdPriceType'))->invoke($priceProductWriterStep, $priceTypeName);
    }
}
