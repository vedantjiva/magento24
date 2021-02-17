<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Model\Validator;

use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Validator\Discount;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

/**
 * Test Class DiscountTest
 */
class DiscountTest extends TestCase
{
    public function testIsValid()
    {
        $discount = new Discount();
        $item = $this->createMock(Item::class);
        $item->expects($this->at(0))
            ->method('getProductType')
            ->willReturn(Giftcard::TYPE_GIFTCARD);

        $item->expects($this->at(1))
            ->method('getProductType')
            ->willReturn($this->anything());

        $this->assertFalse($discount->isValid($item));
        $this->assertTrue($discount->isValid($item));

        $this->assertEmpty($discount->getMessages());
    }
}
