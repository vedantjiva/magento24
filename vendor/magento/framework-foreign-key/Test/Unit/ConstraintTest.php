<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ForeignKey\Constraint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConstraintTest extends TestCase
{
    /**
     * @var Constraint
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $resourceMock;

    /**
     * @var MockObject
     */
    protected $connectionMock;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);

        $this->model = new Constraint(
            $this->resourceMock,
            'name',
            'connectionName',
            'referenceConnection',
            'tableName',
            'referenceTableName',
            'fieldName',
            'referenceFieldName',
            'deleteStrategy',
            ['subConstraints'],
            ['tableAffectedFields']
        );
    }

    public function testGetConnection()
    {
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with('connectionName')
            ->willReturn($this->connectionMock);

        $this->assertEquals($this->connectionMock, $this->model->getConnection());
    }

    public function testGetReferenceConnection()
    {
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with('referenceConnection')
            ->willReturn($this->connectionMock);
        $this->assertEquals($this->connectionMock, $this->model->getReferenceConnection());
    }

    public function testFetCondition()
    {
        $values = [];
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with('connectionName')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())
            ->method('quoteInto')
            ->with('fieldName IN(?)', $values)
            ->willReturn('string');

        $this->assertEquals('string', $this->model->getCondition($values));
    }

    /**
     * @dataProvider allowedStrategyDataProvider
     */
    public function testGetSubConstraints($strategy)
    {
        $this->model = new Constraint(
            $this->resourceMock,
            'name',
            'connectionName',
            'referenceConnection',
            'tableName',
            'referenceTableName',
            'fieldName',
            'referenceFieldName',
            $strategy,
            ['subConstraints'],
            ['tableAffectedFields']
        );

        $this->assertEquals(['subConstraints'], $this->model->getSubConstraints());
    }

    public function allowedStrategyDataProvider()
    {
        return [
            ['CASCADE'],
            ['DB CASCADE'],
        ];
    }

    public function testGetSubConstraintsStrategyIsNotAllowed()
    {
        $this->assertEquals([], $this->model->getSubConstraints());
    }

    public function testGetTableName()
    {
        $this->assertEquals('tableName', $this->model->getTableName());
    }

    public function testGetReferenceTableName()
    {
        $this->assertEquals('referenceTableName', $this->model->getReferenceTableName());
    }

    public function testGetFieldName()
    {
        $this->assertEquals('fieldName', $this->model->getFieldName());
    }

    public function testGetReferenceField()
    {
        $this->assertEquals('referenceFieldName', $this->model->getReferenceField());
    }

    public function testGetStrategy()
    {
        $this->assertEquals('deleteStrategy', $this->model->getStrategy());
    }

    public function testGetSubConstraintsAffectedFields()
    {
        $this->assertEquals(['tableAffectedFields'], $this->model->getSubConstraintsAffectedFields());
    }
}
