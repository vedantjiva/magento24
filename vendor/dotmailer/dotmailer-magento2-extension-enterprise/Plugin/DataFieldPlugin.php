<?php

namespace Dotdigitalgroup\Enterprise\Plugin;

class DataFieldPlugin
{
    const DATA_MAPPING_PATH_PREFIX = 'extra_data';

    /**
     * @var \Dotdigitalgroup\Enterprise\Helper\Data
     */
    private $helper;

    /**
     * DataFieldPlugin constructor.
     * @param \Dotdigitalgroup\Enterprise\Helper\Data $helper
     */
    public function __construct(\Dotdigitalgroup\Enterprise\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield $subject
     * @param $result
     * @return null
     */
    public function beforeGetContactDatafields(
        \Dotdigitalgroup\Email\Model\Connector\Datafield $subject
    ) {
        $subject->setContactDatafields($this->helper->getEnterpriseDataFields(), self::DATA_MAPPING_PATH_PREFIX);
        return null;
    }
}
