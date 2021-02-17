<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Observer\AddPaymentCustomerBalanceItemObserver;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;
use PHPUnit\Framework\TestCase;

class AddPaymentCustomerBalanceItemObserverTest extends TestCase
{
    /** @var AddPaymentCustomerBalanceItemObserver */
    protected $model;

    /**
     * @var Observer
     */
    protected $observer;

    /**
     * @var DataObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->event = new DataObject();
        $this->observer = new Observer(['event' => $this->event]);

        $objectManagerHelper = new ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            AddPaymentCustomerBalanceItemObserver::class
        );
    }

    /**
     * @param float $amount
     * @dataProvider addPaymentCustomerBalanceItemDataProvider
     */
    public function testAddPaymentCustomerBalanceItem($amount)
    {
        $salesModel = $this->getMockForAbstractClass(SalesModelInterface::class);
        $salesModel->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('customer_balance_base_amount')
            ->willReturn($amount);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->willReturn($salesModel);
        if (abs($amount) > 0.0001) {
            $cart->expects($this->once())->method('addDiscount')->with(abs($amount));
        } else {
            $cart->expects($this->never())->method('addDiscount');
        }
        $this->event->setCart($cart);
        $this->model->execute($this->observer);
    }

    public function addPaymentCustomerBalanceItemDataProvider()
    {
        return [[0.0], [0.1], [-0.1]];
    }
}
