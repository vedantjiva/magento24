<?php

namespace Dotdigitalgroup\Enterprise\Model\Config\Source\PageBuilder;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class Accounts implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $accountIds = [];

    /**
     * Accounts constructor.
     *
     * @param Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $data,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $data;
        $this->storeManager = $storeManager;
    }

    /**
     * Get list of surveys and forms.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        $fields = $accountData = $apiUsers = [];

        // Set up account list starting with the default website
        if ($this->helper->isEnabled(0)) {
            $apiUsers[] = $this->helper->getApiUsername(0);
            $accountOwnerEmail = $this->getAccountOwnerEmail(0);
            $accountData[0] = $accountOwnerEmail . ' (Default)';
        }

        foreach ($this->storeManager->getWebsites(false) as $website) {
            if (!$this->helper->isEnabled($website->getId()) ||
                in_array($this->helper->getApiUsername($website->getId()), $apiUsers)
            ) {
                continue;
            }

            $accountOwnerEmail = $this->getAccountOwnerEmail($website->getId());
            if ($accountOwnerEmail) {
                $accountData[$website->getId()] = $accountOwnerEmail . ' (' . $website->getName() . ')';
            }
        }

        if (count($accountData) === 0) {
            $fields[] = ['value' => null, 'label' => __('-- Disabled --')];
            return $fields;
        }

        ksort($accountData);

        foreach ($accountData as $websiteId => $accountString) {
            $fields[] = [
                'value' => $websiteId,
                'label' => $accountString,
            ];
        }

        return $fields;
    }

    /**
     * @param int $websiteId
     * @return string|bool
     * @throws \Exception
     */
    private function getAccountOwnerEmail($websiteId)
    {
        $accountInfo = $this->helper->getWebsiteApiClient($websiteId)
            ->getAccountInfo();

        if (in_array($accountInfo->id, $this->accountIds)) {
            return false;
        }

        $this->accountIds[] = $accountInfo->id;

        return $this->getEmailValueFromProperties($accountInfo->properties) ?: 'Account owner';
    }

    /**
     * @param $properties
     * @return string|bool
     */
    private function getEmailValueFromProperties($properties)
    {
        foreach ($properties as $property) {
            if ($property->name === 'MainEmail') {
                return $property->value;
            }
        }

        return false;
    }
}
