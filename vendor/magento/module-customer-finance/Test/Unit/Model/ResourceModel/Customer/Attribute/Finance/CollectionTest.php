<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerFinance\Test\Unit\Model\ResourceModel\Customer\Attribute\Finance;

use Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection
 */
class CollectionTest extends TestCase
{
    /**
     * Returns mock for finance collection
     *
     * @return Collection
     */
    protected function _getFinanceCollectionMock()
    {
        return $this->createPartialMock(
            Collection::class,
            ['getItems']
        );
    }

    /**
     * Test setOrder method
     */
    public function testSetOrder()
    {
        $collection = $this->_getFinanceCollectionMock();

        $first = new DataObject(['id' => 9]);
        $second = new DataObject(['id' => 10]);

        $collection->addItem($first);
        $collection->addItem($second);

        $collection->expects($this->at(0))->method('getItems')->willReturn([$first, $second]);
        $collection->expects($this->at(1))->method('getItems')->willReturn([$second, $first]);

        /** @var $orderFirst \Magento\Framework\DataObject */
        /** @var \Magento\Framework\DataObject $orderSecond */

        $collection->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        list($orderFirst, $orderSecond) = array_values($collection->getItems());
        $this->assertEquals($first->getId(), $orderFirst->getId());
        $this->assertEquals($second->getId(), $orderSecond->getId());

        $collection->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);
        list($orderFirst, $orderSecond) = array_values($collection->getItems());
        $this->assertEquals($second->getId(), $orderFirst->getId());
        $this->assertEquals($first->getId(), $orderSecond->getId());
    }

    /**
     * Test compare attributes method
     */
    public function testCompareAttributes()
    {
        $collection = $this->_getFinanceCollectionMock();
        $collection->setOrder('id');
        $first = new DataObject(['id' => 9]);
        $second = new DataObject(['id' => 10]);

        $this->assertLessThan(0, $collection->compareAttributes($first, $second));
        $this->assertGreaterThan(0, $collection->compareAttributes($second, $first));
        $this->assertEquals(0, $collection->compareAttributes($first, $first));
    }
}
