<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;

class CoreCopyFieldsetSalesCopyOrderShippingAddressToOrder extends AbstractObserver implements ObserverInterface
{
    /**
     * Observer for converting order shipping address to quote shipping address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_copyFieldset(
            $observer,
            self::CONVERT_ALGORITM_SOURCE_TARGET_WITH_PREFIX,
            self::CONVERT_TYPE_CUSTOMER_ADDRESS
        );

        return $this;
    }
}
