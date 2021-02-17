<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model;

use Magento\Framework\Event\Manager;
use Magento\Framework\Event\ManagerInterface;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\Archive;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchiveTest extends TestCase
{
    /**
     * @var Archive
     */
    protected $archive;

    /**
     * @var \Magento\SalesArchive\Model\ResourceModel\Archive|MockObject
     */
    protected $resourceArchive;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManager;

    /**
     * @var String
     */
    protected $archiveClassName = Archive::class;

    protected function setUp(): void
    {
        $this->resourceArchive = $this->createMock(\Magento\SalesArchive\Model\ResourceModel\Archive::class);
        $this->eventManager = $this->createMock(Manager::class);

        $this->archive = new Archive($this->resourceArchive, $this->eventManager);
    }

    public function testUpdateGridRecords()
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $this->resourceArchive->expects($this->once())
            ->method('updateGridRecords')
            ->with($this->archive, $archiveEntity, $ids);
        $result = $this->archive->updateGridRecords($archiveEntity, $ids);
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    public function testGetIdsInArchive()
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $relatedIds = [001, 003, 004];
        $this->resourceArchive->expects($this->once())
            ->method('getIdsInArchive')
            ->with($archiveEntity, $ids)
            ->willReturn($relatedIds);
        $result = $this->archive->getIdsInArchive($archiveEntity, $ids);
        $this->assertEquals($relatedIds, $result);
    }

    public function testGetRelatedIds()
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $relatedIds = [001, 003, 004];
        $this->resourceArchive->expects($this->once())
            ->method('getRelatedIds')
            ->with($archiveEntity, $ids)
            ->willReturn($relatedIds);
        $result = $this->archive->getRelatedIds($archiveEntity, $ids);
        $this->assertEquals($relatedIds, $result);
    }

    public function testArchiveOrders()
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchiveExpression')
            ->willReturn($ids);

        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(3))
            ->method('moveToArchive')
            ->with(ArchivalList::INVOICE, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(4))
            ->method('moveToArchive')
            ->with(ArchivalList::SHIPMENT, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(5))
            ->method('moveToArchive')
            ->with(ArchivalList::CREDITMEMO, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(6))
            ->method('removeFromGrid')
            ->with(ArchivalList::ORDER, $entity, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(7))
            ->method('removeFromGrid')
            ->with(ArchivalList::INVOICE, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(8))
            ->method('removeFromGrid')
            ->with(ArchivalList::SHIPMENT, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(9))
            ->method('removeFromGrid')
            ->with(ArchivalList::CREDITMEMO, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(10))
            ->method('commit')->willReturnSelf();

        $event = 'magento_salesarchive_archive_archive_orders';
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with($event, ['order_ids' => $ids]);

        $result = $this->archive->archiveOrders();
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    public function testArchiveOrdersException()
    {
        $this->expectException('Exception');
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchiveExpression')
            ->willReturn($ids);
        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)
            ->willThrowException(new \Exception());
        $this->resourceArchive->expects($this->at(3))
            ->method('rollback')->willReturnSelf();

        $result = $this->archive->archiveOrders();
        $this->assertInstanceOf('Exception', $result);
    }

    public function testArchiveOrdersById()
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchive')
            ->with($ids, false)
            ->willReturn($ids);

        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(3))
            ->method('moveToArchive')
            ->with(ArchivalList::INVOICE, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(4))
            ->method('moveToArchive')
            ->with(ArchivalList::SHIPMENT, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(5))
            ->method('moveToArchive')
            ->with(ArchivalList::CREDITMEMO, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(6))
            ->method('removeFromGrid')
            ->with(ArchivalList::ORDER, $entity, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(7))
            ->method('removeFromGrid')
            ->with(ArchivalList::INVOICE, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(8))
            ->method('removeFromGrid')
            ->with(ArchivalList::SHIPMENT, $order, $ids)->willReturnSelf();
        $this->resourceArchive->expects($this->at(9))
            ->method('removeFromGrid')
            ->with(ArchivalList::CREDITMEMO, $order, $ids)->willReturnSelf();

        $this->resourceArchive->expects($this->at(10))
            ->method('commit')->willReturnSelf();

        $event = 'magento_salesarchive_archive_archive_orders';
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with($event, ['order_ids' => $ids]);

        $result = $this->archive->archiveOrdersById($ids);
        $this->assertEquals($ids, $result);
    }

    public function testArchiveOrdersByIdException()
    {
        $this->expectException('Exception');
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchive')
            ->with($ids, false)
            ->willReturn($ids);
        $this->resourceArchive->expects($this->at(1))
            ->method('beginTransaction')->willReturnSelf();
        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)
            ->willThrowException(new \Exception());
        $this->resourceArchive->expects($this->at(3))
            ->method('rollback')->willReturnSelf();

        $result = $this->archive->archiveOrdersById($ids);
        $this->assertInstanceOf('Exception', $result);
    }

    public function testRemoveOrdersFromArchive()
    {
        $this->resourceArchive->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->resourceArchive->expects($this->at(1))
            ->method('removeFromArchive')
            ->with(ArchivalList::ORDER)->willReturnSelf();
        $this->resourceArchive->expects($this->at(2))
            ->method('removeFromArchive')
            ->with(ArchivalList::INVOICE)->willReturnSelf();
        $this->resourceArchive->expects($this->at(3))
            ->method('removeFromArchive')
            ->with(ArchivalList::SHIPMENT)->willReturnSelf();
        $this->resourceArchive->expects($this->at(4))
            ->method('removeFromArchive')
            ->with(ArchivalList::CREDITMEMO)->willReturnSelf();
        $this->resourceArchive->expects($this->at(5))
            ->method('commit')->willReturnSelf();

        $result = $this->archive->removeOrdersFromArchive();
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    public function testRemoveOrdersFromArchiveException()
    {
        $this->expectException('Exception');
        $this->resourceArchive->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->resourceArchive->expects($this->at(1))
            ->method('removeFromArchive')
            ->with(ArchivalList::ORDER)
            ->willThrowException(new \Exception());
        $this->resourceArchive->expects($this->at(2))
            ->method('rollback')->willReturnSelf();
        $result = $this->archive->removeOrdersFromArchive();
        $this->assertInstanceOf('Exception', $result);
    }

    public function testRemoveOrdersFromArchiveById()
    {
        $ids = [100021, 100023, 100054];
        $this->resourceArchive->expects($this->once())
            ->method('removeOrdersFromArchiveById')
            ->with($ids)
            ->willReturn($ids);

        $result = $this->archive->removeOrdersFromArchiveById($ids);
        $this->assertEquals($ids, $result);
    }
}
