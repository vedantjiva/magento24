<?php


namespace Dotdigitalgroup\Enterprise\Block;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\View\Element\Template;

class DotdigitalApi extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    private $emailHelper;

    /**
     * DotdigitalApi constructor.
     * @param Template\Context $context
     * @param Data $emailHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $emailHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->emailHelper = $emailHelper;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isEnabled(): bool
    {
        return $this->emailHelper->isConnectorEnabledAtAnyLevel();
    }

    /**
     * Return the translated message for an invalid API key.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getActivationMessage(): \Magento\Framework\Phrase
    {
        return __(
            "An active dotdigital Engagement Cloud account is required to use this feature.
             Please enable your account <a href='%1' target='_blank'>here</a>.",
            $this->_urlBuilder->getUrl(
                'adminhtml/system_config/edit/section/connector_api_credentials',
                ['_fragment' => 'cms_pagebuilder']
            )
        );
    }
}
