<?php

namespace Dotdigitalgroup\Enterprise\Api;

interface FormManagementInterface
{
    /**
     * Fetch Pages, Surveys or Forms for a website.
     *
     * @param int $websiteId
     * @return \Dotdigitalgroup\Enterprise\Api\Data\FormOptionInterface[]
     */
    public function getFormOptions($websiteId);

    /**
     * @param int $formId
     * @param int $websiteId
     * @param string $formStyle
     * @return \Dotdigitalgroup\Enterprise\Api\Data\FormDataInterface
     */
    public function getFormData($formId, $websiteId, $formStyle);
}
