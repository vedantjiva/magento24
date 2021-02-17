<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableCheckout\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\MessageQueue\MergedMessageInterface;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\ScalableCheckout\Model\Merger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Merger.
 */
class MergerTest extends TestCase
{
    /**
     * @var MergedMessageInterfaceFactory|MockObject
     */
    private $mergedMessageFactory;

    /**
     * @var Merger
     */
    private $merger;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mergedMessageFactory = $this
            ->getMockBuilder(MergedMessageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->merger = $objectManagerHelper->getObject(
            Merger::class,
            [
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
        $message = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $messages = [
            $topicName => [
                $messageId => $message
            ]
        ];
        $mergedMessage = $this->getMockBuilder(MergedMessageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mergedMessageFactory->expects($this->atLeastOnce())->method('create')->willReturn($mergedMessage);
        $result = [
            $topicName => [$mergedMessage]
        ];

        $this->assertEquals($result, $this->merger->merge($messages));
    }
}
