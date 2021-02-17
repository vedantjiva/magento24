<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Model;

use Magento\Banner\Model\Banner;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class BannerTest extends TestCase
{
    /**
     * @var Banner
     */
    protected $banner;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->banner = $objectManager->getObject(Banner::class);
    }

    protected function tearDown(): void
    {
        $this->banner = null;
    }

    public function testGetIdentities()
    {
        $id = 1;
        $this->banner->setId($id);
        $this->assertEquals(
            [Banner::CACHE_TAG . '_' . $id],
            $this->banner->getIdentities()
        );
    }

    public function testBeforeSave()
    {
        $this->banner->setName('Test');
        $this->banner->setId(1);
        $this->banner->setStoreContents([
            0 => '<p>{{widget type="Magento\Banner\Block\Widget\Banner" banner_ids="2"}}</p>'
        ]);
        $this->assertEquals($this->banner, $this->banner->beforeSave());
    }

    public function testBeforeSaveWithSameId()
    {
        $this->banner->setName('Test');
        $this->banner->setId(1);
        $this->banner->setStoreContents([
            0 => '<p>{{widget type="Magento\Banner\Block\Widget\Banner" banner_ids="1,2"}}</p>'
        ]);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            (string)__('Make sure that dynamic blocks rotator does not reference the dynamic block itself.')
        );
        $this->banner->beforeSave();
    }
}
