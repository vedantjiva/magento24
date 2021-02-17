<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessor;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\GraphQl\Model\Query\ContextFactoryInterface;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogPermissions\Helper\Data as CatalogPermissionsData;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\IndexFactory;

class CustomerGroupProcessor implements CollectionProcessorInterface
{
    /**
     * @var ContextFactoryInterface
     */
    private $contextFactory;
    /**
     * @var ConfigInterface
     */

    private $permissionsConfig;
    /**
     * @var CatalogPermissionsData
     */

    private $catalogPermissionsData;
    /**
     * @var IndexFactory
     */

    private $permissionIndexFactory;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param ContextFactoryInterface $contextFactory
     * @param ConfigInterface $permissionsConfig
     * @param CatalogPermissionsData $catalogPermissionsData
     * @param IndexFactory $permissionIndexFactory
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ContextFactoryInterface $contextFactory,
        ConfigInterface $permissionsConfig,
        CatalogPermissionsData $catalogPermissionsData,
        IndexFactory $permissionIndexFactory,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->contextFactory = $contextFactory;
        $this->permissionsConfig = $permissionsConfig;
        $this->catalogPermissionsData = $catalogPermissionsData;
        $this->permissionIndexFactory = $permissionIndexFactory;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Process collection to add additional joins, attributes, and clauses to a product collection.
     *
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param ContextInterface|null $context
     * @return Collection
     */
    public function process(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        ContextInterface $context = null
    ): Collection {
        if (!$this->permissionsConfig->isEnabled()) {
            return $collection;
        }

        /**
         * The $customerGroupId which is used to add permission index to product model.
         * Falls back to zero which represents the "Not Logged In" customer group.
         *
         * @var int $customerGroupId
         */
        try {
            if ($context && $context->getExtensionAttributes()->getIsCustomer() === true) {
                $customerGroupId = (int)$this->customerRepository->getById($context->getUserId())->getGroupId();
            } else {
                $customerGroupId = GroupInterface::NOT_LOGGED_IN_ID;
            }
        } catch (\Exception $e) {
            $customerGroupId = GroupInterface::NOT_LOGGED_IN_ID;
        }

        foreach ($collection as $key => $product) {
            if ($collection->hasFlag('product_children')) {
                $product->addData(
                    [
                        'grant_catalog_category_view' => -1,
                        'grant_catalog_product_price' => -1,
                        'grant_checkout_items' => -1
                    ]
                );
            } else {
                $permissionIndex = $this->permissionIndexFactory->create();
                $permissionIndex->addIndexToProduct($product, $customerGroupId ?? 0);
            }

            $this->applyCategoryRelatedPermissionsOnProduct($product);

            /** Filter out hidden items */
            if ($product->getIsHidden()) {
                $collection->removeItemByKey($key);
            }
        }

        return $collection;
    }

    /**
     * Apply category related permissions on product
     *
     * @param ProductInterface $product
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function applyCategoryRelatedPermissionsOnProduct(ProductInterface $product): void
    {
        if ($product->getData('grant_catalog_category_view') == -2
            || $product->getData('grant_catalog_category_view') != -1
            && !$this->catalogPermissionsData->isAllowedCategoryView()
        ) {
            $product->setIsHidden(true);
        }

        if ($product->getData('grant_catalog_product_price') == -2
            || $product->getData('grant_catalog_product_price') != -1
            && !$this->catalogPermissionsData->isAllowedProductPrice()
        ) {
            $product->setCanShowPrice(false);
            $product->setDisableAddToCart(true);
        }

        if ($product->getData('grant_checkout_items') == -2
            || $product->getData('grant_checkout_items') != -1
            && !$this->catalogPermissionsData->isAllowedCheckoutItems()
        ) {
            $product->setDisableAddToCart(true);
        }
    }
}
