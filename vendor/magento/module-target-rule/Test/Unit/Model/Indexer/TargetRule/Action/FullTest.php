<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Action;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Model\Indexer\TargetRule\Action\Full;
use Magento\TargetRule\Model\ResourceModel\Index;
use PHPUnit\Framework\TestCase;

/**
 * Class for test full reindex target rule
 */
class FullTest extends TestCase
{
    /**
     * Test full reindex target rule
     */
    public function testFullReindex()
    {
        $objectManager = new ObjectManager($this);

        $ruleFactoryMock = $this->createPartialMock(
            \Magento\TargetRule\Model\RuleFactory::class,
            ['create']
        );

        $collectionFactoryMock = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
            ['create']
        );

        $productCollectionFactoryMock = $this->getMockBuilder(ProductCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $resourceMock = $this->createMock(Index::class);

        $collectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn([1, 2]);

        $resourceMock->expects($this->at(2))
            ->method('saveProductIndex')
            ->willReturn(1);

        $storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $timezoneMock = $this->getMockForAbstractClass(TimezoneInterface::class);

        $model = $objectManager->getObject(
            Full::class,
            [
                'ruleFactory' => $ruleFactoryMock,
                'ruleCollectionFactory' => $collectionFactoryMock,
                'resource' => $resourceMock,
                'storeManager' => $storeManagerMock,
                'localeDate' => $timezoneMock,
                'productCollectionFactory' => $productCollectionFactoryMock
            ]
        );

        $model->execute();
    }
}
