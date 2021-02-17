<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\Cart;
use Magento\Payment\Model\Cart\SalesModel\SalesModelInterface;
use Magento\Reward\Observer\AddPaymentRewardItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddPaymentRewardItemTest extends TestCase
{
    /** @var AddPaymentRewardItem */
    protected $model;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $eventMock;

    /**
     * @var ObjectManager
     */
    protected $_objectManagerHelper;

    protected function setUp(): void
    {
        $this->_objectManagerHelper = new ObjectManager($this);
        $this->model = $this->_objectManagerHelper->getObject(
            AddPaymentRewardItem::class
        );
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCart', 'getInvoice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observerMock = $this->createMock(Observer::class);
        $this->observerMock->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
    }

    /**
     * @param float $amount
     * @dataProvider addPaymentRewardItemDataProvider
     */
    public function testAddPaymentRewardItem($amount)
    {
        $salesModel = $this->getMockForAbstractClass(
            SalesModelInterface::class
        );
        $salesModel->expects($this->once())
            ->method('getDataUsingMethod')
            ->with('base_reward_currency_amount')
            ->willReturn($amount);
        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())->method('getSalesModel')->willReturn($salesModel);
        if (abs($amount) > 0.0001) {
            $cart->expects($this->once())->method('addDiscount')->with(abs($amount));
        } else {
            $cart->expects($this->never())->method('addDiscount');
        }
        $this->eventMock->expects($this->once())->method('getCart')->willReturn($cart);
        $this->model->execute($this->observerMock);
    }

    public function addPaymentRewardItemDataProvider()
    {
        return [[0.0], [0.1], [-0.1]];
    }
}
