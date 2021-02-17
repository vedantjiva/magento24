<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\ObjectRelationProcessor;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\ForeignKey\ConfigInterface;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\ForeignKey\ConstraintProcessor;
use Magento\Framework\ForeignKey\ObjectRelationProcessor\EnvironmentConfig;
use Magento\Framework\ForeignKey\ObjectRelationProcessor\Plugin;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    protected $model;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $configMock;

    /**
     * @var ConstraintProcessor|MockObject
     */
    protected $constraintProcessorMock;

    /**
     * @var MockObject
     */
    protected $subjectMock;

    /**
     * @var MockObject
     */
    protected $transactionManagerMock;

    /**
     * @var MockObject
     */
    protected $connectionMock;

    /**
     * @var MockObject
     */
    protected $constraintsMock;

    /**
     * @var MockObject
     */
    private $environmentConfigMock;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockForAbstractClass(ConfigInterface::class);

        $this->constraintProcessorMock = $this->createMock(ConstraintProcessor::class);

        $this->subjectMock =
            $this->createMock(ObjectRelationProcessor::class);
        $this->transactionManagerMock =
            $this->getMockForAbstractClass(TransactionManagerInterface::class);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->constraintsMock = $this->getMockForAbstractClass(ConstraintInterface::class);

        $this->environmentConfigMock = $this->createMock(EnvironmentConfig::class);

        $this->model = new Plugin(
            $this->configMock,
            $this->constraintProcessorMock,
            $this->environmentConfigMock
        );
    }

    public function testBeforeDelete()
    {
        $this->environmentConfigMock->expects($this->once())->method('isScalable')->willReturn(true);
        $selectMock = $this->createMock(Select::class);
        $this->connectionMock->expects($this->once())->method('select')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('forUpdate')->with(true)->willReturnSelf();
        $selectMock->expects($this->once())->method('from')->with('table_name')->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with('condition')->willReturnSelf();
        $this->connectionMock->expects($this->once())->method('fetchAssoc')->with($selectMock);
        $this->configMock->expects($this->once())
            ->method('getConstraintsByReferenceTableName')
            ->with('table_name')
            ->willReturn([$this->constraintsMock]);
        $this->constraintProcessorMock->expects($this->once())
            ->method('resolve')
            ->with($this->transactionManagerMock, $this->constraintsMock, [[]]);
        $this->model->beforeDelete(
            $this->subjectMock,
            $this->transactionManagerMock,
            $this->connectionMock,
            'table_name',
            'condition',
            []
        );
    }

    public function testBeforeValidateDataIntegrityForNativeDBConstraints()
    {
        $this->environmentConfigMock->expects($this->once())->method('isScalable')->willReturn(true);
        $this->configMock->expects($this->once())
            ->method('getConstraintsByTableName')
            ->with('table_name')
            ->willReturn([$this->constraintsMock]);
        $this->constraintsMock->expects($this->once())->method('getStrategy')->willReturn('DB ');

        $this->constraintProcessorMock->expects($this->never())->method('validate');
        $this->model->beforeValidateDataIntegrity($this->subjectMock, 'table_name', []);
    }

    public function testBeforeValidateDataIntegrity()
    {
        $this->environmentConfigMock->expects($this->once())->method('isScalable')->willReturn(true);
        $this->configMock->expects($this->once())
            ->method('getConstraintsByTableName')
            ->with('table_name')
            ->willReturn([$this->constraintsMock]);
        $this->constraintsMock->expects($this->once())->method('getStrategy')->willReturn('notDB');

        $this->constraintProcessorMock->expects($this->once())->method('validate')->with($this->constraintsMock, []);
        $this->model->beforeValidateDataIntegrity($this->subjectMock, 'table_name', []);
    }
}
