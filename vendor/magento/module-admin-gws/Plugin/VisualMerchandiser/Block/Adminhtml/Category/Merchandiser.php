<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\VisualMerchandiser\Block\Adminhtml\Category;

use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser as CategoryProductsBlock;
use Magento\AdminGws\Model\Role as AdminRole;

/**
 * Category products block plugin.
 */
class Merchandiser
{
    /**
     * @var AdminRole $adminRole
     */
    private $adminRole;

    /**
     * @param AdminRole $adminRole
     */
    public function __construct(AdminRole $adminRole)
    {
        $this->adminRole = $adminRole;
    }

    /**
     * Check admin role permissions to change some view elements in the block.
     *
     * @param CategoryProductsBlock $subject
     * @return void
     */
    public function beforeToHtml(CategoryProductsBlock $subject): void
    {
        if (! $this->adminRole->getIsAll()) {
            $this->restrictCategoryProductsAdd($subject);
        }
    }

    /**
     * Disable add product button.
     *
     * @param CategoryProductsBlock $categoryProductsBlock
     * @return void
     */
    public function restrictCategoryProductsAdd(CategoryProductsBlock $categoryProductsBlock): void
    {
        /** @var WidgetButton|null $addProductsButton */
        $addProductsButton = $categoryProductsBlock->getChildBlock('add_products_button');
        if ($addProductsButton) {
            $addProductsButton->setDisabled(true);
        }
    }
}
