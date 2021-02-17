<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Test\Unit\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\Manager;
use Magento\Framework\Url;
use Magento\MultipleWishlist\Controller\Index\Editwishlist;
use Magento\MultipleWishlist\Model\WishlistEditor;
use Magento\Wishlist\Model\Wishlist;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditwishlistTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $editWishListController;

    /**
     * @var MockObject
     */
    protected $context;

    /**
     * @var MockObject
     */
    protected $wishListEditor;

    /**
     * @var MockObject
     */
    protected $session;

    /**
     * @var MockObject
     */
    protected $request;

    /**
     * @var MockObject
     */
    protected $response;

    /**
     * @var MockObject
     */
    protected $messageManager;

    /**
     * @var MockObject
     */
    protected $wishList;

    /**
     * @var MockObject
     */
    protected $objectManager;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    /**
     * @var Json|MockObject
     */
    protected $resultJsonMock;

    /** @var  int */
    protected $wishListId = 1;

    /** @var  int */
    protected $customerId = 1;

    protected $isAjax = false;

    protected $url;

    /**
     * @var Validator|MockObject
     */
    protected $formKeyValidator;

    protected function setUp(): void
    {
        $this->context = $this->createPartialMock(
            Context::class,
            ['getMessageManager', 'getRequest', 'getResponse', 'getObjectManager', 'getUrl', 'getResultFactory']
        );
        $this->wishListEditor = $this->createPartialMock(
            WishlistEditor::class,
            ['edit']
        );
        $this->session = $this->createPartialMock(Session::class, ['getCustomerId']);
        $this->request = $this->createPartialMock(Http::class, ['getParam', 'isAjax']);
        $this->response = $this->createPartialMock(\Magento\Framework\App\Response\Http::class, ['representJson']);
        $this->messageManager = $this->createPartialMock(
            Manager::class,
            ['addSuccess', 'addError', 'addException']
        );
        $this->wishList = $this->createPartialMock(Wishlist::class, ['getId', 'getName']);
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->addMethods(['jsonEncode', 'escapeHtml'])
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->url = $this->createPartialMock(Url::class, ['getUrl']);

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->formKeyValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        unset(
            $this->url,
            $this->objectManager,
            $this->wishList,
            $this->messageManager,
            $this->response,
            $this->request,
            $this->session,
            $this->wishListEditor,
            $this->context,
            $this->editWishListController
        );
    }

    public function createController()
    {
        $this->formKeyValidator->expects($this->once())
            ->method('validate')
            ->with($this->request)
            ->willReturn(true);

        $this->editWishListController = new Editwishlist(
            $this->context,
            $this->wishListEditor,
            $this->session,
            $this->formKeyValidator
        );
    }

    public function configureCustomerSession()
    {
        $this->session
            ->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($this->customerId);
    }

    public function configureWishList($getIdExpects, $getNameExpects)
    {
        $this->wishList
            ->expects($this->exactly($getIdExpects))
            ->method('getId')
            ->willReturn($this->wishListId);
        $this->wishList
            ->expects($this->exactly($getNameExpects))
            ->method('getName')
            ->willReturn('wishlistTestName');
    }

    public function configureObjectManager($getExpects, $jsonEncodeExpects, $escapeHtmlExpects)
    {
        $this->objectManager
            ->expects($this->exactly($getExpects))
            ->method('get')->willReturnSelf();
        $this->objectManager
            ->expects($this->exactly($jsonEncodeExpects))
            ->method('jsonEncode')
            ->willReturn(null);
        $this->objectManager
            ->expects($this->exactly($escapeHtmlExpects))
            ->method('escapeHtml')
            ->with('wishlistTestName')
            ->willReturn('wishlistTestName');
    }

    public function configureUrl($getUrlExpects)
    {
        $this->url
            ->expects($this->exactly($getUrlExpects))
            ->method('getUrl')
            ->willReturn(null);
    }

    public function configureContext()
    {
        $this->context
            ->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);
        $this->context
            ->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $this->context
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);
        $this->context
            ->expects($this->once())
            ->method('getUrl')
            ->willReturn($this->url);
    }

    public function configureResponse($representJsonExpects)
    {
        $this->response
            ->expects($this->exactly($representJsonExpects))
            ->method('representJson')
            ->willReturn(null);
    }

    public function configureRequest()
    {
        $this->request
            ->expects($this->exactly(3))
            ->method('getParam')
            ->willReturn(null);
        $this->request
            ->expects($this->once())
            ->method('isAjax')
            ->willReturn($this->isAjax);
    }

    public function configure(
        $getIdExpects,
        $getNameExpects,
        $getExpects,
        $escapeHtmlExpects,
        $jsonEncodeExpects,
        $getUrlExpects,
        $representJsonExpects
    ) {
        $this->configureWishList($getIdExpects, $getNameExpects);
        $this->configureObjectManager($getExpects, $jsonEncodeExpects, $escapeHtmlExpects);
        $this->configureUrl($getUrlExpects);
        $this->configureResponse($representJsonExpects);
        $this->configureRequest();
        $this->configureContext();
        $this->configureCustomerSession();
        $this->createController();
    }

    public function testExecuteWithInvalidFormKey()
    {
        $this->configureContext();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->formKeyValidator->expects($this->once())
            ->method('validate')
            ->with($this->request)
            ->willReturn(false);

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/')
            ->willReturnSelf();

        $controller = new Editwishlist(
            $this->context,
            $this->wishListEditor,
            $this->session,
            $this->formKeyValidator
        );

        $this->assertSame($this->resultRedirectMock, $controller->execute());
    }

    public function testExecuteWishlistFrameworkException()
    {
        $exeption = new LocalizedException(__('Sign in to edit wish lists.'));

        $this->messageManager
            ->expects($this->never())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->willReturn(null);
        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->willThrowException($exeption);
        $this->messageManager
            ->expects($this->at(0))
            ->method('addError')
            ->with('Sign in to edit wish lists.')
            ->willReturn(null);
        $this->messageManager
            ->expects($this->at(1))
            ->method('addError')
            ->with('Could not create a wish list.')
            ->willReturn(null);

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('*/*', [])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->configure(0, 0, 0, 0, 0, 0, 0);
        $this->assertInstanceOf(
            Redirect::class,
            $this->editWishListController->execute()
        );
    }

    public function testExecuteWishlistExceptionAndAjax()
    {
        $this->isAjax = true;
        $exeption = new \Exception('Sign in to edit wish lists.');

        $this->messageManager
            ->expects($this->never())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->willReturn(null);
        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->willThrowException($exeption);
        $this->messageManager
            ->expects($this->once())
            ->method('addError')
            ->with('Could not create a wish list.')
            ->willReturn(null);
        $this->messageManager
            ->expects($this->once())
            ->method('addException')
            ->with($exeption, __('We can\'t create the wish list right now.'))
            ->willReturn(null);
        $this->objectManager
            ->expects($this->never())
            ->method('escapeHtml')
            ->with('wishlistTestName')
            ->willReturn('wishlistTestName');
        $this->url
            ->expects($this->once())
            ->method('getUrl')
            ->with('*/*', null)
            ->willReturn('magento-test.com');

        $this->configureWishList(0, 0);
        $this->configureRequest();
        $this->configureContext();
        $this->configureCustomerSession();
        $this->createController();

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(['redirect' => 'magento-test.com'], false, [])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_REDIRECT, [], $this->resultRedirectMock],
                [ResultFactory::TYPE_JSON, [], $this->resultJsonMock],
            ]);

        $this->assertInstanceOf(
            Json::class,
            $this->editWishListController->execute()
        );
    }

    public function testExecuteWithAjaxAndWishlist()
    {
        $this->isAjax = true;
        $this->configureWishList(4, 1);

        $this->messageManager
            ->expects($this->once())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->willReturn(null);
        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->willReturn($this->wishList);
        $this->messageManager
            ->expects($this->never())
            ->method('addError')
            ->with('Could not create a wish list.')
            ->willReturn(null);
        $this->messageManager
            ->expects($this->never())
            ->method('addException')
            ->willReturn(null);
        $this->objectManager
            ->expects($this->at(0))
            ->method('get')
            ->with(Escaper::class)->willReturnSelf();
        $this->objectManager
            ->expects($this->at(1))
            ->method('escapeHtml')
            ->with('wishlistTestName')
            ->willReturn('wishlistTestName');

        $this->configureRequest();
        $this->configureContext();
        $this->configureCustomerSession();
        $this->createController();

        $this->resultJsonMock->expects($this->once())
            ->method('setData')
            ->with(['wishlist_id' => $this->wishListId, 'redirect' => null], false, [])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [ResultFactory::TYPE_REDIRECT, [], $this->resultRedirectMock],
                [ResultFactory::TYPE_JSON, [], $this->resultJsonMock],
            ]);

        $this->assertInstanceOf(
            Json::class,
            $this->editWishListController->execute()
        );
    }

    public function testExecuteWithoutAjax()
    {
        $this->messageManager
            ->expects($this->once())
            ->method('addSuccess')
            ->with('Wish list "wishlistTestName" was saved.')
            ->willReturn(null);

        $this->messageManager
            ->expects($this->never())
            ->method('addError')
            ->willReturn(null);

        $this->wishListEditor
            ->expects($this->once())
            ->method('edit')
            ->willReturn($this->wishList);

        $this->configure(3, 1, 1, 1, 0, 0, 0);

        $this->resultRedirectMock->expects($this->once())
            ->method('setPath')
            ->with('wishlist/index/index', ['wishlist_id' => $this->wishListId])
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->any())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT, [])
            ->willReturn($this->resultRedirectMock);

        $this->assertInstanceOf(
            Redirect::class,
            $this->editWishListController->execute()
        );
    }
}
