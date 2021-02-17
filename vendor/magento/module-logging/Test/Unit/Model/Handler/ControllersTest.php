<?php declare(strict_types=1);
/**
 * Test \Magento\Logging\Model\Config
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Test\Unit\Model\Handler;

use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\Event\Changes;
use Magento\Logging\Model\Event\ChangesFactory;
use Magento\Logging\Model\Handler\Controllers;
use Magento\Logging\Model\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ControllersTest extends TestCase
{
    /**
     * @var Controllers
     */
    protected $object;

    /**
     * @var Http|MockObject
     */
    protected $request;

    /**
     * @var ChangesFactory|MockObject
     */
    protected $eventChangesFactory;

    /**
     * @var Changes|MockObject
     */
    protected $eventChanges;

    /**
     * @var Structure|MockObject
     */
    protected $configStructure;

    /**
     * @var Processor|MockObject
     */
    protected $processor;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->request = $this->createMock(Http::class);
        $this->request->expects($this->any())->method('getParams')->willReturn([]);

        $this->eventChanges = new DataObject();
        $this->eventChangesFactory = $this->createPartialMock(
            ChangesFactory::class,
            ['create']
        );
        $this->eventChangesFactory->expects(
            $this->any()
        )->method(
            'create'
        )->willReturn(
            $this->eventChanges
        );

        $this->configStructure = $this->createPartialMock(
            Structure::class,
            ['getFieldPathsByAttribute']
        );
        $this->configStructure->expects(
            $this->any()
        )->method(
            'getFieldPathsByAttribute'
        )->willReturn(
            []
        );

        $this->object = $objectManager->getObject(
            Controllers::class,
            [
                'request' => $this->request,
                'eventChangesFactory' => $this->eventChangesFactory,
                'structureConfig' => $this->configStructure
            ]
        );

        $this->processor = $this->createMock(Processor::class);
    }

    /**
     * @dataProvider postDispatchReportDataProvider
     */
    public function testPostDispatchReport($config, $expectedInfo)
    {
        $helper = new ObjectManager($this);
        $eventModel = $helper->getObject(Event::class);
        $processor = $this->getMockBuilder(
            Processor::class
        )->disableOriginalConstructor()
            ->getMock();

        $result = $this->object->postDispatchReport($config, $eventModel, $processor);
        if (is_object($result)) {
            $result = $result->getInfo();
        }
        $this->assertEquals($expectedInfo, $result);
    }

    /**
     * @return array
     */
    public function postDispatchReportDataProvider()
    {
        return [
            [['controller_action' => 'reports_report_shopcart_product'], 'shopcart_product'],
            [['controller_action' => 'some_another_value'], false]
        ];
    }

    /**
     * Assure that method works when post data contains group without ['fields'] key
     */
    public function testPostDispatchConfigSaveGroupWithoutFieldsKey()
    {
        $this->request->expects(
            $this->once()
        )->method(
            'getPostValue'
        )->willReturn(
            ['groups' => ['name' => []]]
        );

        $this->assertEquals(
            ['info' => 'general'],
            $this->object->postDispatchConfigSave([], new DataObject(), $this->processor)->getData()
        );
    }
}
