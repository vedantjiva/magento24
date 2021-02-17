<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddPaymentCustomerBalanceItemObserver implements ObserverInterface
{
    /**
     * Add customer balance amount to payment discount total
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $cart \Magento\Payment\Model\Cart */
        $cart = $observer->getEvent()->getCart();
        $salesEntity = $cart->getSalesModel();
        $value = abs($salesEntity->getDataUsingMethod('customer_balance_base_amount'));
        if ($value > 0.0001) {
            $cart->addDiscount((double)$value);
        }
    }
}
