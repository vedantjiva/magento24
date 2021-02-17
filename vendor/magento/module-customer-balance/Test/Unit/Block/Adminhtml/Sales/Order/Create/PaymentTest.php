<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\PaymentTest
 */
namespace Magento\CustomerBalance\Test\Unit\Block\Adminhtml\Sales\Order\Create;

use Magento\Backend\Model\Session\Quote;
use Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment;
use Magento\CustomerBalance\Helper\Data;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    /**
     * Tested class
     *
     * @var string
     */
    protected $_className;

    /**
     * @var BalanceFactory
     */
    protected $_balanceFactoryMock;

    /**
     * @var Quote
     */
    protected $_sessionQuoteMock;

    /**
     * @var Create
     */
    protected $_orderCreateMock;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManagerMock;

    /**
     * @var StoreManagerInterface
     */
    protected $_helperMock;

    /**
     * @var StoreManagerInterface
     */
    protected $_balanceInstance;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeMock;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    protected $priceCurrency;

    /**
     * initialize arguments for construct
     */
    protected function setUp(): void
    {
        $this->priceCurrency = $this->getMockBuilder(
            PriceCurrencyInterface::class
        )->getMock();
        $this->_balanceInstance = $this->getMockBuilder(Balance::class)
            ->addMethods(['setCustomerId', 'setWebsiteId'])
            ->onlyMethods(['getAmount', 'loadByCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_balanceFactoryMock = $this->createPartialMock(
            BalanceFactory::class,
            ['create']
        );
        $this->_balanceFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->_balanceInstance
        );
        $this->_balanceInstance->expects(
            $this->any()
        )->method(
            'setCustomerId'
        )->willReturn(
            $this->_balanceInstance
        );
        $this->_balanceInstance->expects(
            $this->any()
        )->method(
            'setWebsiteId'
        )->willReturn(
            $this->_balanceInstance
        );
        $this->_balanceInstance->expects(
            $this->any()
        )->method(
            'loadByCustomer'
        )->willReturn(
            $this->_balanceInstance
        );
        $this->_sessionQuoteMock = $this->createMock(Quote::class);
        $this->_orderCreateMock = $this->createMock(Create::class);
        $this->_storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->addMethods(['getCustomerId'])
            ->onlyMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_orderCreateMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);
        $quoteMock->expects($this->any())->method('getCustomerId')->willReturn(true);
        $quoteMock->expects($this->any())->method('getStoreId')->willReturn(true);
        $this->_helperMock = $this->createMock(Data::class);

        $this->_storeMock = $this->createMock(Store::class);
        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getStore'
        )->willReturn(
            $this->_storeMock
        );

        $helper = new ObjectManager($this);
        $this->_className = $helper->getObject(
            Payment::class,
            [
                'storeManager' => $this->_storeManagerMock,
                'sessionQuote' => $this->_sessionQuoteMock,
                'orderCreate' => $this->_orderCreateMock,
                'priceCurrency' => $this->priceCurrency,
                'balanceFactory' => $this->_balanceFactoryMock,
                'customerBalanceHelper' => $this->_helperMock
            ]
        );
    }

    /**
     * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment::getBalance()
     * Check case when customer balance is disabled
     */
    public function testGetBalanceNotEnabled()
    {
        $this->_helperMock->expects($this->once())->method('isEnabled')->willReturn(false);

        $result = $this->_className->getBalance();
        $this->assertEquals(0.0, $result);
    }

    /**
     * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment::getBalance()
     * Test if need to use converting price by current currency rate
     */
    public function testGetBalanceConvertPrice()
    {
        $this->_helperMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $amount = rand(1, 100);
        $convertedAmount = $amount * 2;

        $this->_balanceInstance->expects($this->once())->method('getAmount')->willReturn($amount);
        $this->priceCurrency->expects($this->once())
            ->method('convert')
            ->with($amount)
            ->willReturn($convertedAmount);
        $result = $this->_className->getBalance(true);
        $this->assertEquals($convertedAmount, $result);
    }

    /**
     * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment::getBalance()
     * No additional cases, standard behaviour
     */
    public function testGetBalanceAmount()
    {
        $amount = rand(1, 1000);
        $this->_helperMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->_balanceInstance->expects($this->once())->method('getAmount')->willReturn($amount);
        $result = $this->_className->getBalance();
        $this->assertEquals($amount, $result);
    }
}
