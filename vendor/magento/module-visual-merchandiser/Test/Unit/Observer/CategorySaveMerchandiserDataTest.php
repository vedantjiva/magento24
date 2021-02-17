<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Observer;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VisualMerchandiser\Model\Position\Cache;
use Magento\VisualMerchandiser\Model\Rules;
use Magento\VisualMerchandiser\Observer\CategorySaveMerchandiserData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\VisualMerchandiser\Observer\CategorySaveMerchandiserData.
 */
class CategorySaveMerchandiserDataTest extends TestCase
{
    /**
     * @var CategorySaveMerchandiserData
     */
    private $categorySaveMerchandiserDataObserver;

    /**
     * @var Cache|MockObject
     */
    private $cacheMock;

    /**
     * @var Rules|MockObject
     */
    private $rulesMock;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    private $categoryRepositoryMock;

    /**
     * @var Http|MockObject
     */
    private $requestMock;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(Http::class)
            ->setMethods(['getPostValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cacheKey = '5a8fb8ef75270';
        $this->cacheMock = $this->getMockBuilder(Cache::class)
            ->setMethods(['getPositions'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rulesMock = $this->getMockBuilder(Rules::class)
            ->setMethods(['loadByCategory'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryRepositoryMock = $this->getMockBuilder(CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);

        $this->categorySaveMerchandiserDataObserver = $objectManagerHelper->getObject(
            CategorySaveMerchandiserData::class,
            [
                '_cache' => $this->cacheMock,
                '_rules' => $this->rulesMock,
                'categoryRepository' => $this->categoryRepositoryMock,
            ]
        );
    }

    /**
     * Test for new category.
     *
     * @return void
     */
    public function testNewCategoryExecute(): void
    {
        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->with(Cache::POSITION_CACHE_KEY)
            ->willReturn($this->cacheKey);
        $this->cacheMock->expects($this->once())->method('getPositions')->willReturn(false);
        $categoryMock = $this->getCategoryMock([], null);
        $eventMock = $this->getEventMock($categoryMock);
        $observerMock = $this->getObserverMock($eventMock);

        $this->categorySaveMerchandiserDataObserver->execute($observerMock);
    }

    /**
     * Test for category with matching products by rule.
     *
     * @dataProvider smartCategoryDataProvider
     * @param array $postData
     * @param string $methodsCall
     * @return void
     */
    public function testSmartCategoryExecute(array $postData, string $methodsCall): void
    {
        $origData = [
            'entity_id' => $postData['entity_id'],
            'name' => 'TEST',
        ];
        $this->requestMock->expects($this->exactly(2))
            ->method('getPostValue')
            ->willReturnMap(
                [
                    [Cache::POSITION_CACHE_KEY, null, $this->cacheKey],
                    [null, null, $postData],
                ]
            );
        $this->cacheMock->expects($this->once())->method('getPositions')->willReturn(false);

        $categoryMock = $this->getCategoryMock($origData, $postData['entity_id']);
        $eventMock = $this->getEventMock($categoryMock);
        $observerMock = $this->getObserverMock($eventMock);

        $ruleMock = $this->getMockBuilder(Rules::class)
            ->setMethods(['setData', 'save', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock->expects($this->$methodsCall())->method('getId')->willReturn(null);
        $ruleMock->expects($this->$methodsCall())->method('setData');
        $ruleMock->expects($this->$methodsCall())->method('save');

        $this->rulesMock->expects($this->$methodsCall())
            ->method('loadByCategory')
            ->with($categoryMock)
            ->willReturn($ruleMock);

        $this->categorySaveMerchandiserDataObserver->execute($observerMock);
    }

    /**
     * @return array
     */
    public function smartCategoryDataProvider(): array
    {
        $entityId = 3;

        return [
            [
                [
                    Cache::POSITION_CACHE_KEY => $this->cacheKey,
                    'entity_id' => $entityId,
                    'is_smart_category' => true,
                    'smart_category_rules' => '[{"attribute":"price","operator":"lt","value":"100","logic":"OR"}]',
                ],
                'once',
            ],
            [
                [
                    Cache::POSITION_CACHE_KEY => $this->cacheKey,
                    'entity_id' => $entityId,
                ],
                'never',
            ],
        ];
    }

    /**
     * @param array $origData
     * @param int|null $categoryId
     * @return MockObject
     */
    private function getCategoryMock(array $origData, $categoryId): MockObject
    {
        $categoryMock = $this->getMockBuilder(Category::class)
            ->setMethods(['getId', 'getOrigData'])
            ->disableOriginalConstructor()
            ->getMock();

        $categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);

        $categoryMock->expects($this->any())
            ->method('getOrigData')
            ->willReturn($origData);

        return $categoryMock;
    }

    /**
     * @param MockObject $categoryMock
     * @return MockObject
     */
    private function getEventMock(MockObject $categoryMock): MockObject
    {
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getCategory', 'getRequest'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCategory')
            ->willReturn($categoryMock);
        $eventMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        return $eventMock;
    }

    /**
     * @param MockObject $eventMock
     * @return MockObject
     */
    private function getObserverMock(MockObject $eventMock): MockObject
    {
        $observerMock = $this->getMockBuilder(Observer::class)
            ->setMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->any())->method('getEvent')->willReturn($eventMock);

        return $observerMock;
    }
}
