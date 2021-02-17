<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Sales\Model\ResourceModel\Provider\UpdatedIdListProvider;
use Magento\SalesArchive\Model\ResourceModel\Archive\TableMapper;
use Magento\SalesArchive\Plugin\ArchivedEntitiesProcessorPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchivedEntitiesProcessorPluginTest extends TestCase
{
    /**
     * @var ArchivedEntitiesProcessorPlugin
     */
    private $plugin;

    /**
     * @var MockObject
     */
    private $resourceConnectionMock;
    /**
     * @var MockObject
     */
    private $tableMapperMock;

    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tableMapperMock = $this->getMockBuilder(TableMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->plugin = new ArchivedEntitiesProcessorPlugin(
            $this->resourceConnectionMock,
            $this->tableMapperMock
        );
    }

    public function testAfterGetIds()
    {
        $result = [1, 2, 5, 8];
        $archivedValues = [1];
        $mainTableName = 'sales_order';
        $gridTableName = 'sales_order_grid';
        $this->tableMapperMock
            ->expects($this::once())
            ->method('getArchiveEntityTableBySourceTable')
            ->willReturn('sales_order_archive_grid');
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $providerMock = $this->getMockBuilder(UpdatedIdListProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock
            ->expects($this::once())
            ->method('getConnection')
            ->with('sales')
            ->willReturn($connectionMock);
        $selectMock->expects($this::once())->method('where')->willReturnSelf();
        $selectMock->expects($this::once())->method('from')->willReturnSelf();
        $connectionMock->expects($this::once())->method('select')->willReturn($selectMock);
        $connectionMock->expects($this::once())->method('fetchAll')->willReturn($archivedValues);
        $this::assertEquals(
            array_diff($result, $archivedValues),
            $this->plugin->afterGetIds($providerMock, $result, $mainTableName, $gridTableName)
        );
    }
}
