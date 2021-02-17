<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Quote\Address;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AttributeMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerCustomAttributes\Model\Quote\Address\CustomAttributeList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomAttributeListTest extends TestCase
{
    /** @var AddressMetadataInterface|MockObject */
    protected $addressMetadata;

    /** @var CustomAttributeList */
    protected $model;

    protected function setUp(): void
    {
        $this->addressMetadata = $this->getMockForAbstractClass(
            AddressMetadataInterface::class,
            [],
            '',
            false
        );

        $this->model = new CustomAttributeList($this->addressMetadata);
    }

    public function testGetAttributes()
    {
        $customAttributesMetadata = $this->getMockForAbstractClass(
            AttributeMetadataInterface::class,
            [],
            '',
            false
        );
        $customAttributesMetadata->expects($this->at(0))
            ->method('getAttributeCode')
            ->willReturn('attributeCode');
        $this->addressMetadata->expects($this->at(0))
            ->method('getCustomAttributesMetadata')
            ->with(AddressInterface::class)
            ->willReturn([$customAttributesMetadata]);

        $customAttributesMetadata->expects($this->at(1))
            ->method('getAttributeCode')
            ->willReturn('customAttributeCode');
        $this->addressMetadata->expects($this->at(1))
            ->method('getCustomAttributesMetadata')
            ->with(CustomerInterface::class)
            ->willReturn([$customAttributesMetadata]);

        $this->assertEquals(
            [
                'attributeCode' => $customAttributesMetadata,
                'customAttributeCode' => $customAttributesMetadata,
            ],
            $this->model->getAttributes()
        );
    }
}
