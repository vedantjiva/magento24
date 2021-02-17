<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget\Tile\Attribute\Renderer;

class Stock extends \Magento\VisualMerchandiser\Block\Adminhtml\Widget\Tile\Attribute\Renderer
{
    /**
     * @return string
     */
    public function getValue()
    {
        return number_format(parent::getValue(), 2);
    }
}
