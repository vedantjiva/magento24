<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\Controller;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogStaging\Model\Indexer\Category\Product\Preview;
use Magento\CatalogStaging\Model\Plugin\Controller\View;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $versionManagerMock;

    /**
     * @var MockObject
     */
    protected $resourceConnectionMock;

    /**
     * @var MockObject
     */
    protected $previewMock;

    /**
     * @var MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject
     */
    protected $storeManager;

    /**
     * @var MockObject
     */
    protected $tableResolver;

    /**
     * @var View
     */
    protected $model;

    protected function setUp(): void
    {
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->previewMock = $this->getMockBuilder(
            Preview::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store = $this->getMockBuilder(StoreInterface::class)
            ->setMethods([
                'getId',
            ])
            ->getMockForAbstractClass();
        $store->expects($this->any())
            ->method('getId')
            ->willReturn(0);
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods([
                'getStore',
            ])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($store);
        $objectManager = new ObjectManager($this);

        $connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $resource = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods([
                'getConnection',
                'getTableName'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $resource->expects($this->any())->method('getTableName')->willReturnArgument(0);

        $this->tableResolver = $objectManager->getObject(
            IndexScopeResolver::class,
            [
                'resource' => $resource
            ]
        );

        $this->model = $objectManager->getObject(
            View::class,
            [
                'versionManager' => $this->versionManagerMock,
                'resourceConnection' => $this->resourceConnectionMock,
                'preview' => $this->previewMock,
                'categoryRepository' => $this->categoryRepositoryMock,
                'storeManager' => $this->storeManager,
                'tableResolver' => $this->tableResolver
            ]
        );
    }

    public function testBeforeExecuteNotPreview()
    {
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(false);
        $viewMock->expects($this->never())
            ->method('getRequest');

        $this->model->beforeExecute($viewMock);
    }

    public function testBeforeExecuteNoCategory()
    {
        $categoryId = null;

        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn(null);

        $this->model->beforeExecute($viewMock);
    }

    public function testBeforeExecuteAlreadyMapped()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Table catalog_category_product_index_store0 already mapped');
        $categoryId = 1;
        $allIds = [1, 2, 3];
        $indexTableTmp = 'index_tmp';
        $selectFromData = [
            'main_table' => [],
            'cat_index' => ['joinType' => Select::INNER_JOIN],
            'tmp'
        ];
        $expectedSelectFromData = $selectFromData;
        $expectedSelectFromData['cat_index']['joinType'] = Select::LEFT_JOIN;

        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->once())
            ->method('getPart')
            ->with(Select::FROM)
            ->willReturn($selectFromData);
        $selectMock->expects($this->once())
            ->method('setPart')
            ->with(Select::FROM, $expectedSelectFromData);

        $categoryMock->expects($this->once())
            ->method('getProductCollection')
            ->willReturn($productCollectionMock);
        $productCollectionMock->expects($this->once())
            ->method('addCategoryFilter')
            ->with($categoryMock)
            ->willReturnSelf();
        $productCollectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn($categoryMock);

        $productCollectionMock->expects($this->once())
            ->method('getAllIds')
            ->willReturn($allIds);

        $this->previewMock->expects($this->once())
            ->method('execute')
            ->with($categoryId, $allIds);
        $this->previewMock->expects($this->once())
            ->method('getTemporaryTable')
            ->willReturn($indexTableTmp);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getTableName')
            ->with($indexTableTmp)
            ->willReturnArgument(0);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getMappedTableName')
            ->with('catalog_category_product_index_store0')
            ->willReturn($indexTableTmp);

        $this->model->beforeExecute($viewMock);
    }

    public function testBeforeExecute()
    {
        $categoryId = 1;
        $allIds = [1, 2, 3];
        $indexTableTmp = 'index_tmp';
        $selectFromData = [
            'main_table' => [],
            'cat_index' => ['joinType' => Select::INNER_JOIN],
            'tmp'
        ];
        $expectedSelectFromData = $selectFromData;
        $expectedSelectFromData['cat_index']['joinType'] = Select::LEFT_JOIN;

        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->once())
            ->method('getPart')
            ->with(Select::FROM)
            ->willReturn($selectFromData);
        $selectMock->expects($this->once())
            ->method('setPart')
            ->with(Select::FROM, $expectedSelectFromData);

        $categoryMock->expects($this->once())
            ->method('getProductCollection')
            ->willReturn($productCollectionMock);
        $productCollectionMock->expects($this->once())
            ->method('addCategoryFilter')
            ->with($categoryMock)
            ->willReturnSelf();
        $productCollectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn($categoryMock);

        $productCollectionMock->expects($this->once())
            ->method('getAllIds')
            ->willReturn($allIds);

        $this->previewMock->expects($this->once())
            ->method('execute')
            ->with($categoryId, $allIds);
        $this->previewMock->expects($this->once())
            ->method('getTemporaryTable')
            ->willReturn($indexTableTmp);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getTableName')
            ->with($indexTableTmp)
            ->willReturnArgument(0);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getMappedTableName')
            ->with('catalog_category_product_index_store0')
            ->willReturn(false);

        $this->resourceConnectionMock->expects($this->once())
            ->method('setMappedTableName')
            ->with('catalog_category_product_index_store0', $indexTableTmp);

        $this->model->beforeExecute($viewMock);
    }
}
