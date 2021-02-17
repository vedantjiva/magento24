<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Visitor;
use Magento\CustomerSegment\Helper\Data;
use Magento\CustomerSegment\Model\Customer;
use Magento\CustomerSegment\Model\ResourceModel\Segment\Collection;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Session\Storage;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerTest extends TestCase
{
    /**
     * @var Customer
     */
    private $model;

    /**
     * @var MockObject
     */
    private $_registry;

    /**
     * @var MockObject
     */
    private $_customerSession;

    /**
     * @var MockObject
     */
    private $_resource;

    /**
     * @var CollectionFactory|MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var Collection|MockObject
     */
    private $collection;

    /**
     * @var Visitor|MockObject
     */
    protected $visitorMock;

    /**
     * @var Context|MockObject
     */
    protected $httpContextMock;

    /**
     * @var array
     */
    private $_fixtureSegmentIds = [123, 456];

    /**
     * @var int
     */
    private $websiteId = 5;

    protected function setUp(): void
    {
        $this->_registry = $this->createPartialMock(Registry::class, ['registry']);

        $website = new DataObject(['id' => $this->websiteId]);
        $storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $storeManager->expects($this->any())->method('getWebsite')->willReturn($website);

        $objectManager = new ObjectManager($this);
        $constructArguments = $objectManager->getConstructArguments(
            Session::class,
            ['storage' => new Storage()]
        );
        $this->_customerSession = $this->getMockBuilder(Session::class)
            ->setMethods(['getCustomer', 'getCustomerSegmentIds', 'setCustomerSegmentIds'])
            ->setConstructorArgs($constructArguments)
            ->getMock();

        $contextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $contextMock->expects($this->once())
            ->method('getResources')
            ->willReturn($this->createMock(ResourceConnection::class));
        $this->_resource = $this->getMockBuilder(\Magento\CustomerSegment\Model\ResourceModel\Customer::class)
            ->setMethods(['getCustomerWebsiteSegments', 'getIdFieldName', 'addCustomerToWebsiteSegments'])
            ->setConstructorArgs(
                [
                    $contextMock,
                    $this->createMock(DateTime::class)
                ]
            )
            ->getMock();
        $this->collectionFactoryMock = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->collection =  $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addWebsiteFilter',
                    'addFieldToFilter',
                    'addIsActiveFilter',
                    'addEventFilter',
                    'getAllIds',
                    'getIterator'
                ]
            )
            ->getMock();
        $this->collectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->collection);
        $this->visitorMock = $this->createMock(Visitor::class);

        $this->httpContextMock = $this->createMock(Context::class);

        $helper = new ObjectManager($this);

        $this->model = $helper->getObject(
            Customer::class,
            [
                'registry' => $this->_registry,
                'resource' => $this->_resource,
                'resourceCustomer' => $this->createMock(\Magento\Customer\Model\ResourceModel\Customer::class),
                'visitor' => $this->visitorMock,
                'storeManager' => $storeManager,
                'customerSession' => $this->_customerSession,
                'httpContext' => $this->httpContextMock,
                'collectionFactory' => $this->collectionFactoryMock
            ]
        );
    }

    protected function tearDown(): void
    {
        $this->model = null;
        $this->_registry = null;
        $this->_customerSession = null;
        $this->_resource = null;
    }

    public function testGetCurrentCustomerSegmentIdsCustomerInRegistry()
    {
        $customer = new DataObject(['id' => 100500]);
        $this->_registry->expects(
            $this->once()
        )->method(
            'registry'
        )->with(
            'segment_customer'
        )->willReturn(
            $customer
        );
        $this->_resource->expects(
            $this->once()
        )->method(
            'getCustomerWebsiteSegments'
        )->with(
            100500,
            5
        )->willReturn(
            $this->_fixtureSegmentIds
        );
        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    /**
     * @return void
     */
    public function testGetCurrentCustomerSegmentIdsCustomerInRegistryNoId(): void
    {
        $customer = new DataObject();
        $this->_registry->expects($this->once())
            ->method('registry')
            ->with('segment_customer')
            ->willReturn($customer);
        $this->_customerSession->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn([$this->websiteId => $this->_fixtureSegmentIds]);
        $this->collection->expects($this->any())
            ->method('addIsActiveFilter')
            ->with(1);

        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    public function testGetCurrentCustomerSegmentIdsCustomerInSession()
    {
        $customer = new DataObject(['id' => 100500]);
        $this->_customerSession->expects($this->once())->method('getCustomer')->willReturn($customer);
        $this->_resource->expects(
            $this->once()
        )->method(
            'getCustomerWebsiteSegments'
        )->with(
            100500,
            5
        )->willReturn(
            $this->_fixtureSegmentIds
        );
        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    /**
     * @return void
     */
    public function testGetCurrentCustomerSegmentIdsCustomerInSessionNoId(): void
    {
        $customer = new DataObject();
        $this->_customerSession->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);
        $this->_customerSession->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn([$this->websiteId => $this->_fixtureSegmentIds]);
        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    public function testProcessEventForVisitor()
    {
        $event = 'test_event';
        $customerSegment = $this->createPartialMock(
            Segment::class,
            ['validateCustomer']
        );
        $customerSegment->expects($this->once())->method('validateCustomer')->willReturn(true);
        $customerSegment->setData('apply_to', Segment::APPLY_TO_VISITORS);
        $customerSegment->setData('id', 'segment_id');

        $this->collection->expects($this->once())->method('addEventFilter')->with($event)->willReturnSelf();
        $this->collection->expects($this->once())->method('addWebsiteFilter')->with(5)->willReturnSelf();
        $this->collection->expects($this->once())->method('addIsActiveFilter')->with(1)->willReturnSelf();
        $this->collection->expects($this->once())->method('getIterator')->willReturn(
            new \ArrayIterator([$customerSegment])
        );

        $this->visitorMock->setData('id', 'visitor_1');
        $this->visitorMock->setData('quote_id', 'quote_1');

        $this->assertEquals($this->model, $this->model->processEvent($event, null, 1));
    }

    /**
     * @param mixed $visitorSegmentIds
     * @param int $websiteId
     * @param array $segmentIds
     * @param array $resultSegmentIds
     * @param array $contextSegmentIds
     *
     * @dataProvider dataProviderAddVisitorToWebsiteSegments
     */
    public function testAddVisitorToWebsiteSegments(
        $visitorSegmentIds,
        $websiteId,
        array $segmentIds,
        array $resultSegmentIds,
        array $contextSegmentIds
    ) {
        /**
         * @var SessionManagerInterface|MockObject $sessionMock
         */
        $sessionMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->setMethods(['getCustomerSegmentIds', 'setCustomerSegmentIds'])
            ->getMockForAbstractClass();
        $sessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn($visitorSegmentIds);
        $sessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($resultSegmentIds);

        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(Data::CONTEXT_SEGMENT, $contextSegmentIds, $contextSegmentIds)
            ->willReturnSelf();

        $this->assertEquals(
            $this->model,
            $this->model->addVisitorToWebsiteSegments($sessionMock, $websiteId, $segmentIds)
        );
    }

    public function dataProviderAddVisitorToWebsiteSegments()
    {
        return [
            ['', 1, [], [1 => []], []],
            [[1 => [2, 3], 2 => [4]], 1, [2, 5], [1 => [2, 3, 3 => 5], 2 => [4]], [2, 3, 3 => 5]],
            [[1 => [2, 3], 3 => [4]], 2, [2, 5], [1 => [2, 3], 2 => [2, 5], 3 => [4]], [2, 5]],
            [[2 => [2, 3]], 2, [], [2 => [2, 3]], [2, 3]],
        ];
    }

    /**
     * @param mixed $visitorSegmentIds
     * @param int $websiteId
     * @param array $segmentIds
     * @param array $resultSegmentIds
     * @param array $contextSegmentIds
     *
     * @dataProvider dataProviderRemoveVisitorFromWebsiteSegments
     */
    public function testRemoveVisitorFromWebsiteSegments(
        $visitorSegmentIds,
        $websiteId,
        array $segmentIds,
        array $resultSegmentIds,
        array $contextSegmentIds
    ) {
        /**
         * @var SessionManagerInterface|MockObject $sessionMock
         */
        $sessionMock = $this->getMockBuilder(SessionManagerInterface::class)
            ->setMethods(['getCustomerSegmentIds', 'setCustomerSegmentIds'])
            ->getMockForAbstractClass();
        $sessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn($visitorSegmentIds);
        $sessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($resultSegmentIds);

        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(Data::CONTEXT_SEGMENT, $contextSegmentIds, $contextSegmentIds)
            ->willReturnSelf();

        $this->assertEquals(
            $this->model,
            $this->model->removeVisitorFromWebsiteSegments($sessionMock, $websiteId, $segmentIds)
        );
    }

    /**
     * @param array $segmentIds
     * @param array $existingSegmentIds
     * @param array $value
     * @param array $visitorCustomerSegmentIds
     * @param array $resultCustomerSegmentIds
     *
     * @dataProvider dataProviderAddCustomerToWebsiteSegments
     *
     * @return void
     */
    public function testAddCustomerToWebsiteSegments(
        array $segmentIds,
        array $existingSegmentIds,
        array $value,
        array $visitorCustomerSegmentIds,
        array $resultCustomerSegmentIds
    ): void {
        $customerId = 5;
        $websiteId = 1;

        $this->_resource->expects($this->once())
            ->method('getCustomerWebsiteSegments')
            ->with($customerId, $websiteId)
            ->willReturn($existingSegmentIds);

        $this->_resource->expects($this->once())
            ->method('addCustomerToWebsiteSegments')
            ->with($customerId, $websiteId, $segmentIds)
            ->willReturnSelf();

        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(Data::CONTEXT_SEGMENT, $value, $value)
            ->willReturnSelf();

        $this->_customerSession->expects($this->any())
            ->method('getCustomerSegmentIds')
            ->willReturn($visitorCustomerSegmentIds);

        $this->_customerSession->expects($this->any())
            ->method('setCustomerSegmentIds')
            ->with($resultCustomerSegmentIds)
            ->willReturnSelf();

        $this->model->addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds);
    }

    public function dataProviderRemoveVisitorFromWebsiteSegments()
    {
        return [
            ['', 1, [], [], []],
            [[1 => [2, 3], 2 => [4]], 1, [2, 5], [1 => [1 => 3], 2 => [4]], [1 => 3]],
            [[1 => [2, 3], 3 => [4]], 2, [2, 5], [1 => [2, 3], 3 => [4]], []],
            [[2 => [2, 3]], 2, [], [2 => [2, 3]], [2, 3]],
            [[2 => [2, 3]], 2, [2, 3], [2 => []], []],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderAddCustomerToWebsiteSegments(): array
    {
        return [
            [[], [], [], [1 => []], [1 => []]],
            [[], ['1'], ['1'], [1 => []], [1 => ['1']]],
            [['1','2'], [], ['1','2'], [1 => ['1']], [1 => [0 => '1', 2 => '2']]],
            [['1','2'], ['3'], ['3', '1', '2'], [1 => ['1', '2']], [1 => ['1', '2', '3']]],
        ];
    }
}
