<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\CustomerSegment\Controller\Adminhtml\Index;

class Match extends Index implements HttpGetActionInterface
{
    /**
     * Match segment customers action
     *
     * @return void
     */
    public function execute()
    {
        try {
            $model = $this->_initSegment();
            if ($model->getApplyTo() != \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS) {
                $model->matchCustomers();
            }
            $this->messageManager->addSuccess(__('Segment Customers matching is added to messages queue.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('customersegment/*/');
            return;
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Segment Customers matching error.'));
            $this->_redirect('customersegment/*/');
            return;
        }
        $this->_redirect('customersegment/*/edit', ['id' => $model->getId(), 'active_tab' => 'customers_tab']);
    }
}
