<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product\Gallery;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\CreateHandler as ProductGalleryCreateHandler;
use Magento\Catalog\Model\Product\Gallery\UpdateHandler;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Json\Helper\Data;
use Magento\MediaStorage\Helper\File\Storage\Database;

/**
 * Create handler for staging catalog product gallery
 */
class CreateHandler extends ProductGalleryCreateHandler implements ExtensionInterface
{

    /**
     * @var UpdateHandler
     */
    private $updateHandler;

    /**
     * @param MetadataPool $metadataPool
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param Gallery $resourceModel
     * @param Data $jsonHelper
     * @param Config $mediaConfig
     * @param Filesystem $filesystem
     * @param Database $fileStorageDb
     * @param UpdateHandler $updateHandler
     */
    public function __construct(
        MetadataPool $metadataPool,
        ProductAttributeRepositoryInterface $attributeRepository,
        Gallery $resourceModel,
        Data $jsonHelper,
        Config $mediaConfig,
        Filesystem $filesystem,
        Database $fileStorageDb,
        UpdateHandler $updateHandler
    ) {
        $this->updateHandler = $updateHandler;

        parent::__construct(
            $metadataPool,
            $attributeRepository,
            $resourceModel,
            $jsonHelper,
            $mediaConfig,
            $filesystem,
            $fileStorageDb
        );
    }

    /**
     * Execute create handler
     *
     * @param Product $product
     * @param array $arguments
     * @return bool|object
     * @throws LocalizedException
     */
    public function execute($product, $arguments = [])
    {
        return $product->isObjectNew()
            ? parent::execute($product, $arguments)
            : $this->updateHandler->execute($product, $arguments);
    }
}
