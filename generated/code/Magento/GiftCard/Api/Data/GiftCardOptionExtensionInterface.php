<?php
namespace Magento\GiftCard\Api\Data;

/**
 * ExtensionInterface class for @see \Magento\GiftCard\Api\Data\GiftCardOptionInterface
 */
interface GiftCardOptionExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @return string[]|null
     */
    public function getGiftcardCreatedCodes();

    /**
     * @param string[] $giftcardCreatedCodes
     * @return $this
     */
    public function setGiftcardCreatedCodes($giftcardCreatedCodes);
}
