<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\Strategy;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ForeignKey\ConstraintInterface;
use Magento\Framework\ForeignKey\Strategy\Cascade;
use Magento\Framework\ForeignKey\Strategy\DbIgnore;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbIgnoreTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $connectionMock;

    /**
     * @var Cascade
     */
    protected $strategy;

    protected function setUp(): void
    {
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $objectManager = new ObjectManager($this);
        $this->strategy = $objectManager->getObject(DbIgnore::class);
    }

    public function testProcess()
    {
        $constraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $this->connectionMock->expects($this->never())->method('delete');
        $this->strategy->process($this->connectionMock, $constraintMock, 'cond1');
    }

    public function testLockAffectedData()
    {
        $table = 'sampleTable';
        $condition = 'sampleCondition';
        $fields = [3, 75, 56, 67];

        $this->connectionMock->expects($this->never())->method('select');
        $this->connectionMock->expects($this->never())->method('fetchAssoc');
        $result = $this->strategy->lockAffectedData($this->connectionMock, $table, $condition, $fields);
        $this->assertEquals([], $result);
    }
}
