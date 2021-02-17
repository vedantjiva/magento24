<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\Entity\ScopeResolver;

/**
 * Class AttributeCopier
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributeCopier
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ScopeResolver
     */
    private $scopeResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * AttributeCopier constructor.
     *
     * @param MetadataPool $metadataPool
     * @param ScopeResolver $scopeResolver
     * @param ResourceConnection $resourceConnection
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        MetadataPool $metadataPool,
        ScopeResolver $scopeResolver,
        ResourceConnection $resourceConnection,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->metadataPool = $metadataPool;
        $this->scopeResolver = $scopeResolver;
        $this->resourceConnection = $resourceConnection;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get entity attributes
     *
     * @param string $entityType
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     * @throws \Exception
     */
    protected function getAttributes($entityType)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $searchResult = $this->attributeRepository->getList(
            $metadata->getEavEntityType(),
            $this->searchCriteriaBuilder->create()
        );
        return $searchResult->getItems();
    }

    /**
     * Returns link id for requested update
     *
     * @param EntityMetadataInterface $metadata
     * @param string $entityId
     * @param string $createdIn
     * @return string
     * @throws \Zend_Db_Select_Exception
     */
    private function getLinkId(EntityMetadataInterface $metadata, $entityId, $createdIn)
    {
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $select = $connection->select();
        $select->from(['t' => $metadata->getEntityTable()], [$metadata->getLinkField()])
            ->where('t.' . $metadata->getIdentifierField() . ' = ?', $entityId)
            ->where('t.created_in <= ?', $createdIn)
            ->order('t.created_in DESC')
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        return $connection->fetchOne($select);
    }

    /**
     * Copy attributes for staging
     *
     * @param string $entityType
     * @param array $entityData
     * @param string $from
     * @param string $to
     * @return bool
     * @throws \Exception
     */
    public function copy($entityType, $entityData, $from, $to)
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $entityId = $entityData[$metadata->getIdentifierField()];
        $fromRowId = $this->getLinkId($metadata, $entityId, $from);
        $toRowId = $this->getLinkId($metadata, $entityId, $to);
        $attributes = $this->getAttributes($entityType);
        $attributeTables = [];
        foreach ($attributes as $attribute) {
            if (!$attribute->isStatic()) {
                $attributeTables[] = $attribute->getBackend()->getTable();
            }
        }
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $attributeTables = array_unique($attributeTables);
        foreach ($attributeTables as $attributeTable) {
            $select = $connection->select()
                ->from($attributeTable, '')
                ->where($metadata->getLinkField() . ' = ?', $fromRowId);
            $insertColumns = [
                'attribute_id' => 'attribute_id',
                'store_id' => 'store_id',
                $metadata->getLinkField() => new \Zend_Db_Expr($toRowId),
                'value' => 'value'
            ];
            $select->columns($insertColumns);
            $query = $select->insertFromSelect($attributeTable, array_keys($insertColumns));
            $connection->query($query);
        }

        $this->copyMediaGalleryRecords($connection, $metadata, $fromRowId, $toRowId);
        return true;
    }

    /**
     * Copy existing gallery items for staging
     *
     * @param AdapterInterface $connection
     * @param EntityMetadataInterface $metadata
     * @param int $fromRowId
     * @param int $toRowId
     */
    private function copyMediaGalleryRecords(
        AdapterInterface $connection,
        EntityMetadataInterface $metadata,
        int $fromRowId,
        int $toRowId
    ): void {
        $select = $connection->select();

        $galleryValueToEntityTable = $this->resourceConnection->getTableName(Gallery::GALLERY_VALUE_TO_ENTITY_TABLE);
        $galleryTable = $this->resourceConnection->getTableName(Gallery::GALLERY_TABLE);
        $galleryValueTable = $this->resourceConnection->getTableName(Gallery::GALLERY_VALUE_TABLE);

        $select->from($galleryValueToEntityTable)
            ->where($metadata->getLinkField() . ' = ?', $fromRowId);
        $ids = $connection->fetchCol($select);

        $select = $connection->select();
        $select->from($galleryTable)
            ->where('value_id IN (?)', $ids);

        $rows = $connection->fetchAll($select);
        foreach ($rows as $row) {
            $oldValueId = $row['value_id'];
            unset($row['value_id']);
            $connection->insert($galleryTable, $row);
            $newValueId = $connection->lastInsertId();
            $connection->insert(
                $galleryValueToEntityTable,
                [
                    'value_id' => $newValueId,
                    $metadata->getLinkField() => $toRowId,
                ]
            );
            $select = $connection->select();
            $select->from($galleryValueTable)
                ->where($metadata->getLinkField() . ' = ?', $fromRowId)
                ->where('value_id = ?', $oldValueId);
            $rows2 = $connection->fetchAll($select);
            foreach ($rows2 as $row2) {
                unset($row2['record_id']);
                $row2['row_id'] = $toRowId;
                $row2['value_id'] = $newValueId;
                $connection->insert($galleryValueTable, $row2);
            }
        }
    }
}
