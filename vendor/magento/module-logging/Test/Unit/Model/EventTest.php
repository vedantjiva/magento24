<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Test\Unit\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Model\Event;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Event
     */
    protected $object;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonMock;

    protected function setUp(): void
    {
        $event = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getEventDispatcher')
            ->willReturn($event);

        $registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userFactoryMock = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFieldName', '_construct', 'getConnection'])
            ->getMock();
        $resourceMock->method('getIdFieldName')
            ->willReturn('some_id');

        $resourceCollectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->object = $this->objectManager->getObject(
            Event::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'userFactory' => $userFactoryMock,
                'resource' => $resourceMock,
                'resourceCollection' => $resourceCollectionMock,
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * We set some initial data in the format that the method will use,
     * then we run the method and ensure that the initial data is not lost and is converted into Json string
     */
    public function testBeforeSave()
    {
        $info = [
            "string" => "value",
            "number" => 42
        ];
        $additionalInfo = [
            "bool" => true,
            "collection" => [1, 2, 3]
        ];

        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $resultData = json_encode(["general" => $info, "additional" => $additionalInfo]);

        $this->object->setId(1);
        $this->object->setInfo($info);
        $this->object->setAdditionalInfo($additionalInfo);

        $this->object->beforeSave();

        $this->assertNotEmpty($this->object->getInfo());
        $this->assertEquals($resultData, $this->object->getInfo());
    }
}
