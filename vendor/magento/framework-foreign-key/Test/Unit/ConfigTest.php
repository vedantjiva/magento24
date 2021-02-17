<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit;

use Magento\Framework\ForeignKey\Config;
use Magento\Framework\ForeignKey\Config\Data;
use Magento\Framework\ForeignKey\ConstraintFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $model;

    /**
     * @var Data|MockObject
     */
    protected $dataContainerMock;

    /**
     * @var ConstraintFactory|MockObject
     */
    protected $constraintFactoryMock;

    protected function setUp(): void
    {
        $this->dataContainerMock = $this->createMock(Data::class);
        $this->constraintFactoryMock = $this->createMock(ConstraintFactory::class);

        $this->model = new Config(
            $this->dataContainerMock,
            $this->constraintFactoryMock
        );
    }

    public function testGetConstraintsByReferenceTableNameReferenceIsSet()
    {
        $referenceTableName = 'reference_table_name';

        $constraintConfig = [$referenceTableName => ['value']];
        $this->dataContainerMock->expects($this->once())
            ->method('get')
            ->with('constraints_by_reference_table')
            ->willReturn($constraintConfig);
        $this->constraintFactoryMock->expects($this->once())
            ->method('get')
            ->with('value', $constraintConfig)->willReturn([]);

        $this->assertEquals([0 => []], $this->model->getConstraintsByReferenceTableName($referenceTableName));
    }

    public function testGetConstraintsByReferenceTableName()
    {
        $referenceTableName = 'reference_table_name';

        $constraintConfig = [$referenceTableName];
        $this->dataContainerMock->expects($this->once())
            ->method('get')
            ->with('constraints_by_reference_table')
            ->willReturn($constraintConfig);

        $this->constraintFactoryMock->expects($this->never())->method('get');
        $this->assertEquals([], $this->model->getConstraintsByReferenceTableName($referenceTableName));
    }

    public function testGetConstraintsByTableNameConstrainsIsSet()
    {
        $tableName = 'reference_table_name';
        $constraintsByTable = [$tableName => ['value']];
        $this->dataContainerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['constraints_by_reference_table', null, []],
                ['constraints_by_table', null, $constraintsByTable],
            ]);
        $this->constraintFactoryMock->expects($this->once())
            ->method('get')
            ->with('value', [])->willReturn([]);

        $this->assertEquals([0 => []], $this->model->getConstraintsByTableName($tableName));
    }

    public function testGetConstraintsByTableName()
    {
        $tableName = 'reference_table_name';
        $constraintsByTable = [];
        $this->dataContainerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['constraints_by_reference_table', null, []],
                ['constraints_by_table', null, $constraintsByTable],
            ]);
        $this->constraintFactoryMock->expects($this->never())->method('get');

        $this->assertEquals([], $this->model->getConstraintsByTableName($tableName));
    }
}
