<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Plugin;

use Magento\Catalog\Model\Category;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Plugin\UpdateCachePlugin;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateCachePluginTest extends TestCase
{
    /**
     * @var ConfigInterface|MockObject
     */
    private $permissionsConfigMock;

    /**
     * @var Registry|MockObject
     */
    private $coreRegistryMock;

    /**
     * @var Session|MockObject
     */
    private $customerSession;

    /**
     * @var UpdateCachePlugin
     */
    private $plugin;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->permissionsConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->coreRegistryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            UpdateCachePlugin::class,
            [
                'coreRegistry' => $this->coreRegistryMock,
                'customerSession' => $this->customerSession,
                'permissionsConfig' => $this->permissionsConfigMock,
            ]
        );
    }

    /**
     * @param bool $isEnabled
     * @param array $data
     * @param array $expected
     * @return void
     * @dataProvider afterGetDataDataProvider
     */
    public function testAfterGetData($isEnabled, $data, $expected)
    {
        $categoryId = 2;
        $customerGroupId = 3;
        $this->permissionsConfigMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn($isEnabled);

        /** @var Category|MockObject $categoryMock */
        $categoryMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryMock->method('getId')
            ->willReturn($categoryId);

        $this->coreRegistryMock->method('registry')
            ->willReturn($categoryMock);

        $this->customerSession->method('getCustomerGroupId')
            ->willReturn($customerGroupId);

        /** @var HttpContext|MockObject $httpContext */
        $httpContext = $this->getMockBuilder(HttpContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = $this->plugin->afterGetData($httpContext, $data);
        $this->assertEquals($expected, $data);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function afterGetDataDataProvider()
    {
        return [
            [
                true,
                ['string' => 'abc', 'int' => 42, 'bool' => true],
                ['string' => 'abc', 'int' => 42, 'bool' => true, 'customer_group' => 3, 'category' => 2],
            ],
            [
                false,
                ['string' => 'abc', 'int' => 42, 'bool' => true],
                ['string' => 'abc', 'int' => 42, 'bool' => true],
            ]
        ];
    }
}
