<?php

namespace Dotdigitalgroup\Enterprise\Api\Data;

interface FormOptionInterface
{
    /**
     * @return string
     */
    public function getValue();

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value);

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param string $name
     * @return $this
     */
    public function setLabel($name);
}
