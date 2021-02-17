<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\EventFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User;

/**
 * Get event for logging with initial data service.
 */
class GetEventForLogging
{
    private const EVENT_CODE = 'login_as_customer';

    /**
     * @var EventFactory
     */
    private $eventFactory;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UserInterfaceFactory
     */
    private $userFactory;

    /**
     * @var User
     */
    private $userResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param EventFactory $eventFactory
     * @param RemoteAddress $remoteAddress
     * @param RequestInterface $request
     * @param UserInterfaceFactory $userFactory
     * @param User $userResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        EventFactory $eventFactory,
        RemoteAddress $remoteAddress,
        RequestInterface $request,
        UserInterfaceFactory $userFactory,
        User $userResource,
        StoreManagerInterface $storeManager
    ) {
        $this->eventFactory = $eventFactory;
        $this->remoteAddress = $remoteAddress;
        $this->request = $request;
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Get event with initial data.
     *
     * @param int $userId
     * @return Event
     */
    public function execute(int $userId): Event
    {
        $user = $this->userFactory->create();
        $this->userResource->load($user, $userId);

        return $this->eventFactory->create([
            'data' => [
                'ip' => $this->remoteAddress->getRemoteAddress(),
                'x_forwarded_ip' => $this->request->getServer('HTTP_X_FORWARDED_FOR'),
                'user' => $user->getUserName(),
                'info' => __('store = %1', $this->storeManager->getStore()->getCode()),
                'is_success' => 1,
                'user_id' => $userId,
                'fullaction' => "{$this->request->getRouteName()}_{$this->request->getControllerName()}" .
                    "_{$this->request->getActionName()}",
                'event_code' => self::EVENT_CODE,
            ],
        ]);
    }
}
