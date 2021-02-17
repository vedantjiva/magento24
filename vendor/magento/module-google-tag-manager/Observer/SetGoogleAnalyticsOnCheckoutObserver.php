<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Set Gtag on checkout observer
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SetGoogleAnalyticsOnCheckoutObserver implements ObserverInterface
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\ViewInterface
     */
    protected $view;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\ViewInterface $view
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ViewInterface $view
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
        $this->view = $view;
    }

    /**
     * Adds to checkout shipping address step and review step GA block with related data
     *
     * Fired by controller_action_postdispatch_checkout event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }
        /** @var \Magento\Checkout\Controller\Onepage $controllerAction */
        $controllerAction = $observer->getEvent()->getControllerAction();
        $action = $controllerAction->getRequest()->getActionName();
        $body = [];
        switch ($action) {
            case 'saveBilling':
                $encodedBody = $controllerAction->getResponse()->getBody();
                if ($encodedBody) {
                    $body = $this->jsonHelper->jsonDecode($encodedBody);
                }

                if ($body['goto_section'] == 'shipping') {
                    $shippingBlock = $controllerAction->getLayout()
                        ->createBlock(\Magento\GoogleTagManager\Block\ListJson::class)
                        ->setTemplate('Magento_GoogleTagManager::checkout/step.phtml')
                        ->setStepName('shipping');
                    $body['update_section']['name'] = 'shipping';
                    $body['update_section']['html'] = '<div id="checkout-shipping-load"></div>'
                        . $shippingBlock->toHtml();
                    $controllerAction->getResponse()->setBody($this->jsonHelper->jsonEncode($body));
                }
                break;
        }

        return $this;
    }
}
