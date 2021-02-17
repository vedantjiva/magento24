<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\GiftWrapping\Model\Wrapping;

/**
 * Testing upload controller.
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    public function setUp() : void
    {
        $this->resource = 'Magento_GiftWrapping::magento_giftwrapping';
        $this->uri = 'backend/admin/giftwrapping/save';
        parent::setUp();
    }

    /**
     * Test save controller
     *
     * @dataProvider saveProvider
     * @param $image
     * @param $postData
     * @param $expects
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function testSave($image, $postData, $expects) : void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Framework\Filesystem $filesystem */
        $this->filesystem = $this->objectManager->get(\Magento\Framework\Filesystem::class);
        $_FILES['image_name'] = $image;
        $this->getRequest()->setPostValue('wrapping', $postData);
        $dispatchUrl = 'backend/admin/giftwrapping/save/store/'
            . \Magento\Store\Model\Store::DEFAULT_STORE_ID . '/';
        $tmpDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::SYS_TMP);
        $fixtureDir = realpath(__DIR__ . '/../../../_files');
        $fileName = 'magento_small_image.jpg';
        $filePath = $tmpDirectory->getAbsolutePath($fileName);
        copy($fixtureDir . DIRECTORY_SEPARATOR . $fileName, $filePath);
        $_FILES['image_name'] = $image;
        $_FILES['image_name']['tmp_name'] = $filePath;
        $imageNamePattern = '/fooImage[_0-9]*\./';

        $this->dispatch($dispatchUrl);
        $coreRegistry = $this->objectManager->get(\Magento\Framework\Registry::class);
        $model = $coreRegistry->registry('current_giftwrapping_model');

        $this->assertEquals($expects['design'], $model->getDesign());
        $this->assertEquals($expects['website_ids'], $model->getWebsiteIds());
        $this->assertEquals($expects['status'], $model->getStatus());
        $this->assertEquals($expects['base_price'], $model->getBasePrice());
        $this->assertRegExp($imageNamePattern, $model->getImage());
        $this->assertNull($model->getTmpImage());
    }

    /**
     * Save test data provider
     *
     * @return array
     */
    public function saveProvider() : array
    {
        return [
            [
                [
                    'name' => 'fooImage.jpg',
                    'type' => 'image/jpeg',
                    'error' => 0,
                    'size' => 12500
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' => 'fooImage.jpg'
                    ]
                ],
                [
                    'id' => 1,
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15
                ]
            ],
            [
                [
                    'name' => 'fooImage.jpg',
                    'type' => 'image/jpeg',
                    'error' => 0,
                    'size' => 12500,
                ],
                [
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15,
                    'image_name' => [
                        'value' => 'fooImage.jpg'
                    ],
                    'tmp_image' => 'barImage.jpg'
                ],
                [
                    'id' => 2,
                    'design' => 'Foobar',
                    'website_ids' => [1],
                    'status' => 1,
                    'base_price' => 15
                ]
            ]
        ];
    }
}
