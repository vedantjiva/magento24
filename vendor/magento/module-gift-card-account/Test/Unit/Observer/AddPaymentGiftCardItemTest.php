<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Observer\AddPaymentGiftCardItem;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;
use PHPUnit\Framework\TestCase;

class AddPaymentGiftCardItemTest extends TestCase
{
    /** @var AddPaymentGiftCardItem */
    private $model;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var DataObject
     */
    private $event;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->model = $objectManagerHelper->getObject(
            AddPaymentGiftCardItem::class
        );

        $this->event = new DataObject();

        $this->observer = new Observer(['event' => $this->event]);
    }

    /**
     * @param float $amount
     * @dataProvider addPaymentGiftCardItemDataProvider
     */
    public function testAddPaymentGiftCardItem($amount)
    {
        $salesModelMock = $this->getMockForAbstractClass(
            SalesModelInterface::class
        );
        $salesModelMock->expects(
            $this->once()
        )->method(
            'getDataUsingMethod'
        )->with(
            'base_gift_cards_amount'
        )->willReturn(
            $amount
        );
        $cartMock = $this->createMock(Cart::class);
        $cartMock->expects($this->once())->method('getSalesModel')->willReturn($salesModelMock);
        if (abs($amount) > 0.0001) {
            $cartMock->expects($this->once())->method('addDiscount')->with(abs($amount));
        } else {
            $cartMock->expects($this->never())->method('addDiscount');
        }
        $this->event->setCart($cartMock);
        $this->model->execute($this->observer);
    }

    public function addPaymentGiftCardItemDataProvider()
    {
        return [[0.0], [0.1], [-0.1]];
    }
}
