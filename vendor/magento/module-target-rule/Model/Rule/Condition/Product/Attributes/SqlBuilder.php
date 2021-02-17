<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Rule\Condition\Product\Attributes;

use Magento\Rule\Model\Condition\Product\AbstractProduct as ProductCondition;
use Magento\Store\Model\Store;
use Magento\Framework\DB\Select;

/**
 * Target rule SQL builder is used to construct SQL conditions for 'matching products'.
 */
class SqlBuilder
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Index
     */
    protected $indexResource;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     * @param \Magento\TargetRule\Model\ResourceModel\Index $indexResource
     */
    public function __construct(
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\TargetRule\Model\ResourceModel\Index $indexResource
    ) {
        $this->metadataPool = $metadataPool;
        $this->indexResource = $indexResource;
    }

    /**
     * Generate WHERE clause based on provided condition.
     *
     * @param ProductCondition $condition
     * @param array $bind
     * @param int|null $storeId
     * @param Select|null $select
     * @return bool|\Zend_Db_Expr
     */
    public function generateWhereClause(
        ProductCondition $condition,
        &$bind = [],
        $storeId = null,
        Select $select = null
    ) {
        $select = $select ?: $this->indexResource->getConnection()->select();

        if ($condition->getAttribute() == 'category_ids') {
            $select->from(
                $this->indexResource->getTable('catalog_category_product'),
                'COUNT(*)'
            )->where(
                'product_id=e.entity_id'
            );
            return $this->addCategoryIdsCondition($select, $condition, $bind);
        }
        $where = $this->addAttributeCondition($select, $condition, $bind, $storeId);
        return false !== $where ? new \Zend_Db_Expr($where) : false;
    }

    /**
     * Modify conditions for collection with category_ids attribute
     *
     * - For conditions like (e.g "Product Category contains Constant Value 6"),
     * the generated SQL will look like the following:
     * (SELECT COUNT(*) FROM `catalog_category_product` WHERE ... AND `category_id` IN ('6')) > 0
     * - For conditions like (e.g "Product Category does not contain  Constant Value 6"),
     * the generated SQL will look like the following:
     * (SELECT COUNT(*) FROM `catalog_category_product` WHERE ... AND `category_id` IN ('6')) = 0
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @return \Zend_Db_Expr
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function addCategoryIdsCondition(
        $select,
        $condition,
        &$bind
    ) {
        $operator = $this->getCategoryIdsConditionOperator($condition->getOperator());
        $value = $this->indexResource->bindArrayOfIds($condition->getValue());
        if ($operator === '!()') {
            $where = $this->indexResource->getOperatorCondition('category_id', '()', $value);
            $select->where($where);
            return new \Zend_Db_Expr(sprintf('(%s) = 0', $select->assemble()));
        }
        $where = $this->indexResource->getOperatorCondition('category_id', $operator, $value);
        $select->where($where);
        return new \Zend_Db_Expr(sprintf('(%s) > 0', $select->assemble()));
    }

    /**
     * Get operator for category_ids attribute condition
     *
     * @param string $conditionOperator
     * @return string
     */
    public function getCategoryIdsConditionOperator(string $conditionOperator): string
    {
        if (in_array($conditionOperator, ['!{}', '!='])) {
            $operator = '!()';
        } elseif (in_array($conditionOperator, ['{}', '=='])) {
            $operator = '()';
        } else {
            $operator = $conditionOperator;
        }
        return $operator;
    }

    /**
     * Add condition based on product attribute.
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @param int $storeId
     * @return array|bool|string
     */
    protected function addAttributeCondition(
        $select,
        $condition,
        &$bind,
        $storeId
    ) {
        $attribute = $condition->getAttributeObject();

        if (!$attribute) {
            return false;
        }
        $attributeCode = $condition->getAttribute();
        $operator = $condition->getOperator();
        if ($attribute->isStatic()) {
            $field = "e.{$attributeCode}";
            if ($this->shouldUseBind($condition)) {
                $where = $this->indexResource->getOperatorBindCondition($field, $attributeCode, $operator, $bind);
            } else {
                $value = $this->normalizeConditionValue($condition);
                $where = $this->indexResource->getOperatorCondition($field, $operator, $value);
            }
            $where = sprintf('(%s)', $where);
        } elseif ($attribute->isScopeGlobal()) {
            $where = $this->addGlobalAttributeConditions(
                $select,
                $condition,
                $bind
            );
        } else {
            $where = $this->addScopedAttributeConditions(
                $select,
                $condition,
                $bind,
                $storeId
            );
        }
        return $where;
    }

    /**
     * Add condition based on attribute with store or website scope.
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @param int $storeId
     * @return string
     */
    private function addScopedAttributeConditions(
        $select,
        $condition,
        array &$bind,
        $storeId
    ) {
        $valueExpr = $this->indexResource->getConnection()->getCheckSql(
            'attr_s.value_id > 0',
            'attr_s.value',
            'attr_d.value'
        );
        $attribute = $condition->getAttributeObject();
        $table = $attribute->getBackendTable();
        $entityFieldName = $this->metadataPool
            ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();

        $select->from(
            ['attr_d' => $table],
            'COUNT(*)'
        )->joinLeft(
            ['attr_s' => $table],
            $this->indexResource->getConnection()->quoteInto(
                sprintf(
                    'attr_s.%s = attr_d.%s AND attr_s.attribute_id = attr_d.attribute_id AND attr_s.store_id=?',
                    $entityFieldName,
                    $entityFieldName
                ),
                $storeId
            ),
            []
        )->where(
            sprintf('attr_d.%s = e.%s', $entityFieldName, $entityFieldName)
        )->where(
            'attr_d.attribute_id=?',
            $attribute->getId()
        )->where(
            'attr_d.store_id=?',
            Store::DEFAULT_STORE_ID
        );
        if ($this->shouldUseBind($condition)) {
            $select->where(
                $this->indexResource->getOperatorBindCondition(
                    $valueExpr,
                    $condition->getAttribute(),
                    $condition->getOperator(),
                    $bind
                )
            );
        } else {
            $select->where(
                $this->indexResource->getOperatorCondition(
                    $valueExpr,
                    $condition->getOperator(),
                    $condition->getValue()
                )
            );
        }

        $where = sprintf('(%s) > 0', $select);
        return $where;
    }

    /**
     * Add condition based on attribute with global scope.
     *
     * The 'catalog_product_relation' table added to allow select parent product entities by child products.
     *
     * The produced part of SELECT query looks like this:
     *
     * e.row_id IN (
     *   SELECT IFNULL(relation.parent_id, table.row_id)
     *   FROM `catalog_product_entity_int` AS `table`
     *   LEFT JOIN `catalog_product_relation` AS `relation` ON table.row_id=relation.child_id
     *   WHERE (table.attribute_id='93') AND (table.store_id=0) AND (`table`.`value`='15')
     * )
     *
     * @param Select $select
     * @param ProductCondition $condition
     * @param array $bind
     * @return string
     */
    private function addGlobalAttributeConditions(
        $select,
        $condition,
        array &$bind
    ) {
        $linkField = $this->metadataPool
            ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->getLinkField();

        $attribute = $condition->getAttributeObject();
        $select->from(
            ['table' => $attribute->getBackendTable()],
            $this->indexResource->getConnection()->getIfNullSql(
                'relation.parent_id',
                'table.' . $linkField
            )
        )->joinLeft(
            ['cpe' => $this->indexResource->getTable('catalog_product_entity')],
            'cpe.' . $linkField . ' = table.' . $linkField,
            []
        )->joinInner(
            ['relation' => $this->indexResource->getTable('catalog_product_relation')],
            'cpe.entity_id = relation.child_id',
            []
        )
        ->where('table.attribute_id=?', $attribute->getId())
        ->where('table.store_id=?', Store::DEFAULT_STORE_ID);

        if ($this->shouldUseBind($condition)) {
            $select->where(
                $this->indexResource->getOperatorBindCondition(
                    'table.value',
                    $condition->getAttribute(),
                    $condition->getOperator(),
                    $bind
                )
            );
        } else {
            $select->where(
                $this->indexResource->getOperatorCondition(
                    'table.value',
                    $condition->getOperator(),
                    $condition->getValue()
                )
            );
        }

        $connection = $this->indexResource->getConnection();
        $selectChildren = $connection->select()
            ->from(
                ['table' => $attribute->getBackendTable()],
                'table.' . $linkField
            )
            ->where('table.attribute_id=?', $attribute->getId())
            ->where('table.store_id=?', Store::DEFAULT_STORE_ID);

        if ($this->shouldUseBind($condition)) {
            $selectChildren->where(
                $this->indexResource->getOperatorBindCondition(
                    'table.value',
                    $condition->getAttribute(),
                    $condition->getOperator(),
                    $bind
                )
            );
        } else {
            $selectChildren->where(
                $this->indexResource->getOperatorCondition(
                    'table.value',
                    $condition->getOperator(),
                    $condition->getValue()
                )
            );
        }

        $resultSelect = $this->indexResource->getConnection()->select()->union(
            [$select, $selectChildren],
            \Magento\Framework\DB\Select::SQL_UNION
        );

        /** MySQL Subquery with IN statement performance optimizer */
        $selectWrapper = $this->indexResource->getConnection()->select()->from($resultSelect);
        return 'e.' . $linkField . ' IN (' . $selectWrapper . ')';
    }

    /**
     * Check if binding should be used for specified condition.
     *
     * @param ProductCondition $condition
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldUseBind($condition)
    {
        return false;
    }

    /**
     * Normalize condition value to make it compatible with SQL operator associated with the condition.
     *
     * @param ProductCondition $condition
     * @return mixed
     */
    protected function normalizeConditionValue($condition)
    {
        return $condition->getValue();
    }
}
