<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableInventory\Test\Unit\Model;

use Magento\Framework\MessageQueue\MergedMessageInterface;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Magento\ScalableInventory\Model\Counter\ItemsBuilder;
use Magento\ScalableInventory\Model\Merger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Merger.
 */
class MergerTest extends TestCase
{
    /**
     * @var ItemsBuilder|MockObject
     */
    private $itemsBuilder;

    /**
     * @var MergedMessageInterfaceFactory|MockObject
     */
    private $mergedMessageFactory;

    /**
     * @var Merger|MockObject
     */
    private $merger;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->itemsBuilder = $this->getMockBuilder(ItemsBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mergedMessageFactory = $this
            ->getMockBuilder(MergedMessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->merger = $objectManagerHelper->getObject(
            Merger::class,
            [
                'itemsBuilder' => $this->itemsBuilder,
                'mergedMessageFactory' => $this->mergedMessageFactory
            ]
        );
    }

    /**
     * Test for merge().
     *
     * @return void
     */
    public function testMerge()
    {
        $topicName = 'topic';
        $messageId = 1;
        $operator = '-';
        $websiteId = 2;
        $productId = 3;
        $qty = 4;
        $messageItem = $this->getMockBuilder(ItemsInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQty', 'getProductId'])
            ->getMockForAbstractClass();
        $messageItem->expects($this->atLeastOnce())->method('getQty')->willReturn($qty);
        $messageItem->expects($this->atLeastOnce())->method('getProductId')->willReturn($productId);
        $message = $this->getMockBuilder(ItemsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $message->expects($this->atLeastOnce())->method('getItems')->willReturn([$messageItem]);
        $message->expects($this->atLeastOnce())->method('getOperator')->willReturn($operator);
        $message->expects($this->atLeastOnce())->method('getWebsiteId')->willReturn($websiteId);
        $mergedMessage = $this->getMockBuilder(MergedMessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mergedMessageFactory->expects($this->atLeastOnce())->method('create')->willReturn($mergedMessage);
        $messages = [
            $topicName => [
                $messageId => $message
            ]
        ];
        $result = [
            $topicName => [$mergedMessage]
        ];

        $this->assertEquals($result, $this->merger->merge($messages));
    }
}
