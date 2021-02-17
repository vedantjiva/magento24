<?php

namespace Dotdigitalgroup\Enterprise\Plugin;

use Dotdigitalgroup\Email\Model\Apiconnector\CustomerDataFieldProvider;
use Dotdigitalgroup\Enterprise\Helper\Data;
use Magento\Store\Api\Data\WebsiteInterface;

class CustomerDataFieldProviderPlugin
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Add enterprise data
     *
     * @param CustomerDataFieldProvider $customerDataFieldProvider
     * @param WebsiteInterface $website
     * @param array $result
     * @return array
     */
    public function afterGetAdditionalDataFields(
        CustomerDataFieldProvider $customerDataFieldProvider,
        array $result
    ) {
        $enterpriseAttributes = $this->helper->getEnterpriseAttributes($customerDataFieldProvider->getWebsite()) ?: [];
        return $result += $enterpriseAttributes;
    }
}
