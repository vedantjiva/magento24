<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Observer;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;

/**
 * Login as customer log add to wishlist observer.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LogAddToWishlistObserver implements ObserverInterface
{
    private const ACTION = 'add_to_wishlist';

    /**
     * @var GetEventForLogging
     */
    private $getEventForLogging;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Event
     */
    private $eventResource;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface
     */
    private $getLoggedAsCustomerAdminId;

    /**
     * @param GetEventForLogging $getEventForLogging
     * @param Session $session
     * @param Event $eventResource
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     */
    public function __construct(
        GetEventForLogging $getEventForLogging,
        Session $session,
        Event $eventResource,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
    ) {
        $this->getEventForLogging = $getEventForLogging;
        $this->session = $session;
        $this->eventResource = $eventResource;
        $this->getLoggedAsCustomerAdminId = $getLoggedAsCustomerAdminId;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->getLoggedAsCustomerAdminId->execute()) {
            return;
        }
        $event = $this->getEventForLogging->execute($this->getLoggedAsCustomerAdminId->execute());
        $event->setAction(self::ACTION);
        $items = $observer->getEvent()->getItems();
        $info = '';
        foreach ($items as $item) {
            $product = $item->getProduct();
            $info .= __(
                'Product sku = %1 has been added to wishlist, ',
                $product->getSku()
            );
        }
        $info .= __(
            'customer id = %1, email = %2, ',
            $this->session->getCustomerId(),
            $this->session->getCustomer()->getEmail()
        );
        $info .= $event->getInfo();

        $event->setInfo($info);
        $this->eventResource->save($event);
    }
}
