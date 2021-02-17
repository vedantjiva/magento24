<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Controller\Cart;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Reward\Helper\Data;

/**
 * Remove Reward Points payment
 *
 *
 */
class Remove extends Action implements HttpPostActionInterface
{
    /**
     * Dispatch request
     *
     * Only logged in users can use this functionality,
     * this function checks if user is logged in before all other actions
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->authenticate()) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * Remove Reward Points payment from current quote
     *
     * @return void|ResponseInterface
     */
    public function execute()
    {
        if (!$this->_objectManager->get(
            Data::class
        )->isEnabledOnFront() || !$this->_objectManager->get(
            Data::class
        )->getHasRates()
        ) {
            return $this->_redirect('customer/account/');
        }

        $quote = $this->_objectManager->get(Session::class)->getQuote();

        if ($quote->getUseRewardPoints()) {
            $quote->setUseRewardPoints(false)->collectTotals()->save();
            $this->messageManager->addSuccess(__('You removed the reward points from this order.'));
        } else {
            $this->messageManager->addError(__('Reward points will not be used in this order.'));
        }

        $referer = $this->getRequest()->getParam('_referer');

        if ($referer === 'payment') {
            return $this->_redirect('checkout', ['_fragment' => 'payment']);
        }

        return $this->_redirect('checkout/cart');
    }
}
