<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Plugin\RestPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestPluginTest extends TestCase
{
    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var FrontControllerInterface|MockObject
     */
    private $subject;

    /**
     * @var RequestInterface|MockObject
     */
    private $appRequest;

    /**
     * @var RestPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCurrentVersionId'])
            ->getMock();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequestData'])
            ->getMock();

        $this->subject = $this->getMockForAbstractClass(FrontControllerInterface::class);

        $this->appRequest = $this->getMockForAbstractClass(RequestInterface::class);

        $this->plugin = new RestPlugin($this->versionManager, $this->request);
    }

    /**
     * @covers \Magento\Staging\Plugin\RestPlugin::beforeDispatch
     */
    public function testBeforeDispatchWithoutVersion()
    {
        $this->request->expects(static::once())
            ->method('getRequestData')
            ->willReturn([]);

        $this->versionManager->expects(static::never())
            ->method('setCurrentVersionId');

        $this->plugin->beforeDispatch($this->subject, $this->appRequest);
    }

    /**
     * @covers \Magento\Staging\Plugin\RestPlugin::beforeDispatch
     */
    public function testBeforeDispatch()
    {
        $version = 278328;

        $this->request->expects(static::once())
            ->method('getRequestData')
            ->willReturn([
                VersionManager::PARAM_NAME => $version
            ]);

        $this->versionManager->expects(static::once())
            ->method('setCurrentVersionId')
            ->with($version);

        $this->plugin->beforeDispatch($this->subject, $this->appRequest);
    }
}
