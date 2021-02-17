<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Category products grid action
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class AbstractGrid extends \Magento\Catalog\Controller\Adminhtml\Category\Grid
{
    /**
     * @var string
     */
    protected $blockClass;

    /**
     * @var string
     */
    protected $blockName;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context, $resultRawFactory, $layoutFactory);
        $this->storeManager = $storeManager;
    }

    /**
     * Grid Action
     *
     * Display list of products related to current category
     *
     * @return \Magento\Framework\Controller\Result\Raw
     * @throws NotFoundException
     */
    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('store');
        $this->storeManager->setCurrentStore($this->storeManager->getStore($storeId)->getCode());

        if (!$this->blockClass || !$this->blockName) {
            throw new NotFoundException(__('Page not found.'));
        }

        $category = $this->_initCategory(true);
        if (!$category) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }

        /** @var \Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\BlockInterface $block */
        $block = $this->layoutFactory->create()->createBlock(
            $this->blockClass,
            $this->blockName
        );
        $block->setPositionCacheKey(
            $this->getRequest()->getParam(\Magento\VisualMerchandiser\Model\Position\Cache::POSITION_CACHE_KEY, false)
        );

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents(
            $block->toHtml()
        );
    }
}
