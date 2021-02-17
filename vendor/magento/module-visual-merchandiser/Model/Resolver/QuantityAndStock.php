<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Resolver;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;

/**
 * Adminhtml Quantity and Stock status helper
 */
class QuantityAndStock extends AbstractHelper
{
    /**
     * Joins stock information
     *
     * @param Collection $collection
     * @return Collection
     * @throws LocalizedException
     */
    public function joinStock(Collection $collection): Collection
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
            'child_stock',
            $collection->getTable('cataloginventory_stock_item'),
            'qty',
            'product_id = entity_id',
            ['stock_id' => Stock::DEFAULT_STOCK_ID],
            'left'
        );
        $collection->joinField(
            'parent_stock',
            $collection->getTable('cataloginventory_stock_item'),
            'qty',
            'product_id = child_id',
            ['stock_id' => Stock::DEFAULT_STOCK_ID],
            'left'
        );
        $collection->getSelect()
            ->columns(
                'IF(  SUM(`at_parent_stock`.`qty`),
                                 SUM(`at_parent_stock`.`qty`),
                                `at_child_stock`.`qty`) as stock'
            );

        return $collection;
    }
}
