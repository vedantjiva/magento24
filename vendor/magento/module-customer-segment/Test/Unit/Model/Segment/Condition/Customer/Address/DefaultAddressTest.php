<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Customer\Address;

use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\DefaultAddress;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultAddressTest extends TestCase
{
    /**
     * Subject of testing.
     *
     * @var DefaultAddress
     */
    protected $subject;

    /**
     * @var Segment|MockObject
     */
    protected $resourceSegmentMock;

    /**
     * @var Attribute|MockObject
     */
    protected $attributeMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var Select|MockObject
     */
    protected $selectMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->resourceSegmentMock = $this->createPartialMock(
            Segment::class,
            ['createSelect']
        );

        $this->selectMock = $this->createPartialMock(Select::class, ['from', 'where', 'limit']);

        $this->resourceSegmentMock->expects($this->any())
            ->method('createSelect')
            ->willReturn($this->selectMock);

        $this->eavConfigMock = $this->createPartialMock(Config::class, ['getAttribute']);

        $this->attributeMock = $this->createPartialMock(
            Attribute::class,
            ['isStatic', 'getBackendTable', 'getAttributeCode', 'getId']
        );

        $this->attributeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->attributeMock->expects($this->any())
            ->method('getBackendTable')
            ->willReturn('data_table');

        $this->attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('default_billing');

        $this->eavConfigMock->expects($this->any())
            ->method('getAttribute')
            ->with('customer', 'default_billing')
            ->willReturn($this->attributeMock);

        $this->subject = $objectManager->getObject(
            DefaultAddress::class,
            [
                'resourceSegment' => $this->resourceSegmentMock,
                'eavConfig' => $this->eavConfigMock
            ]
        );

        $this->subject->setData('value', 'default_billing');
    }

    /**
     * @param bool $customer
     * @param bool $isFiltered
     * @param bool|null $isStaticAttribute
     * @dataProvider getConditionsSqlDataProvider
     * @return void
     */
    public function testGetConditionsSql($customer, $isFiltered, $isStaticAttribute)
    {
        if (!$customer && $isFiltered) {
            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['' => new \Zend_Db_Expr('dual')], [new \Zend_Db_Expr(0)]);

            $this->selectMock->expects($this->never())
                ->method('where');

            $this->selectMock->expects($this->never())
                ->method('limit');
        } else {
            $this->attributeMock->expects($this->any())
                ->method('isStatic')
                ->willReturn($isStaticAttribute);

            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['default' => 'data_table'], [new \Zend_Db_Expr(1)]);

            if (!$isStaticAttribute) {
                $this->selectMock->expects($this->at(1))
                    ->method('where')
                    ->with("`default`.`attribute_id` = ?", 1)
                    ->willReturnSelf();

                $this->selectMock->expects($this->at(2))
                    ->method('where')
                    ->with("`default`.`value` = `customer_address`.`entity_id`");

                if ($isFiltered) {
                    $this->selectMock->expects($this->at(3))
                        ->method('where')
                        ->with("default.entity_id = :customer_id");
                }
            } else {
                $this->selectMock->expects($this->at(1))
                    ->method('where')
                    ->with("`default`.`default_billing` = `customer_address`.`entity_id`");

                if ($isFiltered) {
                    $this->selectMock->expects($this->at(2))
                        ->method('where')
                        ->with("default.entity_id = :customer_id");
                }
            }

            if ($isFiltered) {
                $this->selectMock->expects($this->once())
                    ->method('limit')
                    ->with(1);
            }
        }

        $this->subject->getConditionsSql($customer, 1, $isFiltered);
    }

    /**
     * @return array
     */
    public function getConditionsSqlDataProvider()
    {
        return [
            [false, true, null],
            [true, true, true],
            [true, false, true],
            [true, false, false]
        ];
    }
}
