<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\ScopeResolver;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node as HierarchyNode;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;

/**
 * Create and delete nodes after cms page save
 */
class CmsPageSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Hierarchy
     */
    protected $cmsHierarchy;

    /**
     * @var HierarchyNode
     */
    protected $hierarchyNode;

    /**
     * @var Node
     */
    protected $hierarchyNodeResource;

    /**
     * @var CollectionFactory
     */
    private $nodeCollectionFactory;

    /**
     * @var ScopeResolver
     */
    private $scopeResolver;

    /**
     * @param Hierarchy $cmsHierarchy
     * @param HierarchyNode $hierarchyNode
     * @param Node $hierarchyNodeResource
     * @param CollectionFactory $nodeCollectionFactory
     * @param ScopeResolver $scopeResolver
     */
    public function __construct(
        Hierarchy $cmsHierarchy,
        HierarchyNode $hierarchyNode,
        Node $hierarchyNodeResource,
        CollectionFactory $nodeCollectionFactory,
        ScopeResolver $scopeResolver
    ) {
        $this->cmsHierarchy = $cmsHierarchy;
        $this->hierarchyNode = $hierarchyNode;
        $this->hierarchyNodeResource = $hierarchyNodeResource;
        $this->nodeCollectionFactory = $nodeCollectionFactory;
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * Process extra data after cms page saved
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var Page $page */
        $page = $observer->getEvent()->getObject();

        if (!$this->cmsHierarchy->isEnabled()) {
            return $this;
        }

        // Rebuild URL rewrites if page has changed for identifier
        if ($page->dataHasChangedFor('identifier')) {
            $this->hierarchyNode->updateRewriteUrls($page);
        }

        $this->appendPageToNodes($page);

        /**
         * Update sort order for nodes in parent nodes which have current page as child
         */
        foreach ($page->getNodesSortOrder() as $nodeId => $value) {
            $this->hierarchyNodeResource->updateSortOrder($nodeId, $value);
        }

        return $this;
    }

    /**
     * Append page to selected nodes. Removing page nodes with wrong scope after changing store in "Page in Websites"
     *
     * @param Page $page
     * @return $this
     * @throws LocalizedException
     */
    private function appendPageToNodes(Page $page)
    {
        $nodes = $page->getAppendToNodes();
        $parentNodes = $this->getParentNodes($nodes, $page);
        $pageData = ['page_id' => $page->getId(), 'identifier' => null, 'label' => null];
        $removeFromNodes = [];
        $scopeIds = [];
        foreach ($parentNodes as $parentNode) {
            /* @var $parentNode HierarchyNode */
            if (!isset($nodes[$parentNode->getId()])) {
                //Delete node after uncheck checkbox
                $removeFromNodes[] = $parentNode->getId();
                $scopeIds[] = $parentNode->getScopeId();
                continue;
            }
            $nodeScopeId = (int)$parentNode->getScopeId();

            if (!$this->isBelongsToNodeScope($parentNode->getScope(), $nodeScopeId, (array)$page->getStoreId())) {
                //If parent node scope_id assigned to store which not in "Page In Websites" - delete node
                $scopeIds[] = $nodeScopeId;
                $removeFromNodes[] = $parentNode->getId();
                continue;
            }

            $requestUrl = $parentNode->getIdentifier() . '/' . $page->getIdentifier();
            if ($this->isNodeExist($requestUrl, $nodeScopeId, (int)$parentNode->getId(), (int)$page->getId())) {
                throw new LocalizedException(
                    __(
                        'This page cannot be assigned to node, because a node or page with'
                        . ' the same URL Key already exists in this tree part.'
                    )
                );
            }
            if (!$this->isNodeExist($requestUrl, $nodeScopeId, (int)$parentNode->getId())) {
                $sortOrder = $nodes[$parentNode->getId()];
                $this->createNewNode($parentNode, $pageData, $sortOrder, $page->getIdentifier());
            }
        }
        if (!empty($removeFromNodes) && $nodes !== null && !empty($scopeIds)) {
            $this->hierarchyNodeResource->removePageFromNodes($page->getId(), $removeFromNodes, $scopeIds);
        }

        return $this;
    }

    /**
     * Check if node scope is "All store view" or it is same as page scope
     *
     * @param string $nodeScope
     * @param int $nodeScopeId
     * @param array $pageStoreIds
     * @return bool
     */
    private function isBelongsToNodeScope(string $nodeScope, int $nodeScopeId, array $pageStoreIds): bool
    {
        if (empty($pageStoreIds)) {
            return false;
        }

        foreach ($pageStoreIds as $storeId) {
            $isScopeValid = $this->scopeResolver->isBelongsToScope(
                $nodeScope,
                $nodeScopeId,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($isScopeValid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create new page node
     *
     * @param HierarchyNode $parentNode
     * @param array $pageData
     * @param int $sortOrder
     * @param string $pageIdentifier
     * @return mixed
     */
    private function createNewNode(HierarchyNode $parentNode, array $pageData, int $sortOrder, string $pageIdentifier)
    {
        $newNode = clone $parentNode;
        if ($parentNode->getScopeId() !== HierarchyNode::NODE_SCOPE_DEFAULT_ID) {
            $newNode->setScope(HierarchyNode::NODE_SCOPE_STORE);
        }
        $newNode->setScopeId($parentNode->getScopeId());

        $newNode->addData(
            $pageData
        )->setParentNodeId(
            $newNode->getId()
        )->unsetData(
            $this->hierarchyNode->getIdFieldName()
        )->setLevel(
            $newNode->getLevel() + 1
        )->setSortOrder(
            $sortOrder
        )->setRequestUrl(
            $newNode->getRequestUrl() . '/' . $pageIdentifier
        )->setXpath(
            $newNode->getXpath() . '/'
        );
        $newNode->save();

        return $newNode;
    }

    /**
     * Return parent nodes collection
     *
     * @param array $nodes
     * @param Page $page
     * @return Collection
     */
    private function getParentNodes(?array $nodes, Page $page)
    {
        $nodesToFilter = ($nodes === null) ? [] : array_keys($nodes);
        $nodeCollection = $this->nodeCollectionFactory->create();
        $parentNodes = $nodeCollection->joinPageExistsNodeInfo(
            $page
        )->applyPageExistsOrNodeIdFilter(
            $nodesToFilter,
            $page
        );

        return $parentNodes;
    }

    /**
     * Check if current page node is exist
     *
     * @param string $requestUrl
     * @param int $scopeId
     * @param int $parentNodeId
     * @param int|null $currentPageId
     * @return bool
     */
    private function isNodeExist(string $requestUrl, int $scopeId, int $parentNodeId, ?int $currentPageId = null): bool
    {
        $nodeCollection = $this->nodeCollectionFactory->create();
        $nodeCollection->addFieldToFilter('request_url', $requestUrl)
            ->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('parent_node_id', $parentNodeId);

        if ($currentPageId !== null) {
            $nodeCollection->addFieldToFilter('page_id', ['neq' => $currentPageId]);
        }
        return $nodeCollection->getSize() ? true : false;
    }
}
