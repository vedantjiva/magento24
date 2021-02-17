<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\ForeignKey\ConstraintFactory;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConstraintFactoryTest extends TestCase
{
    /**
     * @var ConstraintFactory
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $objectManagerMock;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);

        $this->model = new ConstraintFactory($this->objectManagerMock);
    }

    public function testGetConstraintConfigTableNameIsNotSet()
    {
        $constraintData = [
            'name' => 'name',
            'connection' => 'connectionName',
            'reference_connection' => 'referenceConnection',
            'table_name' => 'tableName',
            'reference_table_name' => 'referenceTableName',
            'field_name' => 'fieldName',
            'reference_field_name' => 'referenceFieldName',
            'delete_strategy' => 'deleteStrategy',
            'table_affected_fields' => 'tableAffectedFields'
        ];
        $constraintConfig = [];
        $this->objectManagerMock->expects($this->once())->method('create')->with(
            ConstraintInterface::class,
            [
                'name' => $constraintData['name'],
                'connectionName' => $constraintData['connection'],
                'referenceConnection' => $constraintData['reference_connection'],
                'tableName' => $constraintData['table_name'],
                'referenceTableName' => $constraintData['reference_table_name'],
                'fieldName' => $constraintData['field_name'],
                'referenceFieldName' => $constraintData['reference_field_name'],
                'deleteStrategy' => $constraintData['delete_strategy'],
                'subConstraints' => [],
                'tableAffectedFields' => $constraintData['table_affected_fields'],
            ]
        );
        $this->model->get($constraintData, [$constraintConfig]);
    }
}
