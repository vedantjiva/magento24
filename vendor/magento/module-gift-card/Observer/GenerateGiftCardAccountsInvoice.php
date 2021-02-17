<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\GiftCard\Model\AccountGenerator;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductGiftCard;
use Magento\GiftCard\Model\Giftcard;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\ScopeInterface;

/**
 * Gift cards generator observer called on invoice after save
 */
class GenerateGiftCardAccountsInvoice implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var AccountGenerator
     */
    private $accountGenerator;

    /**
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     * @param AccountGenerator $accountGenerator
     */
    public function __construct(
        ManagerInterface $eventManager,
        ScopeConfigInterface $scopeConfig,
        AccountGenerator $accountGenerator
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->accountGenerator = $accountGenerator;
    }

    /**
     * Generate gift card accounts after invoice save.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var Invoice $invoice */
        $invoice = $event->getInvoice();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $invoice->getOrder();
        $orderPaid = false;

        $requiredStatus = (int)$this->scopeConfig->getValue(
            Giftcard::XML_PATH_ORDER_ITEM_STATUS,
            ScopeInterface::SCOPE_STORE,
            $order->getStore()
        );

        if ($requiredStatus !== Item::STATUS_INVOICED || $invoice->getIsUsedForRefund()) {
            return;
        }

        if ((abs((float)$order->getBaseGrandTotal() - (float)$invoice->getBaseGrandTotal()) < 0.0001)) {
            $orderPaid = true;
        }

        /** @var Item $orderItem */
        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getProductType() !== ProductGiftCard::TYPE_GIFTCARD) {
                continue;
            }

            if ($orderPaid) {
                $qty = (int)$orderItem->getQtyInvoiced();
            } else {
                $qty = $this->getInvoicedOrderItemQty($invoice, $orderItem);
            }

            $options = $orderItem->getProductOptions();
            if ($qty > 0) {
                $options['giftcard_paid_invoice_items'][] = $orderItem->getItemId();
            }

            $this->accountGenerator->generate($orderItem, $qty, $options);
        }
    }

    /**
     * Returns order item invoiced quantity.
     *
     * @param InvoiceInterface $invoice
     * @param OrderItemInterface $orderItem
     * @return int
     */
    private function getInvoicedOrderItemQty(
        InvoiceInterface $invoice,
        OrderItemInterface $orderItem
    ): int {
        $qty = 0;
        foreach ($invoice->getItems() as $invoiceItem) {
            // check, if this order item has been paid
            if ($invoiceItem->getOrderItemId() === $orderItem->getItemId()
                && $invoice->getState() == Invoice::STATE_PAID
            ) {
                $qty = (int)$invoiceItem->getQty();
            }
        }

        return $qty;
    }
}
