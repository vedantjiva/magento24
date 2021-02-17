<?php

namespace Dotdigitalgroup\Enterprise\Model\Config\ContentType\AdditionalData\Provider;

use Magento\PageBuilder\Model\Config\ContentType\AdditionalData\ProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Backend\Model\Auth\Session;

/**
 * Provides current store ID
 */
class MagentoApiAccessToken implements ProviderInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @var Session
     */
    private $adminSession;

    /**
     * @param StoreManagerInterface $storeManager
     * @param TokenFactory $tokenFactory
     * @param Session $adminSession
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        TokenFactory $tokenFactory,
        Session $adminSession
    ) {
        $this->storeManager = $storeManager;
        $this->tokenFactory = $tokenFactory;
        $this->adminSession = $adminSession;
    }

    /**
     * @inheritdoc
     */
    public function getData(string $itemName) : array
    {
        return [$itemName => $this->getToken()];
    }

    /**
     * @return mixed
     */
    private function getToken()
    {
        $token = $this->tokenFactory->create()
            ->createAdminToken($this->adminSession->getUser()->getId());

        return $token->getToken();
    }
}
