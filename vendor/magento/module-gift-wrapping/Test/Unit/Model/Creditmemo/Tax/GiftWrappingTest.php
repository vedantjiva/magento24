<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Model\Creditmemo\Tax;

use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Model\Total\Creditmemo\Tax\Giftwrapping;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\TestCase;

/**
 * Test class for \Magento\GiftWrapping\Model\Creditmemo\Tax\Giftwrapping
 */
class GiftWrappingTest extends TestCase
{
    public function testCreditmemoItemTaxWrapping()
    {
        $objectHelper = new ObjectManager($this);
        $model = $objectHelper->getObject(Giftwrapping::class, []);

        $creditmemo = $this->getMockBuilder(
            Creditmemo::class
        )->disableOriginalConstructor()
            ->setMethods(
                ['getAllItems', 'getOrder', 'setGwItemsBaseTaxAmount', 'setGwItemsTaxAmount']
            )->getMock();

        $item = new DataObject();
        $orderItem = new DataObject(
            ['gw_id' => 1, 'gw_base_tax_amount_invoiced' => 5, 'gw_tax_amount_invoiced' => 10]
        );

        $item->setQty(2)->setOrderItem($orderItem);
        $order = new DataObject();

        $creditmemo->expects($this->any())->method('getAllItems')->willReturn([$item]);
        $creditmemo->expects($this->any())->method('getOrder')->willReturn($order);
        $creditmemo->expects($this->once())->method('setGwItemsBaseTaxAmount')->with(10);
        $creditmemo->expects($this->once())->method('setGwItemsTaxAmount')->with(20);

        $model->collect($creditmemo);
    }
}
