<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin;

use \Magento\Sales\Model\Order\Creditmemo as OrderCreditmemo;

class Creditmemo
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Backend\Model\Session $backendSession
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Backend\Model\Session $backendSession
    ) {
        $this->helper = $helper;
        $this->backendSession = $backendSession;
    }

    /**
     * @param OrderCreditmemo $subject
     * @param OrderCreditmemo $result
     * @return OrderCreditmemo
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(OrderCreditmemo $subject, $result)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $result;
        }

        $order = $result->getOrder();
        $this->backendSession->setData('googleanalytics_creditmemo_order', $order->getIncrementId());
        $this->backendSession->setData('googleanalytics_creditmemo_store_id', $result->getStoreId());
        if (abs((float)$result->getBaseGrandTotal() - (float)$order->getBaseGrandTotal()) > 0.009) {
            $this->backendSession->setData('googleanalytics_creditmemo_revenue', $result->getBaseGrandTotal());
        }
        $products = [];

        /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
        foreach ($result->getItemsCollection() as $item) {
            $qty = $item->getQty();
            if ($qty < 1) {
                continue;
            }
            $products[]= [
                'id' => $item->getSku(),
                'quantity' => $qty,
            ];
        }
        $this->backendSession->setData('googleanalytics_creditmemo_products', $products);

        return $result;
    }
}
