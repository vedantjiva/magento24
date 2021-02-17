<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Service;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma\PermissionChecker;
use Magento\Rma\Model\Service\RmaManagement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RmaManagementTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Permission checker
     *
     * @var PermissionChecker|MockObject
     */
    protected $permissionCheckerMock;

    /**
     * Rma repository
     *
     * @var RmaRepositoryInterface|MockObject
     */
    protected $rmaRepositoryMock;

    /**
     * @var RmaManagement
     */
    protected $rmaManagement;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->permissionCheckerMock = $this->createMock(PermissionChecker::class);
        $this->rmaRepositoryMock = $this->getMockForAbstractClass(
            RmaRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->rmaManagement = $this->objectManager->getObject(
            RmaManagement::class,
            [
                'permissionChecker' => $this->permissionCheckerMock,
                'rmaRepository' => $this->rmaRepositoryMock
            ]
        );
    }

    /**
     * Run test saveRma method
     *
     * @return void
     */
    public function testSaveRma()
    {
        $rmaMock = $this->getMockForAbstractClass(
            RmaInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->permissionCheckerMock->expects($this->once())
            ->method('checkRmaForCustomerContext');
        $this->rmaRepositoryMock->expects($this->once())
            ->method('save')
            ->with($rmaMock)
            ->willReturn(true);

        $this->assertTrue($this->rmaManagement->saveRma($rmaMock));
    }

    /**
     * Run test search method
     *
     * @return void
     */
    public function testSearch()
    {
        $expectedResult = 'test-result';

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaResultMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionCheckerMock->expects($this->once())
            ->method('checkRmaForCustomerContext');
        $this->rmaRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaResultMock)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->rmaManagement->search($searchCriteriaMock));
    }
}
