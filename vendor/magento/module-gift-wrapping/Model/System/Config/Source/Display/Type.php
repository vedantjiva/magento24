<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Price display type source model
 *
 * @author Magento Core Team <core@magentocommerce.com>
 * @codeCoverageIgnore
 */
namespace Magento\GiftWrapping\Model\System\Config\Source\Display;

class Type implements \Magento\Framework\Option\ArrayInterface
{
    /**#@+
     * Constants for display type
     */
    const DISPLAY_TYPE_EXCLUDING_TAX = 1;
    const DISPLAY_TYPE_INCLUDING_TAX = 2;
    const DISPLAY_TYPE_BOTH = 3;
    /**#@-*/

    /**#@-*/
    protected $_options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = [];
            $this->_options[] = [
                'value' => self::DISPLAY_TYPE_EXCLUDING_TAX,
                'label' => __('Excluding Tax'),
            ];
            $this->_options[] = [
                'value' => self::DISPLAY_TYPE_INCLUDING_TAX,
                'label' => __('Including Tax'),
            ];
            $this->_options[] = [
                'value' => self::DISPLAY_TYPE_BOTH,
                'label' => __('Including and Excluding Tax'),
            ];
        }
        return $this->_options;
    }
}
