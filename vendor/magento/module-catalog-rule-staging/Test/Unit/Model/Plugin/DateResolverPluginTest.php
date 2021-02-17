<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Model\Plugin;

use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRuleStaging\Model\Plugin\DateResolverPlugin;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateResolverPluginTest extends TestCase
{
    /**
     * @var DateResolverPlugin
     */
    private $subject;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $localeDateMock;

    protected function setUp(): void
    {
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
        $this->localeDateMock = $this->getMockForAbstractClass(TimezoneInterface::class);

        $this->subject = new DateResolverPlugin(
            $this->updateRepositoryMock,
            $this->localeDateMock
        );
    }

    public function testBeforeGetFromDate()
    {
        $versionId = 100;
        $startTime = '2019-01-01 00:00:00';

        $this->localeDateMock->expects($this->once())
            ->method('date')
            ->with(new \DateTime($startTime))
            ->willReturn(new \DateTime($startTime, new \DateTimeZone('UTC')));

        $ruleMock = $this->createMock(Rule::class);
        $ruleMock->expects($this->at(0))
            ->method('getData')
            ->with('campaign_id')
            ->willReturn(null);
        $ruleMock->expects($this->at(1))
            ->method('getData')
            ->with('created_in')
            ->willReturn($versionId);
        $ruleMock->expects($this->once())
            ->method('setData')
            ->with('from_date', $startTime)
            ->willReturnSelf();

        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($versionId)
            ->willReturn($updateMock);
        $updateMock->expects($this->once())
            ->method('getStartTime')
            ->willReturn($startTime);

        $this->subject->beforeGetFromDate($ruleMock);
    }

    public function testBeforeGetToDate()
    {
        $versionId = 100;
        $endTime = '2019-12-31 23:59:59';

        $this->localeDateMock->expects($this->once())
            ->method('date')
            ->with(new \DateTime($endTime))
            ->willReturn(new \DateTime($endTime, new \DateTimeZone('UTC')));

        $ruleMock = $this->createMock(Rule::class);
        $ruleMock->expects($this->at(0))
            ->method('getData')
            ->with('campaign_id')
            ->willReturn(null);
        $ruleMock->expects($this->at(1))
            ->method('getData')
            ->with('created_in')
            ->willReturn($versionId);
        $ruleMock->expects($this->once())
            ->method('setData')
            ->with('to_date', $endTime)
            ->willReturnSelf();

        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($versionId)
            ->willReturn($updateMock);
        $updateMock->expects($this->once())
            ->method('getEndTime')
            ->willReturn($endTime);

        $this->subject->beforeGetToDate($ruleMock);
    }
}
