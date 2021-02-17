<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Strategy;

use Magento\Framework\DB\Adapter\AdapterInterface as Connection;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\ForeignKey\StrategyInterface;

class DbCascade implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Connection $connection, ConstraintInterface $constraint, $condition)
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function lockAffectedData(Connection $connection, $table, $condition, $fields)
    {
        $select = $connection->select();
        $select->forUpdate(true);
        $select->from($table, $fields);
        $select->where($condition);
        return $connection->fetchAssoc($select);
    }
}
