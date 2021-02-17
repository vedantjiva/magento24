<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableInventory\Test\Unit\Model\ResourceModel;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\ResourceModel\Stock;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableInventory\Model\ResourceModel\QtyCounter;
use Magento\ScalableInventory\Model\ResourceModel\QtyCounterProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QtyCounterProxyTest extends TestCase
{
    /**
     * @var QtyCounter|MockObject
     */
    private $qtyCounter;

    /**
     * @var Stock|MockObject
     */
    private $stockResource;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var StockRegistryInterface|MockObject
     */
    private $stockRegistry;

    /**
     * @var QtyCounterProxy
     */
    private $resource;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->qtyCounter = $this->getMockBuilder(QtyCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockResource = $this->getMockBuilder(Stock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resource = $objectManager->getObject(
            QtyCounterProxy::class,
            [
                'qtyCounter' => $this->qtyCounter,
                'stockResource' => $this->stockResource,
                'scopeConfig' => $this->scopeConfig,
                'stockRegistry' => $this->stockRegistry,
            ]
        );
    }

    public function testCorrectItemsQty()
    {
        $items = [5269 => 12, 9462 => 31];
        $websiteId = 1;
        $operator = '+';

        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(Configuration::XML_PATH_BACKORDERS)
            ->willReturn(1);

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(QtyCounterProxy::CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE)
            ->willReturn(1);

        $stockItem5269 = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getBackorders',
                'getUseConfigDeferredStockUpdate',
                'getDeferredStockUpdate'
            ])
            ->getMockForAbstractClass();

        $stockItem5269->expects($this->once())
            ->method('getBackorders')
            ->willReturn(0);
        $stockItem5269->expects($this->once())
            ->method('getDeferredStockUpdate')
            ->willReturn(0);
        $stockItem5269->expects($this->once())
            ->method('getUseConfigDeferredStockUpdate')
            ->willReturn(0);

        $stockItem9462 = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBackorders', 'getUseConfigDeferredStockUpdate', 'getDeferredStockUpdate'])
            ->getMockForAbstractClass();
        $stockItem9462->expects($this->once())
            ->method('getBackorders')
            ->willReturn(1);
        $stockItem9462->expects($this->once())
            ->method('getDeferredStockUpdate')
            ->willReturn(0);
        $stockItem9462->expects($this->once())
            ->method('getUseConfigDeferredStockUpdate')
            ->willReturn(1);

        $this->stockRegistry->expects($this->exactly(2))
            ->method('getStockItem')
            ->willReturnMap([[5269, 1, $stockItem5269], [9462, 1, $stockItem9462]]);

        $this->qtyCounter->expects($this->once())
            ->method('correctItemsQty')
            ->with([9462 => 31], $websiteId, $operator);

        $this->stockResource->expects($this->once())
            ->method('correctItemsQty')
            ->with([5269 => 12], $websiteId, $operator);

        $this->resource->correctItemsQty($items, $websiteId, $operator);
    }
}
