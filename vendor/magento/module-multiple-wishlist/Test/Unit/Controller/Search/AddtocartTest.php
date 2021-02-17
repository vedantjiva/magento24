<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Controller\Search;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Layout;
use Magento\MultipleWishlist\Controller\Search\Addtocart;
use Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory;
use Magento\MultipleWishlist\Model\Search\Strategy\NameFactory;
use Magento\MultipleWishlist\Model\SearchFactory;
use Magento\Quote\Model\Quote;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\LocaleQuantityProcessor;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddtocartTest extends TestCase
{
    /**
     * @var Addtocart
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var RedirectInterface|MockObject
     */
    protected $redirectMock;

    /**
     * @var Wishlist|MockObject
     */
    protected $wishlistMock;

    /**
     * @var WishlistFactory|MockObject
     */
    protected $wishlistFactorytMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var Session|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var Layout|MockObject
     */
    protected $layoutMock;

    /**
     * @var BlockInterface|MockObject
     */
    protected $blockMock;

    /**
     * @var Manager|MockObject
     */
    protected $moduleManagerMock;

    /**
     * @var LocaleQuantityProcessor|MockObject
     */
    protected $quantityProcessorMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @var Cart|MockObject
     */
    protected $checkoutCartMock;

    /**
     * @var ItemFactory|MockObject
     */
    protected $itemFactoryMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $this->wishlistMock = $this->getMockBuilder(Wishlist::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wishlistFactorytMock = $this->getMockBuilder(WishlistFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->wishlistFactorytMock->expects($this->any())
            ->method('create')
            ->willReturn($this->wishlistMock);

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock = $this->getMockBuilder(ItemFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $searchFactoryMock = $this->getMockBuilder(SearchFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $strategyEmailFactoryMock = $this->getMockBuilder(
            EmailFactory::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $strategyNameFactoryMock = $this->getMockBuilder(
            NameFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutCartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $localeResolverMock = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->redirectMock = $this->getMockBuilder(RedirectInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->layoutMock = $this->getMockBuilder(Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockMock = $this->getMockBuilder(BlockInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['toHtml', 'setRefererUrl'])
            ->getMockForAbstractClass();

        $this->moduleManagerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quantityProcessorMock = $this->getMockBuilder(LocaleQuantityProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManagerMock = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addSuccess'])
            ->getMockForAbstractClass();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->any())
            ->method('getRedirect')
            ->willReturn($this->redirectMock);
        $this->contextMock->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->model = new Addtocart(
            $this->contextMock,
            $this->registryMock,
            $this->itemFactoryMock,
            $this->wishlistFactorytMock,
            $searchFactoryMock,
            $strategyEmailFactoryMock,
            $strategyNameFactoryMock,
            $checkoutSessionMock,
            $this->checkoutCartMock,
            $this->customerSessionMock,
            $localeResolverMock,
            $this->moduleManagerMock,
            $this->quantityProcessorMock
        );
    }

    public function testExecuteWithNoSelectedAndRedirectToCart()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn(false);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn(false);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(true);
        $cartHelperMock->expects($this->once())
            ->method('getCartUrl')
            ->willReturn('cart_url');

        $this->objectManagerMock->expects($this->exactly(2))
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $salesQuoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('cart_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            Redirect::class,
            $this->model->execute()
        );
    }

    public function testExecuteWithRedirectToReferer()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([11 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([11 => 'on']);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(11)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willReturn(true);

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->exactly(2))
            ->method('getRefererUrl')
            ->willReturn('referer_url');

        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn('product_name');
        $this->messageManagerMock->expects($this->once())
            ->method('addSuccess')
            ->with('1 product(s) have been added to shopping cart: "product_name".')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('referer_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            Redirect::class,
            $this->model->execute()
        );
    }

    public function testExecuteWithNotSalableAndNoRedirect()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([22 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([22 => 'on']);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(22)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willThrowException(new ProductException(__('Test Phrase')));

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->once())
            ->method('getRefererUrl')
            ->willReturn(false);

        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn('product_name');
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('We can\'t add the following product(s) to shopping cart: "product_name".')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('')
            ->willReturnSelf();

        $this->assertInstanceOf(
            Redirect::class,
            $this->model->execute()
        );
    }

    public function testExecuteWithMagentoException()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([22 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([22 => 'on']);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(22)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willThrowException(new LocalizedException(
                __('Unknown Magento error')
            ));

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMock);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->exactly(2))
            ->method('getRefererUrl')
            ->willReturn('referer_url');

        $productMock->expects($this->once())
            ->method('getName')
            ->willReturn('product_name');
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('Unknown Magento error for "product_name"')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('referer_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            Redirect::class,
            $this->model->execute()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testExecuteWithException()
    {
        $this->requestMock->expects($this->at(0))
            ->method('getParam')
            ->with('qty', null)
            ->willReturn([22 => 2]);
        $this->requestMock->expects($this->at(1))
            ->method('getParam')
            ->with('selected', null)
            ->willReturn([22 => 'on']);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemMock);
        $itemMock->expects($this->once())
            ->method('loadWithOptions')
            ->with(22)
            ->willReturnSelf();

        $this->quantityProcessorMock->expects($this->once())
            ->method('process')
            ->with(2)
            ->willReturn('2');

        $exception = new \Exception();

        $itemMock->expects($this->once())
            ->method('setQty')
            ->with('2')
            ->willReturnSelf();
        $itemMock->expects($this->once())
            ->method('addToCart')
            ->with($this->checkoutCartMock, false)
            ->willThrowException($exception);

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->objectManagerMock->expects($this->at(0))
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($loggerMock);

        $loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $cartHelperMock = $this->getMockBuilder(\Magento\Checkout\Helper\Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock->expects($this->at(1))
            ->method('get')
            ->with(\Magento\Checkout\Helper\Cart::class)
            ->willReturn($cartHelperMock);

        $cartHelperMock->expects($this->once())
            ->method('getShouldRedirectToCart')
            ->willReturn(false);
        $this->redirectMock->expects($this->exactly(2))
            ->method('getRefererUrl')
            ->willReturn('referer_url');

        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with('We can\'t add the item to shopping cart.')
            ->willReturnSelf();

        $salesQuoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $salesQuoteMock->expects($this->once())
            ->method('collectTotals')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->checkoutCartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($salesQuoteMock);

        $this->resultRedirectMock->expects($this->once())
            ->method('setUrl')
            ->with('referer_url')
            ->willReturnSelf();

        $this->assertInstanceOf(
            Redirect::class,
            $this->model->execute()
        );
    }
}
