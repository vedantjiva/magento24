<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\Model\Entity\RepositoryFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Staging\Model\ResourceModel\Db\DeleteObsoleteEntities;
use Magento\Staging\Model\ResourceModel\Db\GetNotIndexedEntities;

/**
 * Class StagingApplier
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StagingApplier
{
    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var Config
     */
    protected $scopeConfigCache;

    /**
     * @var CacheContext
     */
    protected $cacheContext;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var VersionHistoryInterface
     */
    protected $versionHistory;

    /**
     * @var \Magento\Staging\Model\StagingList
     */
    protected $stagingList;

    /**
     * @var DeleteObsoleteEntities
     */
    protected $deleteObsoleteEntities;

    /**
     * @var ResourceModel\Db\GetNotIndexedEntities
     */
    protected $getNotIndexedEntities;

    /**
     * @var CacheInterface
     */
    protected $cacheManager;

    /**
     * @var RetrieverPool
     */
    private $retrieverPool;

    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @var StagingApplierInterface[]
     */
    protected $appliers;

    /**
     * @param UpdateRepositoryInterface $updateRepository
     * @param Config $scopeConfigCache
     * @param CacheContext $cacheContext
     * @param ManagerInterface $eventManager
     * @param VersionHistoryInterface $versionHistory
     * @param StagingList $stagingList
     * @param DeleteObsoleteEntities $deleteObsoleteEntities
     * @param GetNotIndexedEntities $getNotIndexedEntities
     * @param CacheInterface $cacheManager
     * @param RetrieverPool $retrieverPool
     * @param RepositoryFactory $repositoryFactory
     * @param StagingApplierInterface[] $appliers
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository,
        Config $scopeConfigCache,
        CacheContext $cacheContext,
        ManagerInterface $eventManager,
        VersionHistoryInterface $versionHistory,
        StagingList $stagingList,
        DeleteObsoleteEntities $deleteObsoleteEntities,
        GetNotIndexedEntities $getNotIndexedEntities,
        CacheInterface $cacheManager,
        RetrieverPool $retrieverPool,
        RepositoryFactory $repositoryFactory,
        $appliers = []
    ) {
        $this->updateRepository = $updateRepository;
        $this->scopeConfigCache = $scopeConfigCache;
        $this->cacheContext = $cacheContext;
        $this->eventManager = $eventManager;
        $this->versionHistory = $versionHistory;
        $this->stagingList = $stagingList;
        $this->deleteObsoleteEntities = $deleteObsoleteEntities;
        $this->getNotIndexedEntities = $getNotIndexedEntities;
        $this->cacheManager = $cacheManager;
        $this->appliers = $appliers;
        $this->retrieverPool = $retrieverPool;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $currentVersionId = $this->updateRepository->getVersionMaxIdByTime(strtotime('now'));
        if (!empty($currentVersionId) && $currentVersionId != $this->versionHistory->getCurrentId()) {
            $oldVersionId = $this->versionHistory->getCurrentId();
            $this->versionHistory->setCurrentId($currentVersionId);
            $this->scopeConfigCache->clean();
            foreach ($this->stagingList->getEntityTypes() as $entityType) {
                if (isset($this->appliers[$entityType])) {
                    $entityIds = $this->getNotIndexedEntities->execute(
                        $entityType,
                        $oldVersionId,
                        $currentVersionId
                    );
                    $this->appliers[$entityType]->execute($entityIds);
                    foreach ($entityIds as $entityId) {
                        $this->repositoryFactory->create($entityType)->save(
                            $this->retrieverPool->getRetriever($entityType)->getEntity($entityId)
                        );
                    }
                }
            }
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
            $this->cacheManager->clean($this->cacheContext->getIdentities());
            $this->deleteObsoleteEntities($currentVersionId);
        }
    }

    /**
     * Remove obsolete entities
     *
     * @param string $currentVersionId
     * @return void
     */
    protected function deleteObsoleteEntities($currentVersionId)
    {
        foreach ($this->stagingList->getEntityTypes() as $entityType) {
            $this->deleteObsoleteEntities->execute(
                $entityType,
                $currentVersionId,
                $this->versionHistory->getMaximumInDB()
            );
        }
    }
}
