<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Update\UpdateValidator;
use PHPUnit\Framework\TestCase;

class UpdateValidatorTest extends TestCase
{
    /** @var UpdateValidator */
    private $model;

    /** @var ObjectManagerHelper */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->model = $this->objectManagerHelper->getObject(
            UpdateValidator::class
        );
    }

    public function testValidateUpdateStartedExecption()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('The Start Time of this Update cannot be changed. It\'s been already started.');
        $updateId = 1;
        $stagingData = [
            'update_id' => $updateId,
            'start_time' => '2017-02-02 02:02:02',
        ];

        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $result= $this->model->validateUpdateStarted($updateMock, $stagingData);
        $this->assertNull($result);
    }

    public function testValidateUpdateStarted()
    {
        $updateId = 1;
        $stagingData = [
            'update_id' => $updateId,
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 year')),
        ];

        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $result = $this->model->validateUpdateStarted($updateMock, $stagingData);
        $this->assertNull($result);
    }

    public function testValidateParams()
    {
        $params = [
            'stagingData' => [],
            'entityData' => []
        ];

        $result = $this->model->validateParams($params);
        $this->assertNull($result);
    }

    public function testValidateWithInvalidParam()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The required parameter is "stagingData". Set parameter and try again.');
        $params = [
            'entityData' => []
        ];

        $this->model->validateParams($params);
    }
}
