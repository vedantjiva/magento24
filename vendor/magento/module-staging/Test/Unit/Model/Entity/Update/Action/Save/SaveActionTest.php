<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action\Save;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Controller\Adminhtml\Entity\Update\Service;
use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Staging\Model\Entity\Update\Action\Save\SaveAction;
use Magento\Staging\Model\EntityStaging;
use Magento\Staging\Model\Update;
use Magento\Staging\Model\Update\UpdateValidator;
use Magento\Staging\Model\UpdateFactory;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for testing action save entity
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveActionTest extends TestCase
{
    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var HydratorInterface|MockObject
     */
    private $entityHydrator;

    /**
     * @var EntityStaging|MockObject
     */
    private $entityStaging;

    /**
     * @var Service|MockObject
     */
    private $updateService;

    /**
     * @var UpdateRepositoryInterface|MockObject
     */
    private $updateRepository;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var UpdateFactory|MockObject
     */
    private $updateFactory;

    /**
     * @var SaveAction
     */
    private $saveAction;

    /** @var  UpdateValidator|MockObject  */
    private $updateValidator;

    protected function setUp(): void
    {
        $this->updateService = $this->getMockBuilder(Service::class)
            ->disableOriginalCOnstructor()
            ->getMock();

        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityHydrator = $this->getMockBuilder(HydratorInterface::class)
            ->getMockForAbstractClass();

        $this->entityStaging = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateValidator = $this->getMockBuilder(UpdateValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateFactory = $this->getMockBuilder(UpdateFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->saveAction = new SaveAction(
            $this->updateService,
            $this->versionManager,
            $this->entityHydrator,
            $this->entityStaging,
            $this->updateRepository,
            $this->metadataPool,
            $this->updateFactory,
            $this->updateValidator
        );
    }

    /**
     * Checks the creation of new update
     *
     * @dataProvider createUpdateDataProvider
     * @param int $updateId
     */
    public function testExecuteCreateUpdate(
        $updateId
    ) {
        $newUpdateId = 32;
        $params = [
            'stagingData' => isset($updateId) ? ['update_id' => $updateId] : [],
            'entityData' => [],
        ];

        $updateMock = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($newUpdateId);
        $updateMock->expects($this->once())
            ->method('setIsCampaign')
            ->with(false)
            ->willReturnSelf();

        $this->updateFactory->expects($this->once())
            ->method('create')
            ->willReturn($updateMock);

        $hydratorMock = $this->getMockBuilder(\Magento\Framework\EntityManager\HydratorInterface::class)
            ->getMockForAbstractClass();
        $hydratorMock->expects($this->once())
            ->method('hydrate')
            ->with($updateMock, $params['stagingData'])
            ->willReturnSelf();

        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);

        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($newUpdateId);

        $this->updateRepository->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $entity = new \stdClass();
        $this->entityHydrator->expects($this->once())
            ->method('hydrate')
            ->with($params['entityData'])
            ->willReturn($entity);
        $this->entityStaging->expects($this->once())
            ->method('schedule')
            ->with($entity, $newUpdateId, []);

        $this->assertTrue($this->saveAction->execute($params));
    }

    /**
     * Data required to test new update creation
     *
     * @return array
     */
    public function createUpdateDataProvider()
    {
        return [
            ['update_id' => null],
            ['update_id' => ''],
        ];
    }

    /**
     * Checks the editing of existed update for case when no 'EndTime' parameter was changed
     */
    public function testExecuteEditUpdateWithNoEndTimeChanged()
    {
        $updateId = 1;

        $startDateTime = new \DateTime();
        $startDateTime->add(new \DateInterval('P1D'));

        $endDateTime = new \DateTime();
        $endDateTime->add(new \DateInterval('P2D'));
        $currentEndDateTime = $endDateTime->format('Y-m-d H:i:s');

        $stagingData = [
            'update_id' => 1,
            'start_time' => 1,
            'end_time' => $currentEndDateTime,
        ];

        $params = [
            'stagingData' => $stagingData,
            'entityData' => [],
        ];

        $updateMock = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();

        $updateMock->expects($this->any())
            ->method('getId')
            ->willReturn($updateId);

        $this->updateRepository->expects($this->atLeastOnce())
            ->method('get')
            ->with($updateId)
            ->willReturn($updateMock);

        $hydratorMock = $this->getMockBuilder(\Magento\Framework\EntityManager\HydratorInterface::class)
            ->getMockForAbstractClass();
        $hydratorMock->expects($this->once())
            ->method('hydrate')
            ->with($updateMock, $params['stagingData'])
            ->willReturnSelf();

        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);

        $this->versionManager->expects($this->once())
            ->method('setCurrentVersionId')
            ->with($updateId);
        $this->updateRepository->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $this->assertTrue($this->saveAction->execute($params));
    }

    /**
     * Checks the editing of existed update for case when 'EndTime' parameter was changed
     */
    public function testExecuteEditUpdateWithEndTimeChanged()
    {
        $updateId = 1;

        $startDateTime = new \DateTime();
        $startDateTime->add(new \DateInterval('P1D'));

        $endDateTime = new \DateTime();
        $endDateTime->add(new \DateInterval('P2D'));

        $endDateTimeChanged = new \DateTime();
        $endDateTimeChanged->add(new \DateInterval('P3D'));
        $currentEndDateTimeChanged = $endDateTimeChanged->format('Y-m-d H:i:s');

        $stagingData = [
            'update_id' => 1,
            'start_time' => 1,
            'end_time' => $currentEndDateTimeChanged,
        ];

        $params = [
            'stagingData' => $stagingData,
            'entityData' => [],
        ];

        $updateMock = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->expects($this->any())
            ->method('getId')
            ->willReturn($updateId);

        $this->updateRepository->expects($this->atLeastOnce())
            ->method('get')
            ->with($updateId)
            ->willReturn($updateMock);

        $hydratorMock = $this->getMockBuilder(\Magento\Framework\EntityManager\HydratorInterface::class)
            ->getMockForAbstractClass();

        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);

        $this->updateRepository->expects($this->once())
            ->method('save')
            ->willReturnSelf();

        $entity = new \stdClass();
        $this->entityHydrator->expects($this->any())
            ->method('hydrate')
            ->with($params['entityData'])
            ->willReturn($entity);
        $this->entityStaging->expects($this->any())
            ->method('schedule')
            ->with($entity, $updateId, ['origin_in' => $updateId]);

        $this->assertTrue($this->saveAction->execute($params));
    }

    /**
     * Checks the editing of existed update for case when 'StartTime' parameter was changed with Active entity
     */
    public function testExecuteEditUpdateStartTimeWithActiveEntity()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('The Start Time of this Update cannot be changed. It\'s been already started.');
        $this->saveAction = new SaveAction(
            $this->updateService,
            $this->versionManager,
            $this->entityHydrator,
            $this->entityStaging,
            $this->updateRepository,
            $this->metadataPool,
            $this->updateFactory
        );

        $startDateTime = new \DateTime();
        //set passed time for activate entity
        $startDateTime->sub(new \DateInterval('P1D'));
        $updateTime = new \DateTime();
        $endDateTimeChanged = new \DateTime();
        $endDateTimeChanged->add(new \DateInterval('P3D'));
        $currentEndDateTimeChanged = $endDateTimeChanged->format('Y-m-d H:i:s');

        $stagingData = [
            'update_id' => 1,
            'start_time' => $startDateTime->format('Y-m-d H:i:s'),
            'end_time' => $currentEndDateTimeChanged,
        ];
        $params = [
            'stagingData' => $stagingData,
            'entityData' => [],
        ];

        $updateMock = $this->getMockBuilder(Update::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updateMock->method('getId')
            ->willReturn(1);
        $updateMock->method('getStartTime')
            ->willReturn($updateTime->format('Y-m-d H:i:s'));
        $this->updateRepository->expects($this->atLeastOnce())
            ->method('get')
            ->with(1)
            ->willReturn($updateMock);
        $hydratorMock = $this->getMockBuilder(\Magento\Framework\EntityManager\HydratorInterface::class)
            ->getMockForAbstractClass();
        $this->metadataPool->method('getHydrator')
            ->with(UpdateInterface::class)
            ->willReturn($hydratorMock);
        $this->updateRepository->method('save')
            ->willReturnSelf();
        $entity = new \stdClass();
        $this->entityHydrator->method('hydrate')
            ->with($params['entityData'])
            ->willReturn($entity);
        $this->entityStaging->method('schedule')
            ->with($entity, 1, ['origin_in' => 1]);

        $this->saveAction->execute($params);
    }
}
