<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\CatalogPermissions\Observer\CheckQuotePermissionsObserver;
use Magento\Customer\Model\Session;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\CatalogPermissions\Observer\CheckQuotePermissionsObserver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckQuotePermissionsObserverTest extends TestCase
{
    /**
     * @var CheckQuotePermissionsObserver
     */
    protected $observer;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $permissionsConfig;

    /**
     * @var Index|MockObject
     */
    protected $permissionIndex;

    /**
     * @var StoreRepository|MockObject
     */
    private $storeRepositoryMock;

    /**
     * @var Data|MockObject
     */
    private $catalogPermData;

    /**
     * @var Session|MockObject
     */
    private $customerSession;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->permissionsConfig = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->permissionIndex = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogPermData = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeRepositoryMock = $this->getMockBuilder(StoreRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = $objectManager->getObject(
            CheckQuotePermissionsObserver::class,
            [
                'permissionsConfig' => $this->permissionsConfig,
                'customerSession' => $this->customerSession,
                'permissionIndex' => $this->permissionIndex,
                'catalogPermData' => $this->catalogPermData,
                'storeRepository' => $this->storeRepositoryMock,
            ]
        );
    }

    /**
     * @param int $step
     * @return MockObject
     */
    protected function preparationData($step = 0)
    {
        $quoteMock = $this->createMock(Quote::class);

        if ($step == 0) {
            $quoteMock->expects($this->exactly(2))
                ->method('getAllItems')
                ->willReturn([]);
        } else {
            $quoteItems = $this->getMockBuilder(AbstractCollection::class)
                ->addMethods(['setDisableAddToCart', 'getParentItem', 'getDisableAddToCart', 'getProduct'])
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

            $productMock = $this->getMockBuilder(Product::class)
                ->disableOriginalConstructor()
                ->getMock();
            $productMock->expects($this->any())
                ->method('getCategoryIds')
                ->willReturn([1]);

            $quoteItems->expects($this->once())
                ->method('getParentItem')
                ->willReturn(0);

            $quoteItems->expects($this->once())
                ->method('getDisableAddToCart')
                ->willReturn(0);

            $quoteItems->expects($this->any())
                ->method('getProduct')
                ->willReturn($productMock);

            $quoteMock->expects($this->exactly(2))
                ->method('getAllItems')
                ->willReturn([$quoteItems]);
        }

        if ($step == 1) {
            $this->permissionIndex->expects($this->exactly(1))
                ->method('getIndexForCategory')
                ->willReturn([]);
        } elseif ($step == 2) {
            $this->permissionIndex->expects($this->exactly(1))
                ->method('getIndexForCategory')
                ->willReturn(
                    [
                        1 => [
                            'grant_checkout_items' => Permission::PERMISSION_ALLOW
                        ]
                    ]
                );
        }

        $cartMock = $this->createMock(Cart::class);
        $cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCart'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCart')
            ->willReturn($cartMock);

        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $observerMock->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $quoteMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);

        $this->customerSession->expects($this->any())
            ->method('getCustomerGroupId')
            ->willReturn(1);

        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->storeRepositoryMock->expects($this->any())
            ->method('getById')
            ->willReturn($storeMock);

        return $observerMock;
    }

    /**
     * @return void
     */
    public function testCheckQuotePermissionsPermissionsConfigDisabled()
    {
        $this->permissionsConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $observerMock = $this->createMock(Observer::class);
        $this->assertEquals($this->observer, $this->observer->execute($observerMock));
    }

    /**
     * @param int $step
     * @dataProvider dataSteps
     * @return void
     */
    public function testCheckQuotePermissionsPermissionsConfigEnabled($step)
    {
        $this->permissionsConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $observer = $this->preparationData($step);
        $this->assertEquals($this->observer, $this->observer->execute($observer));
    }

    /**
     * @return array
     */
    public function dataSteps()
    {
        return [[0], [1], [2]];
    }
}
