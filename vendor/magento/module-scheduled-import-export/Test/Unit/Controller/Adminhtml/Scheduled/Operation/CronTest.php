<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScheduledImportExport\Test\Unit\Controller\Adminhtml\Scheduled\Operation;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Area;
use Magento\Framework\App\Console\Request;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\View\DesignInterface;
use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation;
use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation\Cron;
use Magento\ScheduledImportExport\Model\Observer;
use Magento\Theme\Model\Theme;
use Magento\Theme\Model\View\Design;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CronTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirectMock;

    protected function setUp(): void
    {
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
    }

    public function testCronActionFrontendAreaIsSetToDesignBeforeProcessOperation()
    {
        $designTheme = 'Magento/blank';

        $observer = $this->createPartialMock(
            Observer::class,
            ['processScheduledOperation']
        );

        $theme = $this->createMock(Theme::class);

        $design = $this->createPartialMock(
            Design::class,
            ['getArea', 'getDesignTheme', 'getConfigurationDesignTheme', 'setDesignTheme']
        );
        $design->expects($this->once())->method('getArea')
            ->willReturn('adminhtml');
        $design->expects($this->once())->method('getDesignTheme')
            ->willReturn($theme);
        $design->expects($this->once())->method('getConfigurationDesignTheme')
            ->with(Area::AREA_FRONTEND)
            ->willReturn($designTheme);

        $design->expects($this->at(3))->method('setDesignTheme')
            ->with($designTheme, Area::AREA_FRONTEND);
        $design->expects($this->at(4))->method('setDesignTheme')
            ->with($theme, 'adminhtml');

        $request = $this->createPartialMock(Request::class, ['getParam']);
        $request->expects($this->once())->method('getParam')
            ->with('operation')
            ->willReturn('2');

        $objectManagerMock = $this->createPartialMock(ObjectManager::class, ['get']);
        $objectManagerMock->expects($this->at(0))->method('get')
            ->with(DesignInterface::class)
            ->willReturn($design);
        $objectManagerMock->expects($this->at(1))->method('get')
            ->with(Observer::class)
            ->willReturn($observer);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $context = $objectManager->getObject(
            Context::class,
            [
                'request' => $request,
                'objectManager' => $objectManagerMock,
                'resultFactory' => $this->resultFactoryMock
            ]
        );

        /** @var Operation $instance */
        $instance = $objectManager->getObject(
            Cron::class,
            ['context' => $context]
        );

        $this->assertSame($this->resultRedirectMock, $instance->execute());
    }
}
