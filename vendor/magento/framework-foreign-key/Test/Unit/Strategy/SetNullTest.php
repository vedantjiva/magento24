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
use Magento\Framework\ForeignKey\Strategy\SetNull;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetNullTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $connectionMock;

    /**
     * @var SetNull
     */
    protected $strategy;

    protected function setUp(): void
    {
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $objectManager = new ObjectManager($this);
        $this->strategy = $objectManager->getObject(SetNull::class);
    }

    public function testProcess()
    {
        $constraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $condition = 'cond1';
        $tableName = 'large_table';
        $fieldName = 'first';

        $constraintMock->expects($this->once())->method('getTableName')->willReturn($tableName);
        $constraintMock->expects($this->once())->method('getFieldName')->willReturn($fieldName);

        $this->connectionMock->expects($this->once())
            ->method('update')
            ->with($tableName, [$fieldName => null], $condition);
        $this->strategy->process($this->connectionMock, $constraintMock, $condition);
    }

    public function testLockAffectedData()
    {
        $table = 'sampleTable';
        $condition = 'sampleCondition';
        $fields = [4, 5, 6, 7];
        $affectedData = ['item1', 'item2'];

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
