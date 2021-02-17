<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Helper\Catalog\Product;

use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GiftCard\Helper\Catalog\Product\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests of helper for fetching properties by product configurational item
 */
class ConfigurationTest extends TestCase
{
    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /**
     * @var Configuration
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Helper\Product\Configuration|MockObject
     */
    protected $ctlgProdConfigur;

    /**
     * @var Escaper|MockObject
     */
    protected $escaper;

    protected function setUp(): void
    {
        $this->ctlgProdConfigur = $this->getMockBuilder(\Magento\Catalog\Helper\Product\Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->helper = $this->objectManagerHelper->getObject(
            Configuration::class,
            [
                'context' => $context,
                'ctlgProdConfigur' => $this->ctlgProdConfigur,
                'escaper' => $this->escaper
            ]
        );
    }

    public function testGetGiftcardOptions()
    {
        $expected = [
            [
                'label' => 'Gift Card Sender',
                'value' => 'sender_name &lt;sender@test.com&gt;',
                'option_type' => 'html'
            ],
            [
                'label' => 'Gift Card Recipient',
                'value' => 'recipient_name &lt;recipient@test.com&gt;',
                'option_type' => 'html'
            ],
            [
                'label' => 'Gift Card Message',
                'value' => 'some message',
                'option_type' => 'html'
            ],
        ];

        $itemMock = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->prepareCustomOption($itemMock, 'giftcard_sender_name', 'sender_name', 0, 'sender_name');
        $this->prepareCustomOption($itemMock, 'giftcard_sender_email', 'sender_email', 1, 'sender@test.com');
        $this->prepareCustomOption($itemMock, 'giftcard_recipient_name', 'recipient_name', 2, 'recipient_name');
        $this->prepareCustomOption($itemMock, 'giftcard_recipient_email', 'recipient_email', 3, 'recipient@test.com');
        $this->prepareCustomOption($itemMock, 'giftcard_message', 'giftcard_message', 4, 'some message');

        $this->assertEquals($expected, $this->helper->getGiftcardOptions($itemMock));
    }

    /**
     * @param $itemMock
     * @param $code
     * @param $value
     * @param $index
     * @param $result
     * @return mixed
     */
    private function prepareCustomOption($itemMock, $code, $value, $index, $result)
    {
        $optionMock = $this->getMockBuilder(
            OptionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->at($index))
            ->method('getOptionByCode')
            ->with($code)
            ->willReturn($optionMock);

        $optionMock->expects($this->once())
            ->method('getValue')
            ->willReturn($value);

        $this->escaper->expects($this->at($index))
            ->method('escapeHtml')
            ->with($value)
            ->willReturn($result);

        return $result;
    }

    public function testPrepareCustomOptionWithoutValue()
    {
        $code = 'option_code';

        $itemMock = $this->getMockBuilder(ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $optionMock = $this->getMockBuilder(
            OptionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with($code)
            ->willReturn($optionMock);
        $optionMock->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $this->assertFalse($this->helper->prepareCustomOption($itemMock, $code));
    }
}
