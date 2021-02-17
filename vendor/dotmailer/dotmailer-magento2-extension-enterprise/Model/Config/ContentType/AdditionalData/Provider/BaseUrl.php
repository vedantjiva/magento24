<?php

namespace Dotdigitalgroup\Enterprise\Model\Config\ContentType\AdditionalData\Provider;

use Magento\PageBuilder\Model\Config\ContentType\AdditionalData\ProviderInterface;
use Magento\Framework\UrlInterface;

/**
 * Provides open dialog URL for media gallery slideout
 */
class BaseUrl implements ProviderInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritdoc
     */
    public function getData(string $itemName) : array
    {
        return [
            $itemName => $this->urlBuilder->getBaseUrl()
        ];
    }
}
