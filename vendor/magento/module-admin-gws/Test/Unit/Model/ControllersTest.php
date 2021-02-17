<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\AdminGws\Model\Controllers as Ctrl;
use Magento\AdminGws\Model\ResourceModel\Collections;
use Magento\AdminGws\Model\Role;
use Magento\Backend\App\Action;
use Magento\Backend\Test\Unit\App\Action\Stub\ActionStub;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\CatalogEvent\Model\Event;
use Magento\CatalogRule\Model\Rule;
use Magento\CheckoutAgreements\Model\Agreement;
use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount\Index;
use Magento\GiftRegistry\Model\ResourceModel\Entity;
use Magento\Review\Model\Review;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\Website;
use Magento\UrlRewrite\Model\UrlRewrite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedAtLeastOnce;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ControllersTest extends TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Controllers
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_roleMock;

    /**
     * @var MockObject
     */
    protected $_storeManagerMock;

    /**
     * Controller request object
     *
     * @var MockObject
     */
    protected $_ctrlRequestMock;

    /**
     * Controller response object
     *
     * @var MockObject
     */
    protected $responseMock;

    /**
     * @var MockObject
     */
    protected $_controllerMock;

    /**
     * @var MockObject
     */
    protected $collectionsFactoryMock;

    /**
     * @var MockObject
     */
    protected $collectionsMock;

    /**
     * @var MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var MockObject
     */
    protected $_objectManager;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);
        $this->_roleMock = $this->createMock(Role::class);
        $this->_objectManager = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->_storeManagerMock = $this->createMock(StoreManager::class);
        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(
                ['setRedirect']
            )
            ->onlyMethods(['sendResponse'])
            ->getMockForAbstractClass();
        $this->_controllerMock = $this->createMock(Action::class);
        $this->_ctrlRequestMock = $this->createMock(Http::class);
        $this->collectionsFactoryMock = $this->createPartialMock(
            \Magento\AdminGws\Model\ResourceModel\CollectionsFactory::class,
            ['create']
        );
        $this->collectionsMock = $this->createPartialMock(
            Collections::class,
            ['getUsersOutsideLimitedScope', 'getRolesOutsideLimitedScope']
        );

        $coreRegistry = $this->createMock(Registry::class);

        $this->categoryRepositoryMock = $this->getMockForAbstractClass(
            CategoryRepositoryInterface::class,
            [],
            '',
            false
        );

        $this->_model = $helper->getObject(
            \Magento\AdminGws\Model\Controllers::class,
            [
                'role' => $this->_roleMock,
                'registry' => $coreRegistry,
                'objectManager' => $this->_objectManager,
                'storeManager' => $this->_storeManagerMock,
                'response' => $this->responseMock,
                'request' => $this->_ctrlRequestMock,
                'collectionsFactory' => $this->collectionsFactoryMock,
                'categoryRepository' => $this->categoryRepositoryMock
            ]
        );
    }

    protected function tearDown(): void
    {
        unset($this->_controllerMock);
        unset($this->_ctrlRequestMock);
        unset($this->responseMock);
        unset($this->_model);
        unset($this->_objectManager);
        unset($this->_roleMock);
        unset($this->collectionsFactoryMock);
        unset($this->collectionsMock);
        unset($this->categoryRepositoryMock);
    }

    public function testValidateRuleEntityActionRoleHasntWebSiteIdsAndConsideringActionsToDenyForwardAvoidCycling()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);

        $this->_ctrlRequestMock->expects($this->at(1))
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_DENIED);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn(null);

        $this->_model->validateRuleEntityAction();
    }

    public function testValidateRuleEntityActionRoleHasntWebSiteIdsAndConsideringActionsToDenyForward()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);

        $this->_ctrlRequestMock->expects(
            $this->at(1)
        )->method(
            'getActionName'
        )->willReturn(
            'any_action'
        );
        $this->_ctrlRequestMock->expects($this->once())->method('initForward');
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setActionName'
        )->with(
            Ctrl::ACTION_DENIED
        )->willReturnSelf();
        $this->_ctrlRequestMock->expects($this->once())->method('setDispatched')->with(false);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn(null);

        $this->_model->validateRuleEntityAction();
    }

    public function testValidateRuleEntityActionWhichIsNotInDenyList()
    {
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getActionName'
        )->willReturn(
            'any_action'
        );

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn(null);
        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    public function testValidateRuleEntityActionNoAppropriateEntityIdInRequestParams()
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(null);
        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);
        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    /**
     * Test get valid entity model class name
     *
     * @param $controllerName string
     * @param $modelName string
     * @dataProvider validateRuleEntityActionGetValidModuleClassNameDataProvider
     */
    public function testValidateRuleEntityActionGetValidModuleClassName($controllerName, $modelName)
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            $controllerName
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            $modelName
        )->willReturn(
            null
        );

        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    public function validateRuleEntityActionGetValidModuleClassNameDataProvider()
    {
        return [
            ['promo_catalog', Rule::class],
            ['promo_quote', \Magento\SalesRule\Model\Rule::class],
            ['reminder', \Magento\Reminder\Model\Rule::class],
            ['customersegment', Segment::class]
        ];
    }

    public function testValidateRuleEntityActionGetModuleClassNameWithInvalidController()
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'some_other'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $this->_objectManager->expects($this->exactly(0))->method('create');

        $this->assertTrue($this->_model->validateRuleEntityAction($this->_controllerMock));
    }

    public function testValidateRuleEntityActionDenyActionIfSpecifiedRuleEntityDoesntExist()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $modelMock = $this->createMock(Rule::class);
        $modelMock->expects($this->once())->method('load')->with(1);
        $modelMock->expects($this->once())->method('getId')->willReturn(false);

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    public function testValidateRuleEntityActionDenyActionIfRoleHasNoExclusiveAccessToAssignedToRuleEntityWebsites()
    {
        $modelMock = $this->createMock(Rule::class);

        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn([1]);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);
        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            false
        );

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);

        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->willReturn(1);
        $modelMock->expects($this->once())->method('getOrigData')->willReturn([1, 2]);

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    public function testValidateRuleEntityActionDenyActionIfRoleHasNoAccessToAssignedToRuleEntityWebsites()
    {
        $this->_ctrlRequestMock->expects($this->at(0))
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn([1]);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );

        $modelMock = $this->createMock(Rule::class);
        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->willReturn(1);
        $modelMock->expects($this->once())->method('getOrigData')->willReturn([1, 2]);

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);
        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            true
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            false
        );

        $this->assertEmpty($this->_model->validateRuleEntityAction());
    }

    /**
     * @param array $post
     * @param boolean $result
     * @param boolean $isAll
     *
     * @dataProvider validateCmsHierarchyActionDataProvider
     */
    public function testValidateCmsHierarchyAction(array $post, $isAll, $result, $getActionNameInvoke)
    {
        $this->_ctrlRequestMock->expects($this->any())
            ->method('getPost')
            ->willReturn($post);

        $this->expectsForward($this->never(), $getActionNameInvoke);

        $websiteId = (isset($post['website'])) ? $post['website'] : 1;
        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $storeId = (isset($post['store'])) ? $post['store'] : 1;
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getWebsite'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($storeId);
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->_storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);

        $hasExclusiveAccess = in_array($websiteMock->getId(), [1]);
        $hasExclusiveStoreAccess = in_array($storeMock->getId(), [2]);

        $this->_roleMock->expects($this->any())
            ->method('hasExclusiveAccess')
            ->willReturn($hasExclusiveAccess);

        $this->_roleMock->expects($this->any())
            ->method('hasExclusiveStoreAccess')
            ->willReturn($hasExclusiveStoreAccess);

        $this->_roleMock->expects($this->any())
            ->method('getIsAll')
            ->willReturn($isAll);

        $this->assertEquals($result, $this->_model->validateCmsHierarchyAction());
    }

    /**
     * Data provider for testValidateCmsHierarchyAction()
     *
     * @return array
     */
    public function validateCmsHierarchyActionDataProvider()
    {
        return [
            [[], true, true, 'getActionNameInvoke' => $this->never()],
            [[], false, false, 'getActionNameInvoke' => $this->atLeastOnce()],
            [['website' => 1, 'store' => 1], false, false, 'getActionNameInvoke' => $this->atLeastOnce()],
            [['store' => 2], false, true, 'getActionNameInvoke' => $this->never()],
            [['store' => 1], false, false, 'getActionNameInvoke' => $this->atLeastOnce()],
        ];
    }

    public function testValidateRuleEntityActionWithValidParams()
    {
        $this->_ctrlRequestMock->expects($this->once())
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getControllerName'
        )->willReturn(
            'promo_catalog'
        );
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn([1]);

        $this->_roleMock->expects($this->once())->method('getWebsiteIds')->willReturn([1]);

        $modelMock = $this->createMock(Rule::class);
        $modelMock->expects($this->once())->method('load')->with([1]);
        $modelMock->expects($this->once())->method('getId')->willReturn(1);
        $modelMock->expects($this->once())->method('getOrigData')->willReturn([1, 2]);

        $this->_objectManager->expects($this->exactly(1))->method('create')->willReturn($modelMock);

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasExclusiveAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            true
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'hasWebsiteAccess'
        )->with(
            [0 => 1, 2 => 2]
        )->willReturn(
            true
        );

        $this->assertTrue($this->_model->validateRuleEntityAction());
    }

    public function testValidateAdminUserActionWithoutId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'user_id'
        )->willReturn(
            null
        );
        $this->assertTrue($this->_model->validateAdminUserAction());
    }

    public function testValidateAdminUserActionWithNotLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'user_id'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getUsersOutsideLimitedScope'
        )->willReturn(
            []
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->assertTrue($this->_model->validateAdminUserAction());
    }

    public function testValidateAdminUserActionWithLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'user_id'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getUsersOutsideLimitedScope'
        )->willReturn(
            [1]
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertFalse($this->_model->validateAdminUserAction());
    }

    public function testValidateAdminRoleActionWithoutId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            null
        );

        $this->assertTrue($this->_model->validateAdminRoleAction());
    }

    public function testValidateAdminRoleActionWithNotLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getRolesOutsideLimitedScope'
        )->willReturn(
            []
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->assertTrue($this->_model->validateAdminRoleAction());
    }

    public function testValidateAdminRoleActionWithLimitedId()
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            1
        );

        $this->collectionsMock->expects(
            $this->any()
        )->method(
            'getRolesOutsideLimitedScope'
        )->willReturn(
            [1]
        );

        $this->collectionsFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->collectionsMock
        );

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertFalse($this->_model->validateAdminRoleAction());
    }

    public function testValidateRmaAttributeDeleteAction()
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->assertFalse($this->_model->validateRmaAttributeDeleteAction());
    }

    public function testValidateRmaAttributeSaveAction()
    {
        $websiteId = 1;

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            ['delete' => '1']
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $this->_storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->_roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);

        $this->assertTrue($this->_model->validateRmaAttributeSaveAction());
    }

    public function testValidateRmaAttributeSaveActionNoWebsiteAccess()
    {
        $websiteId = 1;

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            []
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getWebsite'
        )->willReturn(
            $websiteMock
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->willReturn(
            false
        );

        $this->expectsForward($this->never(), $this->atLeastOnce());

        $this->assertFalse($this->_model->validateRmaAttributeSaveAction());
    }

    public function testValidateRmaAttributeSaveActionNoWebsiteCodeAndNoAllowedWebsites()
    {
        $websiteId = null;
        $allowedWebsiteIds = [];

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            []
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'getWebsiteIds'
        )->willReturn(
            $allowedWebsiteIds
        );

        $this->_ctrlRequestMock->expects($this->atLeastOnce())->method('getActionName')
            ->willReturn(Ctrl::ACTION_DENIED);

        $this->assertFalse($this->_model->validateRmaAttributeSaveAction());
    }

    public function testValidateRmaAttributeSaveActionRedirectToAllowedWebsites()
    {
        $websiteId = null;
        $allowedWebsiteIds = [2];

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getPost'
        )->with(
            'option'
        )->willReturn(
            []
        );

        $this->_ctrlRequestMock->expects(
            $this->never()
        )->method(
            'setPostValue'
        )->with(
            'option'
        );

        $this->_ctrlRequestMock->expects(
            $this->once()
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'getWebsiteIds'
        )->willReturn(
            $allowedWebsiteIds
        );

        $this->responseMock->expects(
            $this->once()
        )->method(
            'setRedirect'
        );
        $this->assertFalse($this->_model->validateRmaAttributeSaveAction());
    }

    /**
     * @param $isWebSiteLevel
     * @param $action
     * @param $id
     * @param $expectedInvoke
     * @dataProvider validateGiftCardAccountDataProvider
     */
    public function testValidateGiftCardAccount($isWebSiteLevel, $action, $id, $expectedInvoke, $getActionNameInvoke)
    {
        $controllerMock = $this->createPartialMock(
            Index::class,
            ['setShowCodePoolStatusMessage']
        );

        $this->_roleMock->expects(
            $this->once()
        )->method(
            'getIsWebsiteLevel'
        )->willReturn(
            $isWebSiteLevel
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($action);

        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'id'
        )->willReturn(
            $id
        );

        $this->expectsForward($expectedInvoke, $getActionNameInvoke);
        $this->_model->validateGiftCardAccount($controllerMock);
    }

    /**
     * Data provider for testValidateCmsHierarchyAction()
     *
     * @return array
     */
    public function validateGiftCardAccountDataProvider()
    {
        return [
            'WithWebsiteLevelPermissions' => [
                'isWebSiteLevel' => true,
                'action' => '',
                'id' => null,
                'expectedInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'WithoutWebsiteLevelPermissionsActionNew' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_NEW,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionGenerate' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_GENERATE,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionEditWithoutId' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_EDIT,
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionEdit' => [
                'isWebSiteLevel' => false,
                'action' => Ctrl::ACTION_EDIT,
                'id' => 1,
                'expectedInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'WithoutWebsiteLevelPermissionsActionNewCamelCaseActionName' => [
                'isWebSiteLevel' => false,
                'action' => 'NeW',
                'id' => null,
                'expectedInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
        ];
    }

    /**
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedInvoke
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @dataProvider validateGiftregistryEntityActionDataProvider
     */
    public function testValidateGiftregistryEntityAction(
        $id,
        $websiteId,
        $roleWebsiteIds,
        $expectedInvoke,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        $expectedValue
    ) {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            $id
        );

        $recourceEntityMock = $this->createPartialMock(
            Entity::class,
            ['getWebsiteIdByEntityId']
        );

        $modelEntityMock = $this->createPartialMock(
            \Magento\GiftRegistry\Model\Entity::class,
            ['getResource']
        );

        $modelEntityMock->expects(
            $expectedInvoke
        )->method(
            'getResource'
        )->willReturn(
            $recourceEntityMock
        );

        $recourceEntityMock->expects(
            $expectedInvoke
        )->method(
            'getWebsiteIdByEntityId'
        )->with(
            $id
        )->willReturn(
            $websiteId
        );

        $this->_objectManager->expects(
            $expectedInvoke
        )->method(
            'create'
        )->with(
            \Magento\GiftRegistry\Model\Entity::class
        )->willReturn(
            $modelEntityMock
        );

        $this->_roleMock->expects(
            $expectedInvoke
        )->method(
            'getWebsiteIds'
        )->willReturn(
            $roleWebsiteIds
        );

        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateGiftregistryEntityAction());
    }

    /**
     * Data provider for testValidateGiftregistryEntityAction()
     *
     * @return array
     */
    public function validateGiftregistryEntityActionDataProvider()
    {
        $id = 1;
        $websiteId = 1;
        return [
            'withoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedInvoke' => $this->never(),
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false
            ],
            'withIdNotInRoleIds' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedInvoke' => $this->atLeastOnce(),
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false
            ],
            'withIdInRoleIds' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedInvoke' => $this->atLeastOnce(),
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true
            ],
        ];
    }

    /**
     * @param $actionName
     * @param $attributeId
     * @param $websiteId
     * @param $hasWebsiteAccess
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @dataProvider validateCustomerAttributeActionsDataProvider
     */
    public function testValidateCustomerAttributeActions(
        $actionName,
        $attributeId,
        $websiteId,
        $hasWebsiteAccess,
        $expectedForwardInvoke,
        $expectedValue
    ) {
        $this->_ctrlRequestMock->expects($this->at(0))->method('getActionName')->willReturn($actionName);

        $this->_ctrlRequestMock->expects(
            $this->at(1)
        )->method(
            'getParam'
        )->with(
            'attribute_id'
        )->willReturn(
            $attributeId
        );
        $this->_ctrlRequestMock->expects(
            $this->at(2)
        )->method(
            'getParam'
        )->with(
            'website'
        )->willReturn(
            $websiteId
        );

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasWebsiteAccess'
        )->willReturn(
            $hasWebsiteAccess
        );

        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->assertEquals($expectedValue, $this->_model->validateCustomerAttributeActions());
    }

    /**
     * Data provider for testValidateCustomerAttributeActions()
     *
     * @return array
     */
    public function validateCustomerAttributeActionsDataProvider()
    {
        return [
            'actionNew' => [
                'actionName' => Ctrl::ACTION_NEW,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false,
            ],
            'actionDelete' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false,
            ],
            'actionEdit' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'attributeId' => null,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false,
            ],
            'actionSave' => [
                'actionName' => Ctrl::ACTION_SAVE,
                'attributeId' => null,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false,
            ],
            'actionEditWithAttributeId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'attributeId' => 1,
                'websiteId' => null,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'actionDoesntMatterWithoutWebAccess' => [
                'actionName' => 'DoesntMatter',
                'attributeId' => null,
                'websiteId' => 1,
                'hasWebsiteAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedForwardInvoke
     * @dataProvider validateCustomerEditDataProvider
     */
    public function testValidateCustomerEdit(
        $id,
        $websiteId,
        $roleWebsiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $this->expectsCustomerAction($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateCustomerEdit();
    }

    /**
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedForwardInvoke
     * @dataProvider validateCustomerbalanceDataProvider
     */
    public function testValidateCustomerbalance(
        $id,
        $websiteId,
        $roleWebsiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $this->expectsCustomerAction($id, $websiteId, $roleWebsiteIds, $expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateCustomerbalance();
    }

    /**
     * Data provider for testValidateCustomer()
     *
     * @return array
     */
    public function validateCustomerEditDataProvider()
    {
        $id = 1;
        $websiteId = 1;
        return [
            'customerWithoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'customerHasNoRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'customerHasRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
        ];
    }

    /**
     * Data provider for testValidateCustomerbalance()
     *
     * @return array
     */
    public function validateCustomerbalanceDataProvider()
    {
        $id = 1;
        $websiteId = 1;
        return [
            'customerWithoutId' => [
                'id' => null,
                'websiteId' => null,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'customerHasNoRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'customerHasRole' => [
                'id' => $id,
                'websiteId' => $websiteId,
                'roleWebsiteIds' => [$websiteId],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
        ];
    }

    /**
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogProductMassActionsDataProvider
     */
    public function testValidateCatalogProductMassActions($hasStoreAccess, $expectedForwardInvoke, $getActionNameInvoke)
    {
        $storeId = 1;
        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'store'
        )->willReturn(
            $storeId
        );
        $this->_storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);

        $storeMock->expects($this->any())->method('getId')->willReturn($storeId);

        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasStoreAccess'
        )->willReturn(
            $hasStoreAccess
        );

        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateCatalogProductMassActions();
    }

    /**
     * Data provider for testValidateCatalogProductMassActions()
     *
     * @return array
     */
    public function validateCatalogProductMassActionsDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
            ]
        ];
    }

    /**
     * @param $isGetAll
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @dataProvider validateCatalogProductAttributeActionsDataProvider
     */
    public function testValidateCatalogProductAttributeActions(
        $isGetAll,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        $expectedValue
    ) {
        $this->_roleMock->expects($this->any())->method('getIsAll')->willReturn($isGetAll);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateCatalogProductAttributeActions());
    }

    /**
     * Data provider for testValidateCatalogProductAttributeActions()
     *
     * @return array
     */
    public function validateCatalogProductAttributeActionsDataProvider()
    {
        return [
            'permissionsAreAllowed' => [
                'isAll' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreNotAllowed' => [
                'isAll' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @param $isGetAll
     * @param $attributeId
     * @param $expectedForwardInvoke
     * @param $expectedValue
     * @param $getActionNameInvoke
     * @dataProvider validateCatalogProductAttributeCreateActionDataProvider
     */
    public function testValidateCatalogProductAttributeCreateAction(
        $isGetAll,
        $attributeId,
        $expectedForwardInvoke,
        $getActionNameInvoke,
        $expectedValue
    ) {
        $this->_roleMock->expects($this->any())->method('getIsAll')->willReturn($isGetAll);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'attribute_id'
        )->willReturn(
            $attributeId
        );
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->assertEquals($expectedValue, $this->_model->validateCatalogProductAttributeCreateAction());
    }

    /**
     * Data provider for testValidateCatalogProductAttributeCreateAction()
     *
     * @return array
     */
    public function validateCatalogProductAttributeCreateActionDataProvider()
    {
        $attributeId = 1;
        return [
            'permissionsAreAllowedAndAttributeIdIsSet' => [
                'isAll' => true,
                'attributeId' => $attributeId,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreAllowedAndAttributeIdIsNotSet' => [
                'isAll' => true,
                'attributeId' => null,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreNotAllowedAndAttributeIdIsSet' => [
                'isAll' => false,
                'attributeId' => $attributeId,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
                'expectedValue' => true,
            ],
            'permissionsAreNotAllowedAndAttributeIdIsNotSet' => [
                'isAll' => false,
                'attributeId' => null,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
                'expectedValue' => false,
            ],
        ];
    }

    /**
     * @param $reviewId
     * @param $reviewStoreIds
     * @param $storeIds
     * @param $expectedRedirectInvoke
     * @dataProvider validateCatalogProductReviewDataProvider
     */
    public function testValidateCatalogProductReview($reviewId, $reviewStoreIds, $storeIds, $expectedRedirectInvoke)
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'id'
        )->willReturn(
            $reviewId
        );

        $reviewMock = $this->getMockBuilder(Review::class)
            ->addMethods(['getStores'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $reviewMock->expects($this->once())->method('load')->willReturnSelf();

        $reviewMock->expects(
            $this->once()
        )->method(
            'getStores'
        )->willReturn(
            $reviewStoreIds
        );

        $this->_objectManager->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            Review::class
        )->willReturn(
            $reviewMock
        );

        $this->_roleMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);

        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogProductReview();
    }

    /**
     * Data provider for testValidateCatalogProductReview()
     *
     * @return array
     */
    public function validateCatalogProductReviewDataProvider()
    {
        $reviewId = 1;
        return [
            'allowIfReviewHasAccess' => [
                'reviewId' => $reviewId,
                'reviewStoreIds' => [1],
                'storeIds' => [1, 2, 3],
                'expectedRedirectInvoke' => $this->never(),
            ],
            'redirectIfReviewHasNoAccess' => [
                'reviewId' => $reviewId,
                'reviewStoreIds' => [1],
                'storeIds' => [2, 3],
                'expectedRedirectInvoke' => $this->once(),
            ]
        ];
    }

    /**
     * @param $storeId
     * @param $hasStoreAccess
     * @param $expectedRedirectInvoke
     * @dataProvider validateCatalogProductEditDataProvider
     */
    public function testValidateCatalogProductEdit($storeId, $hasStoreAccess, $expectedRedirectInvoke)
    {
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturn(
            $storeId
        );

        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $storeMock->expects($this->any())->method('getId')->willReturn($storeId);

        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getStore'
        )->willReturn(
            $storeMock
        );

        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogProductEdit();
    }

    /**
     * Data provider for testValidateCatalogProductEditData()
     *
     * @return array
     */
    public function validateCatalogProductEditDataProvider()
    {
        $storeId = 1;
        return [
            'allowStoreInRequestWhenStoreIdIsEmpty' => [
                'storeId' => null,
                'hasStoreAccess' => false,
                'expectedRedirectInvoke' => $this->never(),
            ],
            'allowIfHasStoreAccess' => [
                'storeId' => $storeId,
                'hasStoreAccess' => true,
                'expectedRedirectInvoke' => $this->never(),
            ],
            'redirectIfNoStoreAcces' => [
                'storeId' => $storeId,
                'hasStoreAccess' => false,
                'expectedRedirectInvoke' => $this->once(),
            ],
        ];
    }

    /**
     * @param $actionName
     * @param $categoryId
     * @param $isWebsiteLevel
     * @param $allowedRootCategories
     * @param $categoryPath
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogEventsDataProvider
     */
    public function testValidateCatalogEvents(
        $actionName,
        $categoryId,
        $isWebsiteLevel,
        $allowedRootCategories,
        $categoryPath,
        $expectedForwardInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->with(
            'category_id'
        )->willReturn(
            $categoryId
        );

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);

        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn($isWebsiteLevel);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);

        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->_model->validateCatalogEvents();
    }

    public function testValidateCatalogEventsException()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_NEW);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('category_id')->willReturn(1);
        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException()
        );
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateCatalogEvents();
    }

    /**
     * Data provider for testValidateCatalogEvents()
     *
     * @return array
     */
    public function validateCatalogEventsDataProvider()
    {
        return [
            'allowIfActionNameIsNotNew' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'categoryId' => null,
                'isWebsiteLevel' => null,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'expectedForwardInvoke' => $this->never(),
            ],
            'forwardIfActionNameIsNewWithoutCategory' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => null,
                'isWebsiteLevel' => null,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'expectedForwardInvoke' => $this->atLeastOnce(),
            ],
            'forwardIfActionNameIsNewWithCategoryAndWithoutWebsiteLevelAndWithoutAllowedCategory' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => 1,
                'isWebsiteLevel' => false,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory2',
                'expectedForwardInvoke' => $this->atLeastOnce(),
            ],
            'allowIfActionNameIsNewWithCategoryAndAccess' => [
                'actionName' => Ctrl::ACTION_NEW,
                'categoryId' => 1,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'expectedForwardInvoke' => $this->never(),
            ],
        ];
    }

    /**
     * @param $id
     * @param $isWebsiteLevel
     * @param $allowedRootCategories
     * @param $categoryPath
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $expectedRedirectInvoke
     * @dataProvider validateCatalogEventEditDataProvider
     */
    public function testValidateCatalogEventEdit(
        $id,
        $isWebsiteLevel,
        $allowedRootCategories,
        $categoryPath,
        $hasStoreAccess,
        $expectedForwardInvoke,
        $expectedRedirectInvoke,
        $getActionNameInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn($id);
        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn($isWebsiteLevel);

        $catalogEventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCategoryId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $catalogEventMock->expects($this->any())->method('load')->willReturnSelf();
        $catalogEventMock->expects($this->any())->method('getCategoryId')->willReturn(1);
        $catalogEventMock->expects($this->any())->method('getId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Event::class
        )->willReturn(
            $catalogEventMock
        );

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);

        $storeMock = $this->createPartialMock(Store::class, ['getId']);
        $storeMock->expects($this->any())->method('getId')->willReturn(1);

        $this->_storeManagerMock->expects($this->any())->method('getStore')->willReturn($storeMock);
        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getDefaultStoreView'
        )->willReturn(
            $storeMock
        );
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->expectsRedirect($expectedRedirectInvoke);
        $this->_model->validateCatalogEventEdit();
    }

    public function testValidateCatalogEventEditException()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('getIsWebsiteLevel')->willReturn(true);

        $catalogEventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getCategoryId'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $catalogEventMock->expects($this->any())->method('load')->willReturnSelf();
        $catalogEventMock->expects($this->any())->method('getCategoryId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Event::class
        )->willReturn(
            $catalogEventMock
        );

        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException()
        );
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateCatalogEventEdit();
    }

    /**
     * Data provider for testValidateCatalogEvents()
     *
     * @return array
     */
    public function validateCatalogEventEditDataProvider()
    {
        $storeId = 1;
        return [
            'allow' => [
                'id' => null,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => null,
                'categoryPath' => null,
                'hasStoreAccess' => null,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
            'forwardIfCategoryNotAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory2'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => null,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce()
            ],
            'redirectIfCategoryAllowedButStoreInRequestNotAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->once(),
                'getActionNameInvoke' => $this->never()
            ],
            'allowIfCategoryAllowedAndStoreInRequestAllowed' => [
                'id' => $storeId,
                'isWebsiteLevel' => true,
                'allowedRootCategories' => ['testCategory1'],
                'categoryPath' => 'testCategory1',
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'expectedRedirectInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never()
            ],
        ];
    }

    /**
     * @param $actionName
     * @param $parentId
     * @param $categoryPath
     * @param $allowedRootCategories
     * @param $exclusiveCategoryAccess
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogCategoriesAddDataProvider
     */
    public function testValidateCatalogCategoriesAdd(
        $actionName,
        $parentId,
        $categoryPath,
        $allowedRootCategories,
        $exclusiveCategoryAccess,
        $expectedForwardInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('parent')->willReturn($parentId);

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);
        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturn(
            $exclusiveCategoryAccess
        );
        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->_model->validateCatalogCategories();
    }

    /**
     * Data provider for validateCatalogCategoriesAdd
     *
     * @return array
     */
    public function validateCatalogCategoriesAddDataProvider()
    {
        return [
            'allowIfNotAddAndEdit' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'parentId' => null,
                'categoryPath' => null,
                'allowedRootCategories' => null,
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'allowIfAddAndHasPermission' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfAddAndNoAllowedCategory' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'forwardIfAddAndNoExclusiveCategoryAccess' => [
                'actionName' => Ctrl::ACTION_ADD,
                'parentId' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => false,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
        ];
    }

    /**
     * Test ValidateCatalogCategoriesEdit
     *
     * @param $actionName
     * @param $parentId
     * @param $id
     * @param $categoryPath
     * @param $allowedRootCategories
     * @param $exclusiveCategoryAccess
     * @param $expectedForwardInvoke
     * @dataProvider validateCatalogCategoriesEditDataProvider
     */
    public function testValidateCatalogCategoriesEdit(
        $actionName,
        $parentId,
        $id,
        $categoryPath,
        $allowedRootCategories,
        $exclusiveCategoryAccess,
        $expectedForwardInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn($actionName);
        $this->_ctrlRequestMock->expects(
            $this->any()
        )->method(
            'getParam'
        )->willReturnMap(
            [
                ['id', null, $id],
                ['parent', null, $parentId]
            ]
        );

        $categoryMock = $this->getMockForAbstractClass(
            CategoryInterface::class,
            [],
            '',
            false
        );
        $categoryMock->expects($this->any())->method('getPath')->willReturn($categoryPath);
        $this->categoryRepositoryMock->expects($this->any())->method('get')->willReturn($categoryMock);
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn($allowedRootCategories);
        $this->_roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->willReturn(
            $exclusiveCategoryAccess
        );
        $this->expectsForward($expectedForwardInvoke, $this->atLeastOnce());
        $this->_model->validateCatalogCategories();
    }

    public function testValidateCatalogCategoriesEditException()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_EDIT);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);

        $this->categoryRepositoryMock->expects(
            $this->any()
        )->method(
            'get'
        )->willThrowException(
            new NoSuchEntityException()
        );
        $this->_roleMock->expects($this->any())->method('getAllowedRootCategories')->willReturn([]);
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateCatalogCategories();
    }

    /**
     * @return array
     */
    public function validateCatalogCategoriesEditDataProvider()
    {
        return [
            'allowIfNotAddAndEdit' => [
                'actionName' => Ctrl::ACTION_DELETE,
                'parentId' => null,
                'id' => null,
                'categoryPath' => null,
                'allowedRootCategories' => null,
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'allowIfEditAndHasPermissionAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfEditAndNoAllowedCategoryAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => true,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'forwardIfEditAndNoExclusiveCategoryAccessAndNoId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => 1,
                'id' => null,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => false,
                'expectedForwardInvoke' => $this->atLeastOnce()
            ],
            'allowIfEditAndHasPermissionAndId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => null,
                'id' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory1'],
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
            'forwardIfEditAndNoAllowedCategoryAndId' => [
                'actionName' => Ctrl::ACTION_EDIT,
                'parentId' => null,
                'id' => 1,
                'categoryPath' => 'testCategory1',
                'allowedRootCategories' => ['testCategory2'],
                'exclusiveCategoryAccess' => null,
                'expectedForwardInvoke' => $this->never()
            ],
        ];
    }

    public function testValidateSalesOrderCreation()
    {
        $this->_roleMock->expects($this->any())->method('getWebsiteIds')->willReturn([]);
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateSalesOrderCreation();
    }

    /**
     * test validateSalesOrderViewAction
     *
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderViewAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $salesOrderMock = $this->createPartialMock(
            Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('order_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderViewAction();
    }

    /**
     * test validateSalesOrderCreditmemoViewAction
     *
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderCreditmemoViewAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $orderCreditmemoMock = $this->createPartialMock(
            Creditmemo::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderCreditmemoMock->expects($this->any())->method('load')->willReturnSelf();
        $orderCreditmemoMock->expects($this->any())->method('getId')->willReturn(1);
        $orderCreditmemoMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Creditmemo::class
        )->willReturn(
            $orderCreditmemoMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('creditmemo_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderCreditmemoViewAction();
    }

    /**
     * test validateSalesOrderInvoiceViewAction
     *
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderInvoiceViewAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $orderInvoiceMock = $this->createPartialMock(
            Invoice::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderInvoiceMock->expects($this->any())->method('load')->willReturnSelf();
        $orderInvoiceMock->expects($this->any())->method('getId')->willReturn(1);
        $orderInvoiceMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Invoice::class
        )->willReturn(
            $orderInvoiceMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('invoice_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderInvoiceViewAction();
    }

    /**
     * test validateSalesOrderShipmentViewAction
     *
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderShipmentViewAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $orderShipmentMock = $this->createPartialMock(
            Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('shipment_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderShipmentViewAction();
    }

    /**
     * Test validateSalesOrderCreditmemoCreateAction
     *
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderCreditmemoCreateAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $orderCreditmemoMock = $this->createPartialMock(
            Creditmemo::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderCreditmemoMock->expects($this->any())->method('load')->willReturnSelf();
        $orderCreditmemoMock->expects($this->any())->method('getId')->willReturn(1);
        $orderCreditmemoMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Creditmemo::class
        )->willReturn(
            $orderCreditmemoMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('invoice_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(2))->method('getParam')->with('creditmemo_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderCreditmemoCreateAction();
    }

    /**
     * Test validateSalesOrderInvoiceCreateAction
     *
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderInvoiceCreateAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $orderInvoiceMock = $this->createPartialMock(
            Invoice::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderInvoiceMock->expects($this->any())->method('load')->willReturnSelf();
        $orderInvoiceMock->expects($this->any())->method('getId')->willReturn(1);
        $orderInvoiceMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Invoice::class
        )->willReturn(
            $orderInvoiceMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('invoice_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderInvoiceCreateAction();
    }

    /**
     * Test validateSalesOrderShipmentCreateAction
     *
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderShipmentCreateAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $orderShipmentMock = $this->createPartialMock(
            Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('shipment_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderShipmentCreateAction();
    }

    /**
     * test validateSalesOrderMassAction
     *
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderMassAction($hasStoreAccess, $expectedForwardInvoke, $getActionNameInvoke)
    {
        $salesOrderMock = $this->createPartialMock(
            Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')
            ->with('order_ids', [])
            ->willReturn([1, 2, 3]);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderMassAction();
    }

    /**
     * Test validateSalesOrderEditStartAction
     *
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @dataProvider validateSalesOrderDataProvider
     */
    public function testValidateSalesOrderEditStartAction($hasStoreAccess, $expectedForwardInvoke, $getActionNameInvoke)
    {
        $salesOrderMock = $this->createPartialMock(
            Order::class,
            ['load', 'getStoreId', 'getId']
        );
        $salesOrderMock->expects($this->any())->method('load')->willReturnSelf();
        $salesOrderMock->expects($this->any())->method('getId')->willReturn(1);
        $salesOrderMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Order::class
        )->willReturn(
            $salesOrderMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('order_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateSalesOrderEditStartAction();
    }

    /**
     * Data provider for ValidateSalesOrder tests.
     *
     * @return array
     */
    public function validateSalesOrderDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
            ]
        ];
    }

    public function testValidateSalesOrderShipmentTrackActionHasStoreAccess()
    {
        $orderShipmentTrackMock = $this->createPartialMock(
            Track::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentTrackMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentTrackMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentTrackMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->at(0)
        )->method(
            'create'
        )->with(
            Track::class
        )->willReturn(
            $orderShipmentTrackMock
        );

        $orderShipmentMock = $this->createPartialMock(
            Shipment::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->at(1)
        )->method(
            'create'
        )->with(
            Shipment::class
        )->willReturn(
            $orderShipmentMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('track_id')->willReturn(1);
        $this->_ctrlRequestMock->expects($this->at(1))->method('getParam')->with('order_id')->willReturn(null);
        $this->_ctrlRequestMock->expects($this->at(2))->method('getParam')->with('shipment_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn(true);
        $this->_model->validateSalesOrderShipmentTrackAction();
    }

    public function testValidateSalesOrderShipmentTrackActionHasNoStoreAccess()
    {
        $orderShipmentTrackMock = $this->createPartialMock(
            Track::class,
            ['load', 'getStoreId', 'getId']
        );
        $orderShipmentTrackMock->expects($this->any())->method('load')->willReturnSelf();
        $orderShipmentTrackMock->expects($this->any())->method('getId')->willReturn(1);
        $orderShipmentTrackMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Track::class
        )->willReturn(
            $orderShipmentTrackMock
        );

        $this->_ctrlRequestMock->expects($this->at(0))->method('getParam')->with('track_id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn(false);
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateSalesOrderShipmentTrackAction();
    }

    /**
     * Test validateCheckoutAgreementEditAction
     *
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @dataProvider validateCheckoutAgreementEditActionDataProvider
     */
    public function testValidateCheckoutAgreementEditAction(
        $hasStoreAccess,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $checkoutAgreementMock = $this->getMockBuilder(Agreement::class)
            ->addMethods(['getStoreId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutAgreementMock->expects($this->any())->method('load')->willReturnSelf();
        $checkoutAgreementMock->expects($this->any())->method('getId')->willReturn(1);
        $checkoutAgreementMock->expects($this->any())->method('getStoreId')->willReturn([1]);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Agreement::class
        )->willReturn(
            $checkoutAgreementMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateCheckoutAgreementEditAction();
    }

    /**
     * Data provider for ValidateCheckoutAgreementEditAction test.
     *
     * @return array
     */
    public function validateCheckoutAgreementEditActionDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
            ]
        ];
    }

    /**
     * Test validateUrlRewriteEditAction
     *
     * @param $hasStoreAccess
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     * @dataProvider validateActionsDataProvider
     */
    public function testValidateUrlRewriteEditAction($hasStoreAccess, $expectedForwardInvoke, $getActionNameInvoke)
    {
        $urlRewriteMock = $this->getMockBuilder(UrlRewrite::class)
            ->addMethods(['getStoreId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $urlRewriteMock->expects($this->any())->method('load')->willReturnSelf();
        $urlRewriteMock->expects($this->any())->method('getId')->willReturn(1);
        $urlRewriteMock->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            UrlRewrite::class
        )->willReturn(
            $urlRewriteMock
        );

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('hasStoreAccess')->willReturn($hasStoreAccess);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateUrlRewriteEditAction();
    }

    /**
     * Validate actions data provider.
     *
     * @return array
     */
    public function validateActionsDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'hasStoreAccess' => true,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'hasStoreAccess' => false,
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->atLeastOnce(),
            ]
        ];
    }

    public function testValidateAttributeSetActions()
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validateAttributeSetActions();
    }

    public function testValidateManageCurrencyRates()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_FETCH_RATES);
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateManageCurrencyRates();
    }

    public function testValidateTransactionalEmails()
    {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_DELETE);
        $this->expectsForward($this->atLeastOnce(), $this->atLeastOnce());
        $this->_model->validateTransactionalEmails();
    }

    public function testValidatePromoCatalogApplyRules()
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->validatePromoCatalogApplyRules();
    }

    public function testPromoCatalogIndexAction()
    {
        $controllerMock = $this->getMockBuilder(ActionStub::class)
            ->addMethods(['setDirtyRulesNoticeMessage'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($this->_model, $this->_model->promoCatalogIndexAction($controllerMock));
    }

    /**
     * Test validateNoWebsiteGeneric
     *
     * @dataProvider validateNoWebsiteGenericDataProvider
     */
    public function testValidateNoWebsiteGeneric(
        $denyActions,
        $saveAction,
        $idFieldName,
        $websiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $this->_ctrlRequestMock->expects($this->any())->method('getActionName')->willReturn(Ctrl::ACTION_DELETE);
        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn(1);
        $this->_roleMock->expects($this->any())->method('getWebsiteIds')->willReturn($websiteIds);
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
        $this->_model->validateNoWebsiteGeneric($denyActions, $saveAction, $idFieldName);
    }

    /**
     * Data provider for validateNoWebsiteGeneric method.
     *
     * @return array
     */
    public function validateNoWebsiteGenericDataProvider()
    {
        return [
            'hasStoreAccess' => [
                'denyActions' => [Ctrl::ACTION_NEW, Ctrl::ACTION_DELETE],
                'saveAction' => Ctrl::ACTION_SAVE,
                'idFieldName' => 'id',
                'websiteIds' => [1],
                'expectedForwardInvoke' => $this->never(),
                'getActionNameInvoke' => $this->never(),
            ],
            'hasNoStoreAccess' => [
                'denyActions' => [Ctrl::ACTION_NEW, Ctrl::ACTION_DELETE],
                'saveAction' => Ctrl::ACTION_SAVE,
                'idFieldName' => 'id',
                'websiteIds' => null,
                'expectedForwardInvoke' => $this->atLeastOnce(),
                'getActionNameInvoke' => $this->atLeastOnce(),
            ]
        ];
    }

    public function testBlockCustomerGroupSave()
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->blockCustomerGroupSave();
    }

    public function testBlockIndexAction()
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->blockIndexAction();
    }

    public function testBlockTaxChange()
    {
        $this->expectsForward($this->never(), $this->atLeastOnce());
        $this->_model->blockTaxChange();
    }

    /**
     * Expect for customer Action.
     *
     * @param $id
     * @param $websiteId
     * @param $roleWebsiteIds
     * @param $expectedForwardInvoke
     * @param $getActionNameInvoke
     */
    protected function expectsCustomerAction(
        $id,
        $websiteId,
        $roleWebsiteIds,
        $expectedForwardInvoke,
        $getActionNameInvoke
    ) {
        $customerMock = $this->getMockBuilder(Customer::class)
            ->addMethods(['getWebsiteId'])
            ->onlyMethods(['load', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_ctrlRequestMock->expects($this->any())->method('getParam')->with('id')->willReturn($id);
        $customerMock->expects($this->any())->method('load')->with($id)->willReturnSelf();
        $customerMock->expects($this->any())->method('getWebsiteId')->willReturn($websiteId);
        $customerMock->expects($this->any())->method('getId')->willReturn($id);

        $this->_roleMock->expects($this->any())->method('getRelevantWebsiteIds')->willReturn($roleWebsiteIds);

        $this->_objectManager->expects(
            $this->any()
        )->method(
            'create'
        )->with(
            Customer::class
        )->willReturn(
            $customerMock
        );
        $this->expectsForward($expectedForwardInvoke, $getActionNameInvoke);
    }

    /**
     * Expect for controller forward action.
     *
     * @param InvokedCount|InvokedAtLeastOnce $expectedForwardInvoke
     * @param InvokedCount|InvokedAtLeastOnce $getActionNameInvokeCount
     */
    protected function expectsForward($expectedForwardInvoke, $getActionNameInvokeCount)
    {
        $this->_ctrlRequestMock->expects($expectedForwardInvoke)
            ->method('setActionName')->with(Ctrl::ACTION_DENIED)
            ->willReturnSelf();
        $this->_ctrlRequestMock->expects($getActionNameInvokeCount)
            ->method('getActionName')
            ->willReturn(Ctrl::ACTION_DENIED);
        $this->_ctrlRequestMock->expects($expectedForwardInvoke)
            ->method('setDispatched')->with(false);
    }

    /**
     * Expect for controller redirect action.
     *
     * @param InvokedCount $expectedRedirectInvoke
     */
    protected function expectsRedirect($expectedRedirectInvoke)
    {
        $this->responseMock->expects($expectedRedirectInvoke)->method('setRedirect');
    }
}
