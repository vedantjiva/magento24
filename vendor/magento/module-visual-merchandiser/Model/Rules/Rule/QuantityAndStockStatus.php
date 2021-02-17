<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Helper class for obtaining the stock quantity of product collection
 */
class QuantityAndStockStatus extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * Applying the rules to the collection
     *
     * @param Collection $collection
     * @return void
     * @throws LocalizedException
     */
    public function applyToCollection($collection)
    {
        $collection->joinField(
            'child_id',
            $collection->getTable('catalog_product_relation'),
            'child_id',
            'parent_id=entity_id',
            null,
            'left'
        );

        $collection->joinField(
            'stock',
            $collection->getTable('cataloginventory_stock_item'),
            'qty',
            'product_id=entity_id',
            ['stock_id' => $this->getStockId()],
            'left'
        );

        $collection->joinField(
            'parent_stock',
            $collection->getTable('cataloginventory_stock_item'),
            'qty',
            'product_id=child_id',
            ['stock_id' => $this->getStockId()],
            'left'
        );

        $selectedOption = strtolower($this->_rule['value']);
        $collection->getSelect()
            ->columns(
                'IF(  SUM(`at_parent_stock`.`qty`),
                                     SUM(`at_parent_stock`.`qty`),
                                    `at_stock`.`qty`) as stock'
            )
            ->group('entity_id')
            ->having(
                'IF(SUM(`at_parent_stock`.`qty`), SUM(`at_parent_stock`.`qty`), SUM(`at_stock`.`qty`))'
                . $this->getOperatorExpression($this->_rule['operator']),
                $selectedOption
            )
            ->reset(\Magento\Framework\DB\Select::ORDER);
    }

    /**
     * Get default stock id
     *
     * @return int
     */
    protected function getStockId()
    {
        return \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID;
    }
}
