<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Model;

/**
 * Test for Magento\AdminGws\Model\Controllers
 */
class ControllersTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\AdminGws\Model\Controllers
     */
    protected $model;

    /**
     * @var \Magento\AdminGws\Model\Role|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $roleMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Store\Model\StoreManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleMock = $this->createMock(\Magento\AdminGws\Model\Role::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);

        $this->model = $this->_objectManager->create(
            \Magento\AdminGws\Model\Controllers::class,
            [
                'role' => $this->roleMock,
                'registry' => $this->registryMock,
                'storeManager' => $this->storeManagerMock,
                'request' => $this->requestMock
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->roleMock = null;
        $this->registryMock = null;
        $this->storeManagerMock = null;
        $this->requestMock = null;
        $this->model = null;
        parent::tearDown();
    }

    /**
     * User role has access to specific store view scope. No redirect should be expected in this case.
     */
    public function testValidateSystemConfigValidStoreCodeWithStoreAccess()
    {
        $this->requestMock->expects($this->any())->method('getParam')->with('store')->willReturn(
            'testStore'
        );

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);

        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->willReturn(true);

        $this->model->validateSystemConfig();
    }

    /**
     * User role has access to specific website view scope. No redirect should be expected in this case.
     */
    public function testValidateSystemConfigValidWebsiteCodeWithWebsiteAccess()
    {
        $this->requestMock->expects($this->at(0))->method('getParam')->with('store')->willReturn(
            null
        );

        $this->requestMock->expects($this->at(1))->method('getParam')->with('website')->willReturn(
            'testWebsite'
        );

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);

        $this->model->validateSystemConfig();
    }

    /**
     * User role has no access to specific store view scope or website. Redirect to first allowed website
     */
    public function testValidateSystemConfigRedirectToWebsite()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(
            null
        );

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $this->storeManagerMock->expects($this->any())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);

        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(true);

        $this->model->validateSystemConfig();
        $this->assertRedirect();
    }

    /**
     * User role has no access to specific store view scope or website. Redirect to first allowed store view.
     */
    public function testValidateSystemConfigRedirectToStore()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(
            null
        );

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite', 'getCode', 'getId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);
        $storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('base');
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);

        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(false);

        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->with(1)
            ->willReturn(true);

        $this->model->validateSystemConfig();
        $this->assertRedirect();
    }

    /**
     * User role has no access to any store view scope or website. Redirect to access denied page.
     */
    public function testValidateSystemConfigRedirectToDenied()
    {
        $this->requestMock->expects($this->any())->method('getParam')->willReturn(
            null
        );

        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCode'])
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getCode')
            ->willReturn('default');

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite', 'getCode', 'getId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);
        $storeMock->expects($this->any())
            ->method('getCode')
            ->willReturn('base');
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->storeManagerMock->expects($this->any())
            ->method('getDefaultStoreView')
            ->willReturn($storeMock);
        $this->storeManagerMock->expects($this->any())
            ->method('getStores')
            ->willReturn([$storeMock]);

        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(false);

        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->with(1)
            ->willReturn(false);

        $this->model->validateSystemConfig();
        $this->assertRedirect($this->stringContains('admin/noroute'));
    }

    /**
     * Test when system store is validated to be matched
     */
    public function testValidateSystemStoreMatched()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(true);
        $this->model->validateSystemStore();
    }

    /**
     * Test "save" action when request is forwarded to website view
     */
    public function testValidateSystemStoreActionNameSaveForwardToWebsite()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('save');
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(['website' => 'testWebsite']);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "save" action when request is forwarded to store view
     */
    public function testValidateSystemStoreActionNameSaveForwardToStore()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('save');
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn(['website' => null, 'store' => 'testStore']);
        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "newWebsite" action
     */
    public function testValidateSystemStoreActionNameNewWebsite()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('newWebsite');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "newGroup" action
     */
    public function testValidateSystemStoreActionNameNewGroup()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('newGroup');
        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "newStore" action
     */
    public function testValidateSystemStoreActionNameNewStore()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('newStore');
        $this->roleMock->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "editWebsite" action
     */
    public function testValidateSystemStoreActionNameEditWebsite()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('editWebsite');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(null);
        $this->model->validateSystemStore();
    }

    /**
     * Test "editGroup" action
     */
    public function testValidateSystemStoreActionNameEditGroup()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('editGroup');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->roleMock->expects($this->any())
            ->method('hasStoreGroupAccess')
            ->willReturn(null);
        $this->model->validateSystemStore();
    }

    /**
     * Test "editStore" action
     */
    public function testValidateSystemStoreActionNameEditStore()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('editStore');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->roleMock->expects($this->any())
            ->method('hasStoreAccess')
            ->willReturn(null);
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteWebsite" action
     */
    public function testValidateSystemStoreActionNameDeleteWebsite()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteWebsite');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteWebsitePost" action
     */
    public function testValidateSystemStoreActionNameDeleteWebsitePost()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteWebsitePost');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteGroup" action
     */
    public function testValidateSystemStoreActionNameDeleteGroup()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteGroup');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteGroupPost" action with website access
     */
    public function testValidateSystemStoreActionNameDeleteGroupPostHasWebsiteAccess()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteGroupPost');
        $groupMock = $this->getMockBuilder(\Magento\Store\Model\Group::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();
        $groupMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->roleMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($groupMock);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteGroupPost" action with no website access
     */
    public function testValidateSystemStoreActionNameDeleteGroupPostNoWebsiteAccess()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteGroupPost');
        $groupMock = $this->getMockBuilder(\Magento\Store\Model\Group::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();
        $groupMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->roleMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($groupMock);
        $this->roleMock->expects($this->any())
            ->method('getGroup')
            ->willReturn(true);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(false);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteStore" action
     */
    public function testValidateSystemStoreActionNameDeleteStore()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteStore');
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteStorePost" action with website access
     */
    public function testValidateSystemStoreActionNameDeleteStorePostHasWebsiteAccess()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteStorePost');
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(true);
        $this->model->validateSystemStore();
    }

    /**
     * Test "deleteStorePost" action with no website access
     */
    public function testValidateSystemStoreActionNameDeleteStorePostNoWebsiteAccess()
    {
        $this->registryMock->expects($this->any())
            ->method('registry')
            ->willReturn(null);
        $this->requestMock->expects($this->any())
            ->method('getActionName')
            ->willReturn('deleteStorePost');
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('testWebsite');
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($storeMock);
        $this->roleMock->expects($this->any())
            ->method('hasWebsiteAccess')
            ->willReturn(false);
        $this->requestMock->expects($this->any())
            ->method('setActionName')
            ->willReturnSelf();
        $this->model->validateSystemStore();
    }
}
