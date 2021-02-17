<?php

namespace Dotdigitalgroup\Enterprise\Model\Form;

use Dotdigitalgroup\Enterprise\Api\Data\FormDataInterface;

class FormData extends \Magento\Framework\Api\AbstractExtensibleObject implements FormDataInterface
{
    /**
     * @return string
     */
    public function getFormName()
    {
        return $this->_get('form_name');
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setFormName($name)
    {
        return $this->setData('form_name', $name);
    }

    /**
     * @return string
     */
    public function getFormPageId()
    {
        return $this->_get('form_page_id');
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setFormPageId($pageId)
    {
        return $this->setData('form_page_id', $pageId);
    }

    /**
     * @return string
     */
    public function getFormDomain()
    {
        return $this->_get('form_domain');
    }

    /**
     * @param string $domain
     * @return $this
     */
    public function setFormDomain($domain)
    {
        return $this->setData('form_domain', $domain);
    }

    /**
     * @return string
     */
    public function getScriptSrc()
    {
        return $this->_get('script_src');
    }

    /**
     * @param string $src
     * @return $this
     */
    public function setScriptSrc($src)
    {
        return $this->setData('script_src', $src);
    }

    /**
     * @return string
     */
    public function getFormSharing()
    {
        return $this->_get('form_sharing');
    }

    /**
     * @param string $sharing
     * @return $this
     */
    public function setFormSharing($sharing)
    {
        return $this->setData('form_sharing', $sharing);
    }
}
