<?php

namespace Dotdigitalgroup\Enterprise\Model\Form;

use Dotdigitalgroup\Enterprise\Api\FormManagementInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class FormManagement implements FormManagementInterface
{
    const EMBEDDED_URL = '/resources/sharing/embed.js';
    const POPOVER_URL = '/resources/sharing/popover.js';
    const FORM_SHARING_EMBED = 'lp-embed';
    const FORM_SHARING_POPOVER = 'lp-popover';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Enterprise\Api\Data\FormOptionInterfaceFactory
     */
    private $formOptionFactory;

    /**
     * @var \Dotdigitalgroup\Enterprise\Api\Data\FormDataInterfaceFactory
     */
    private $formDataFactory;

    /**
     * SurveysForms constructor.
     *
     * @param Data $data
     * @param StoreManagerInterface $storeManager
     * @param \Dotdigitalgroup\Enterprise\Api\Data\FormOptionInterfaceFactory $formOptionFactory
     * @param \Dotdigitalgroup\Enterprise\Api\Data\FormDataInterfaceFactory $formDataFactory
     */
    public function __construct(
        Data $data,
        StoreManagerInterface $storeManager,
        \Dotdigitalgroup\Enterprise\Api\Data\FormOptionInterfaceFactory $formOptionFactory,
        \Dotdigitalgroup\Enterprise\Api\Data\FormDataInterfaceFactory $formDataFactory
    ) {
        $this->helper = $data;
        $this->storeManager = $storeManager;
        $this->formOptionFactory = $formOptionFactory;
        $this->formDataFactory = $formDataFactory;
    }

    /**
     * @param int $websiteId
     * @return array|\Dotdigitalgroup\Enterprise\Api\Data\FormOptionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormOptions($websiteId)
    {
        $forms = [];

        if (!$this->helper->isEnabled($websiteId)) {
            return [];
        }

        $client = $this->helper->getWebsiteApiClient($websiteId);

        if ($ECForms = $client->getSurveysAndForms()) {
            foreach ($ECForms as $ECForm) {
                if (isset($ECForm->id) && $ECForm->state == 'Active' && $this->isNewFormType($ECForm->url)) {
                    $forms[] = $this->formOptionFactory->create()
                        ->setValue($ECForm->id)
                        ->setLabel($ECForm->name);
                }
            }
        }

        return $forms;
    }

    /**
     * @param $formId
     * @param $websiteId
     * @param $formStyle
     * @return \Dotdigitalgroup\Enterprise\Api\Data\FormDataInterface|null
     */
    public function getFormData($formId, $websiteId, $formStyle)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $ECForm = $client->getFormById($formId);

        if (!$ECForm) {
            return null;
        }

        $pageId = $this->extractPageId($ECForm->url);
        $domain = $this->extractDomain($ECForm->url);
        $scriptSrc = $formStyle === 'embedded' ?
            '//'. $domain . self::EMBEDDED_URL :
            '//'. $domain . self::POPOVER_URL;
        $sharing = $formStyle === 'embedded' ? self::FORM_SHARING_EMBED : self::FORM_SHARING_POPOVER;

        return $this->formDataFactory->create()
            ->setFormName($ECForm->name)
            ->setFormPageId($pageId)
            ->setFormDomain($domain)
            ->setScriptSrc($scriptSrc)
            ->setFormSharing($sharing);
    }

    /**
     * Extract the page id from the form URL e.g. 001ln562-3411pu49
     *
     * @param $url
     * @return string
     */
    private function extractPageId($url)
    {
        $bits = explode('/', $url);
        return array_reverse($bits)[1].'/'.array_reverse($bits)[0];
    }

    /**
     * Extract the domain from the survey URL e.g. rl.dotdigital-pages.com
     *
     * @param $url
     * @return string
     */
    private function extractDomain($url)
    {
        $bits = explode('/', $url);
        return $bits[2];
    }

    /**
     * @param $form
     * @return false|int
     */
    private function isNewFormType($form)
    {
        return strpos($form, '/p/');
    }
}
