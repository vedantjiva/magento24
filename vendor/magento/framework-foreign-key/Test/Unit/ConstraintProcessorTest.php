<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\ForeignKey\ConstraintProcessor;
use Magento\Framework\ForeignKey\StrategyInterface;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConstraintProcessorTest extends TestCase
{
    /**
     * @var ConstraintProcessor
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $transactionManagerMock;

    /**
     * @var MockObject
     */
    protected $constraintMock;

    /**
     * @var MockObject
     */
    protected $constraintConnectionMock;

    /**
     * @var MockObject
     */
    protected $strategyMock;

    /**
     * @var MockObject
     */
    protected $selectMock;

    /**
     * @var array
     */
    protected $involvedData;

    protected function setUp(): void
    {
        $this->involvedData = [
            'item' => ['reference_field' => 'value']
        ];

        $this->transactionManagerMock =
            $this->getMockForAbstractClass(TransactionManagerInterface::class);
        $this->constraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $this->constraintConnectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->strategyMock = $this->getMockForAbstractClass(StrategyInterface::class);
        $this->selectMock = $this->createMock(Select::class);

        $this->model = new ConstraintProcessor(['strategy' => $this->strategyMock]);
    }

    public function testResolveWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('The "strategy" strategy code is unknown. Verify the code and try again.');
        $this->model = new ConstraintProcessor([]);
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testResolveWithEmptySubConstraints()
    {
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->constraintMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())->method('getTableName')->willReturn('table_name');
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())
            ->method('getCondition')
            ->with(['value'])
            ->willReturn('constraint_condition');
        $this->constraintMock->expects($this->once())->method('getSubConstraints');
        $this->transactionManagerMock->expects($this->once())
            ->method('start')
            ->with($this->constraintConnectionMock)
            ->willReturn($this->constraintConnectionMock);
        $this->strategyMock->expects($this->once())
            ->method('process')
            ->with($this->constraintConnectionMock, $this->constraintMock, 'constraint_condition');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testResolveWithEmptyLockedData()
    {
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->constraintMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())->method('getTableName')->willReturn('table_name');
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())
            ->method('getCondition')
            ->with(['value'])
            ->willReturn('constraint_conditions');
        $this->constraintMock->expects($this->once())->method('getSubConstraints')->willReturnSelf();
        $this->transactionManagerMock->expects($this->once())
            ->method('start')
            ->with($this->constraintConnectionMock)
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())
            ->method('getSubConstraintsAffectedFields')
            ->willReturn(['SubConstraintsAffectedFields']);
        $this->strategyMock->expects($this->once())
            ->method('lockAffectedData')
            ->with(
                $this->constraintConnectionMock,
                'table_name',
                'constraint_conditions',
                ['SubConstraintsAffectedFields']
            );
        $this->strategyMock->expects($this->never())->method('process');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testResolve()
    {
        $this->constraintMock->expects($this->once())->method('getStrategy')->willReturn('strategy');
        $this->constraintMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())->method('getTableName')->willReturn('table_name');
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())
            ->method('getCondition')
            ->with(['value'])
            ->willReturn('constraint_conditions');
        $this->constraintMock->expects($this->once())->method('getSubConstraints')->willReturnSelf();
        $this->transactionManagerMock->expects($this->once())
            ->method('start')
            ->with($this->constraintConnectionMock)
            ->willReturn($this->constraintConnectionMock);
        $this->constraintMock->expects($this->once())
            ->method('getSubConstraintsAffectedFields')
            ->willReturn(['SubConstraintsAffectedFields']);
        $this->strategyMock->expects($this->once())
            ->method('lockAffectedData')
            ->with(
                $this->constraintConnectionMock,
                'table_name',
                'constraint_conditions',
                ['SubConstraintsAffectedFields']
            )->willReturn(['locked_data']);
        $this->strategyMock->expects($this->once())
            ->method('process')
            ->with($this->constraintConnectionMock, $this->constraintMock, 'constraint_conditions');
        $this->model->resolve($this->transactionManagerMock, $this->constraintMock, $this->involvedData);
    }

    public function testValidateWithNullValue()
    {
        $data = ['null' => null];
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->once())->method('getFieldName')->willReturn('null');
        $this->model->validate($this->constraintMock, $data);
    }

    public function testValidate()
    {
        $data = ['data' => 'value'];
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->exactly(2))->method('getFieldName')->willReturn('data');
        $this->constraintMock->expects($this->once())
            ->method('getReferenceConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintConnectionMock->expects($this->once())->method('select')->willReturn($this->selectMock);
        $this->constraintMock->expects($this->once())
            ->method('getReferenceTableName')
            ->willReturn('reference_table_name');
        $this->selectMock->expects($this->once())
            ->method('from')
            ->with('reference_table_name')
            ->willReturn($this->selectMock);
        $this->selectMock->expects($this->once())->method('columns')->with(['reference_field'])->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('reference_field' . ' = ?', 'value')
            ->willReturnSelf();
        $this->constraintConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with($this->selectMock)
            ->willReturn(['not empty result']);
        $this->model->validate($this->constraintMock, $data);
    }

    public function testValidateException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $data = ['data' => 'value'];
        $this->constraintMock->expects($this->once())->method('getReferenceField')->willReturn('reference_field');
        $this->constraintMock->expects($this->exactly(2))->method('getFieldName')->willReturn('data');
        $this->constraintMock->expects($this->once())
            ->method('getReferenceConnection')
            ->willReturn($this->constraintConnectionMock);
        $this->constraintConnectionMock->expects($this->once())->method('select')->willReturn($this->selectMock);
        $this->constraintMock->expects($this->once())
            ->method('getReferenceTableName')
            ->willReturn('reference_table_name');
        $this->selectMock->expects($this->once())
            ->method('from')
            ->with('reference_table_name')
            ->willReturn($this->selectMock);
        $this->selectMock->expects($this->once())->method('columns')->with(['reference_field'])->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with('reference_field' . ' = ?', 'value')
            ->willReturnSelf();
        $this->constraintConnectionMock->expects($this->once())
            ->method('fetchAssoc')
            ->with($this->selectMock)
            ->willReturn([]);
        $this->model->validate($this->constraintMock, $data);

        $this->expectExceptionMessage(
            "The row couldn't be updated because a foreign key constraint failed. Verify the constraint and try again."
        );
    }
}
