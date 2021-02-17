<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderAfterLoad implements ObserverInterface
{
    /**
     * @var \Magento\CustomerCustomAttributes\Model\Sales\OrderFactory
     */
    protected $orderFactory;

    /**
     * @param \Magento\CustomerCustomAttributes\Model\Sales\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\CustomerCustomAttributes\Model\Sales\OrderFactory $orderFactory
    ) {
        $this->orderFactory = $orderFactory;
    }

    /**
     * After load observer for order
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof \Magento\Framework\Model\AbstractModel) {
            /** @var $orderModel \Magento\CustomerCustomAttributes\Model\Sales\Order */
            $orderModel = $this->orderFactory->create();
            $orderModel->load($order->getId());
            $orderModel->attachAttributeData($order);
        }
        return $this;
    }
}
