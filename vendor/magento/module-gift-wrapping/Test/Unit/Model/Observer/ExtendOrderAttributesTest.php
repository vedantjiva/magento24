<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Model\Observer\ExtendOrderAttributes;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test of order attributes extension observer.
 */
class ExtendOrderAttributesTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var ExtendOrderAttributes
     */
    protected $subject;

    /**
     * Event observer mock.
     *
     * @var Observer|MockObject
     */
    protected $observerMock;

    /**
     * Event mock.
     *
     * @var DataObject|MockObject
     */
    protected $eventMock;

    /**
     * Order model mock.
     *
     * @var \Magento\Sales\Model\Order|MockObject
     */
    protected $orderMock;

    /**
     * Quote address model mock.
     *
     * @var \Magento\Quote\Model\Address|MockObject
     */
    protected $quoteAddressMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->observerMock = $this->createPartialMock(Observer::class, ['getEvent']);

        $this->eventMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->orderMock = $this->createMock(
            Order::class
        );

        $quoteMock = $this->createPartialMock(Quote::class, ['getShippingAddress']);

        $this->quoteAddressMock = $this->createPartialMock(
            Address::class,
            ['hasData', 'getData']
        );
        $quoteMock->expects($this->once())->method('getShippingAddress')->willReturn($this->quoteAddressMock);

        $this->eventMock->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->eventMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->subject = $objectManager->getObject(
            ExtendOrderAttributes::class
        );
    }

    public function testExecute()
    {
        $gwBasePriceInclTax = 25;

        $this->quoteAddressMock->expects($this->any())->method('hasData')->willReturnCallback(
            function ($attribute) {
                return in_array($attribute, ['gw_id', 'gw_allow_gift_receipt', 'gw_base_price_incl_tax']);
            }
        );

        $this->quoteAddressMock->expects($this->at(1))->method('getData')->with('gw_id')->willReturn(1);
        $this->orderMock->expects($this->at(0))->method('setData')->with('gw_id', 1);

        $this->quoteAddressMock->expects($this->at(3))->method('getData')->with('gw_allow_gift_receipt')
            ->willReturn(true);
        $this->orderMock->expects($this->at(1))->method('setData')->with('gw_allow_gift_receipt', true);

        $this->quoteAddressMock->expects($this->at(19))->method('getData')->with('gw_base_price_incl_tax')
            ->willReturn($gwBasePriceInclTax);
        $this->orderMock->expects($this->at(2))->method('setData')->with('gw_base_price_incl_tax', $gwBasePriceInclTax);

        $this->subject->execute($this->observerMock);
    }
}
