<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableInventory\Test\Unit\Model\ResourceModel;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\MessageQueue\PublisherPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Magento\ScalableInventory\Model\Counter\ItemsBuilder;
use Magento\ScalableInventory\Model\ResourceModel\QtyCounter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QtyCounterTest extends TestCase
{
    /**
     * @var ItemsBuilder|MockObject
     */
    private $itemsBuilder;

    /**
     * @var PublisherInterface|MockObject
     */
    private $publisher;

    /**
     * @var QtyCounter
     */
    private $resource;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->itemsBuilder = $this->getMockBuilder(ItemsBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publisher = $this->getMockBuilder(PublisherPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resource = $objectManager->getObject(
            QtyCounter::class,
            ['itemsBuilder' => $this->itemsBuilder, 'publisher' => $this->publisher]
        );
    }

    public function testCorrectItemsQty()
    {
        $items = [4 => 2, 23 => 12];
        $websiteId = 1;
        $operator = '-';

        $itemsObject = $this->getMockBuilder(ItemsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->itemsBuilder->expects($this->once())
            ->method('build')
            ->with($items, $websiteId, $operator)
            ->willReturn($itemsObject);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with(QtyCounter::TOPIC_NAME, $itemsObject);

        $this->resource->correctItemsQty($items, $websiteId, $operator);
    }
}
