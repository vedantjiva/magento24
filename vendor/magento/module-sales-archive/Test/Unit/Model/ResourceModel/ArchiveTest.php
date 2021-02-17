<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Db\Context as ResourceModelContext;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\ResourceModel\Attribute;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\Archive;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\ResourceModel\Archive as ArchiveResourceModel;
use Magento\SalesSequence\Model\Manager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ArchiveTest extends TestCase
{
    /**
     * @var \Magento\SalesArchive\Model\Archive|
     */
    protected $archive;

    /**
     * @var Archive|MockObject
     */
    protected $archiveMock;

    /**
     * @var MockObject ///\Magento\SalesArchive\Model\ResourceModel\Archive|
     */
    protected $resourceArchiveMock;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Config|MockObject
     */
    protected $configMock;

    /**
     * @var ArchivalList|MockObject
     */
    protected $archivalListMock;

    /**
     * @var DateTime|MockObject
     */
    protected $dateTimeMock;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);

        $this->configMock = $this->createMock(Config::class);

        $this->archivalListMock = $this->createMock(ArchivalList::class);

        $this->dateTimeMock = $this->createMock(DateTime::class);

        $contextMock = $this->createMock(ResourceModelContext::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);
        $attributeMock = $this->createMock(Attribute::class);
        $sequenceManagerMock = $this->createMock(Manager::class);
        $entitySnapshotMock = $this->createMock(
            Snapshot::class
        );
        $entityRelationMock = $this->createMock(
            RelationComposite::class
        );

        $this->resourceArchiveMock = $this->getMockBuilder(ArchiveResourceModel::class)
            ->setConstructorArgs([
                $contextMock,
                $entitySnapshotMock,
                $entityRelationMock,
                $attributeMock,
                $sequenceManagerMock,
                $this->configMock,
                $this->archivalListMock,
                $this->dateTimeMock
            ])
            ->setMethods([
                'getIdsInArchive',
                'beginTransaction',
                'removeFromArchive',
                'commit',
                'rollback',
            ])
            ->getMock();

        $contextMock = $this->createMock(ResourceModelContext::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $objectManager = new ObjectManager($this);
        $this->archive = $objectManager->getObject(
            ArchiveResourceModel::class,
            [
                'context' => $contextMock,
                'attribute' => $attributeMock,
                'sequenceManager' => $sequenceManagerMock,
                'entitySnapshot' => $entitySnapshotMock,
                'salesArchiveConfig' => $this->configMock,
                'archivalList' => $this->archivalListMock,
                'dateTime' => $this->dateTimeMock
            ]
        );
    }

    private function getEntityNames()
    {
        return [
            ArchivalList::ORDER,
            ArchivalList::INVOICE,
            ArchivalList::SHIPMENT,
            ArchivalList::CREDITMEMO
        ];
    }

    public function testRemoveOrdersFromArchiveById()
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchiveMock->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->archivalListMock->expects($this->once())
            ->method('getEntityNames')
            ->willReturn($this->getEntityNames());
        $this->resourceArchiveMock->expects($this->at(1))
            ->method('getIdsInArchive')
            ->with(ArchivalList::ORDER, $ids)
            ->willReturn($ids);
        $this->resourceArchiveMock->expects($this->at(2))
            ->method('removeFromArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)->willReturnSelf();
        $this->resourceArchiveMock->expects($this->at(3))
            ->method('getIdsInArchive')
            ->with(ArchivalList::INVOICE, $ids)
            ->willReturn($ids);
        $this->resourceArchiveMock->expects($this->at(4))
            ->method('removeFromArchive')
            ->with(ArchivalList::INVOICE, $order, $ids)->willReturnSelf();
        $this->resourceArchiveMock->expects($this->at(5))
            ->method('getIdsInArchive')
            ->with(ArchivalList::SHIPMENT, $ids)
            ->willReturn($ids);
        $this->resourceArchiveMock->expects($this->at(6))
            ->method('removeFromArchive')
            ->with(ArchivalList::SHIPMENT, $order, $ids)->willReturnSelf();
        $this->resourceArchiveMock->expects($this->at(7))
            ->method('getIdsInArchive')
            ->with(ArchivalList::CREDITMEMO, $ids)
            ->willReturn($ids);
        $this->resourceArchiveMock->expects($this->at(8))
            ->method('removeFromArchive')
            ->with(ArchivalList::CREDITMEMO, $order, $ids)->willReturnSelf();
        $this->resourceArchiveMock->expects($this->at(9))
            ->method('commit')->willReturnSelf();
        $result = $this->resourceArchiveMock->removeOrdersFromArchiveById($ids);
        $this->assertEquals($ids, $result);
    }

    public function testRemoveOrdersFromArchiveByIdException()
    {
        $this->expectException('Exception');
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->archivalListMock->expects($this->once())
            ->method('getEntityNames')
            ->willReturn($this->getEntityNames());
        $this->resourceArchiveMock->expects($this->once())
            ->method('getIdsInArchive')
            ->with(ArchivalList::ORDER, $ids)
            ->willReturn($ids);
        $this->resourceArchiveMock->expects($this->once())
            ->method('beginTransaction')->willReturnSelf();
        $this->resourceArchiveMock->expects($this->once())
            ->method('removeFromArchive')
            ->with(ArchivalList::ORDER, $entity, $ids)
            ->willThrowException(new \Exception());
        $this->resourceArchiveMock->expects($this->once())
            ->method('rollback');

        $result = $this->resourceArchiveMock->removeOrdersFromArchiveById($ids);
        $this->assertInstanceOf('Exception', $result);
    }
}
