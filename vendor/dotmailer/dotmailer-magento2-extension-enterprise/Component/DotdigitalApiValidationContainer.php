<?php

namespace Dotdigitalgroup\Enterprise\Component;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Ui\Component\Container;

class DotdigitalApiValidationContainer extends Container
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Data
     */
    private $helper;

    public function __construct(
        ContextInterface $context,
        UrlInterface $url,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $components,
            $data
        );
        $this->helper = $helper;
        $this->url = $url;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        parent::prepare();

        $config = $this->getData('config');
        $isEnabled = $this->helper->isConnectorEnabledAtAnyLevel();

        if (!$isEnabled) {
            $config['visible'] = true;
        }

        if (isset($config['dotdigital_configuration_url'])) {
            $config['dotdigital_configuration_url'] = $this->url->getUrl(
                $config['dotdigital_configuration_url'],
                ['_fragment' => 'cms_pagebuilder']
            );
        }
        if (isset($config['content'])) {
            $config['content'] = sprintf($config['content'], $config['dotdigital_configuration_url']);
        }

        $this->setData('config', (array) $config);
    }
}
