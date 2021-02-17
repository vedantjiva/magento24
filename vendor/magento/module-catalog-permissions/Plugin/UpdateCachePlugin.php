<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Plugin;

use Magento\Customer\Model\Session;
use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;

/**
 * Class UpdateCachePlugin to update Context with data
 */
class UpdateCachePlugin
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ConfigInterface
     */
    private $permissionsConfig;

    /**
     * @param Registry $coreRegistry
     * @param Session $customerSession
     * @param ConfigInterface $permissionsConfig
     */
    public function __construct(
        Registry $coreRegistry,
        Session $customerSession,
        ConfigInterface $permissionsConfig
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->customerSession = $customerSession;
        $this->permissionsConfig = $permissionsConfig;
    }

    /**
     * Update the context with current category and customer group id
     *
     * @param HttpContext $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetData(HttpContext $subject, array $result)
    {
        if (!$this->permissionsConfig->isEnabled()) {
            return $result;
        }

        $category = $this->coreRegistry->registry('current_category');
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        if ($customerGroupId) {
            $result['customer_group'] = $customerGroupId;
        }

        if ($category && $category->getId()) {
            $result['category'] = $category->getId();
        }

        return $result;
    }
}
