<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Adminhtml\Cms\Page\Edit\Tab;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Cms Page Edit Hierarchy Tab Block
 */
class Hierarchy extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Array of nodes for tree
     *
     * @var array|null
     */
    protected $_nodes = null;

    /**
     * Cms hierarchy
     *
     * @var \Magento\VersionsCms\Helper\Hierarchy
     */
    protected $_cmsHierarchy;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory
     */
    protected $_nodeCollectionFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $_jsonDecoder;

    /**
     * @var string
     */
    protected $_template = 'page/tab/hierarchy.phtml';

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory $nodeCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy,
        \Magento\Framework\Registry $registry,
        \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory $nodeCollectionFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_jsonDecoder = $jsonDecoder;
        $this->_jsonEncoder = $jsonEncoder;
        $this->_coreRegistry = $registry;
        $this->_cmsHierarchy = $cmsHierarchy;
        $this->_nodeCollectionFactory = $nodeCollectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current page instance
     *
     * @return \Magento\Cms\Model\Page
     */
    public function getPage()
    {
        return $this->_coreRegistry->registry('cms_page');
    }

    /**
     * Retrieve Hierarchy JSON string
     *
     * @return string
     */
    public function getNodesJson()
    {
        return $this->_jsonEncoder->encode($this->getNodes());
    }

    /**
     * Prepare nodes data from DB  all from session if error occurred.
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getNodes()
    {
        if ($this->_nodes === null) {
            $this->_nodes = [];
            $data = null;
            try {
                $jsonData = $this->getPage()->getNodesData();
                if ($jsonData) {
                    $data = $this->_jsonDecoder->decode($jsonData);
                }
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
            } catch (\Zend_Json_Exception $e) {
                // continue and use the collection to get the node data
            }

            /** @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection $collection */
            $collection = $this->_nodeCollectionFactory
                ->create()
                ->joinCmsPage()
                ->setOrderByLevel()
                ->joinPageExistsNodeInfo(
                    $this->getPage()
                );

            if (is_array($data)) {
                foreach ($data as $v) {
                    if (isset($v['page_exists'])) {
                        $pageExists = (bool)$v['page_exists'];
                    } else {
                        $pageExists = false;
                    }
                    $node = [
                        'node_id' => $v['node_id'],
                        'parent_node_id' => $v['parent_node_id'],
                        'label' => $v['label'],
                        'page_exists' => $pageExists,
                        'page_id' => $v['page_id'],
                        'current_page' => (bool)$v['current_page'],
                    ];
                    $item = $collection->getItemById($v['node_id']);
                    if ($item) {
                        $node['assigned_to_stores'] = $this->getPageStoreIds($item);
                    } else {
                        $node['assigned_to_stores'] = [];
                    }

                    $this->_nodes[] = $node;
                }
            } else {
                foreach ($collection as $item) {
                    if ($item->getLevel() == \Magento\VersionsCms\Model\Hierarchy\Node::NODE_LEVEL_FAKE) {
                        continue;
                    }
                    /* @var $item \Magento\VersionsCms\Model\Hierarchy\Node */
                    $node = [
                        'node_id' => $item->getId(),
                        'parent_node_id' => $item->getParentNodeId(),
                        'label' => $item->getLabel(),
                        'store_label' => $this->getNodeStoreName((int)$item->getScopeId()),
                        'page_exists' => (bool)$item->getPageExists(),
                        'page_id' => $item->getPageId(),
                        'current_page' => (bool)$item->getCurrentPage(),
                        'assigned_to_stores' => $this->getPageStoreIds($item),
                    ];
                    $this->_nodes[] = $node;
                }
            }
        }
        return $this->_nodes;
    }

    /**
     * Return store name for node by scope_id
     *
     * @param int $scopeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getNodeStoreName(int $scopeId)
    {
        $scope = $this->storeManager->getStore($scopeId);
        if (!$scope->getId()) {
            return 'All Store Views';
        }
        return $scope->getName();
    }

    /**
     * Return page store ids.
     *
     * @param object $node
     * @return array
     */
    public function getPageStoreIds($node)
    {
        if (!$node->getPageId() || !$node->getPageInStores()) {
            return [];
        }
        return explode(',', $node->getPageInStores());
    }

    /**
     * Forced nodes setter
     *
     * @param array $nodes New nodes array
     * @return $this
     */
    public function setNodes($nodes)
    {
        if (is_array($nodes)) {
            $this->_nodes = $nodes;
        }

        return $this;
    }

    /**
     * Retrieve ids of selected nodes from two sources.
     * First is from prepared data from DB.
     * Second source is data from page model in case we had error.
     *
     * @return string
     */
    public function getSelectedNodeIds()
    {
        if (!$this->getPage()->hasData('node_ids')) {
            $ids = [];

            foreach ($this->getNodes() as $node) {
                if (isset($node['page_exists']) && $node['page_exists']) {
                    $ids[] = $node['node_id'];
                }
            }
            return implode(',', $ids);
        }

        return $this->getPage()->getData('node_ids');
    }

    /**
     * Prepare json string with current page data
     *
     * @return string
     */
    public function getCurrentPageJson()
    {
        $title = $this->_escaper->escapeHtml($this->getPage()->getTitle());
        $data = ['label' => $title, 'id' => $this->getPage()->getId()];

        return $this->_jsonEncoder->encode($data);
    }

    /**
     * Retrieve Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Hierarchy');
    }

    /**
     * Retrieve Tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Hierarchy');
    }

    /**
     * Check is can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        if (!$this->getPage()->getId() || !$this->_cmsHierarchy->isEnabled() || !$this->_authorization->isAllowed(
            'Magento_VersionsCms::hierarchy'
        )
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check tab is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
