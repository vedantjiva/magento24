<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableInventory\Test\Unit\Model\Counter;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableInventory\Api\Counter\ItemInterface;
use Magento\ScalableInventory\Api\Counter\ItemInterfaceFactory;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Magento\ScalableInventory\Api\Counter\ItemsInterfaceFactory;
use Magento\ScalableInventory\Model\Counter\ItemsBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemsBuilderTest extends TestCase
{
    /**
     * @var ItemsBuilder
     */
    private $builder;

    /**
     * @var ItemInterface|MockObject
     */
    private $item;

    /**
     * @var ItemsInterface|MockObject
     */
    private $qty;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->item = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $itemFactory = $this->getMockBuilder(ItemInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $itemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->item);

        $this->qty = $this->getMockBuilder(ItemsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $itemsFactory = $this->getMockBuilder(ItemsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $itemsFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->qty);

        $this->builder = $objectManager->getObject(
            ItemsBuilder::class,
            ['itemsFactory' => $itemsFactory, 'itemFactory' => $itemFactory]
        );
    }

    public function testBuild()
    {
        $productId = 1;
        $qty = 1;
        $items = [$productId => $qty];
        $websiteId = 1;
        $operator = '+';

        $this->item->expects($this->once())
            ->method('setProductId')
            ->with($productId);
        $this->item->expects($this->once())
            ->method('setQty')
            ->with($qty);

        $this->qty->expects($this->once())
            ->method('setItems')
            ->with([$this->item]);
        $this->qty->expects($this->once())
            ->method('setWebsiteId')
            ->with($websiteId);
        $this->qty->expects($this->once())
            ->method('setOperator')
            ->with($operator);

        $result = $this->builder->build($items, $websiteId, $operator);

        $this->assertEquals($this->qty, $result);
    }
}
