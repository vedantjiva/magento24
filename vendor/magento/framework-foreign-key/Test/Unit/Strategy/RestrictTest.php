<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\Strategy;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\ForeignKey\Strategy\Restrict;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestrictTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $connectionMock;

    /**
     * @var Restrict
     */
    protected $strategy;

    protected function setUp(): void
    {
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $objectManager = new ObjectManager($this);
        $this->strategy = $objectManager->getObject(Restrict::class);
    }

    public function testProcess()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $constraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $condition = 'cond1';

        $this->strategy->process($this->connectionMock, $constraintMock, $condition);

        $this->expectExceptionMessage(
            "The row couldn't be updated because a foreign key constraint failed. Verify the constraint and try again."
        );
    }

    public function testLockAffectedDataException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $table = 'sampleTable';
        $condition = 'sampleCondition';
        $fields = [3, 75, 56, 67];
        $affectedData = 'some data';

        $selectMock = $this->createMock(Select::class);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($selectMock);

        $selectMock->expects($this->once())->method('forUpdate')->with(true)->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with($table, $fields)->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with($condition)->willReturnSelf();

        $this->connectionMock->expects($this->once())->method('fetchAssoc')->willReturn($affectedData);

        $this->strategy->lockAffectedData($this->connectionMock, $table, $condition, $fields);

        $this->expectExceptionMessage(
            "The row couldn't be updated because a foreign key constraint failed. Verify the constraint and try again."
        );
    }

    public function testLockAffectedData()
    {
        $table = 'sampleTable';
        $condition = 'sampleCondition';
        $fields = [3, 75, 56, 67];
        $affectedData = null;

        $selectMock = $this->createMock(Select::class);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($selectMock);

        $selectMock->expects($this->once())->method('forUpdate')->with(true)->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with($table, $fields)->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with($condition)->willReturnSelf();

        $this->connectionMock->expects($this->once())->method('fetchAssoc')->willReturn($affectedData);

        $result = $this->strategy->lockAffectedData($this->connectionMock, $table, $condition, $fields);
        $this->assertEquals($affectedData, $result);
    }
}
