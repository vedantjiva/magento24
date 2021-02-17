<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableInventory\Test\Unit\Model\ResourceModel;

use Magento\CatalogInventory\Model\ResourceModel\Stock;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableInventory\Api\Counter\ItemInterface;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Magento\ScalableInventory\Model\ResourceModel\QtyCounterConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QtyCounterConsumerTest extends TestCase
{
    /**
     * @var QtyCounterConsumer
     */
    private $consumer;

    /**
     * @var Stock|MockObject
     */
    private $stockResource;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->stockResource = $this->getMockBuilder(Stock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->consumer = $objectManager->getObject(
            QtyCounterConsumer::class,
            ['stockResource' => $this->stockResource]
        );
    }

    public function testProcessMessage()
    {
        $productId = 1;
        $qty = 1;

        $item = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())
            ->method('getProductId')
            ->willReturn($productId);
        $item->expects($this->once())
            ->method('getQty')
            ->willReturn($qty);

        /** @var ItemsInterface|MockObject $qtyObject */
        $qtyObject = $this->getMockBuilder(ItemsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qtyObject->expects($this->once())
            ->method('getItems')
            ->willReturn([$item]);

        $this->consumer->processMessage($qtyObject);
    }
}
