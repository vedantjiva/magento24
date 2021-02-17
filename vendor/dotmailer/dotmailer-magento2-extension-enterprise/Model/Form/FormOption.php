<?php

namespace Dotdigitalgroup\Enterprise\Model\Form;

use Dotdigitalgroup\Enterprise\Api\Data\FormOptionInterface;

class FormOption extends \Magento\Framework\Api\AbstractExtensibleObject implements FormOptionInterface
{
    /**
     * @return string
     */
    public function getValue()
    {
        return $this->_get('value');
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        return $this->setData('value', $value);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->_get('label');
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setLabel($label)
    {
        return $this->setData('label', $label);
    }
}
