<?php
namespace Magento\GiftCard\Api\Data;

/**
 * Extension class for @see \Magento\GiftCard\Api\Data\GiftCardOptionInterface
 */
class GiftCardOptionExtension extends \Magento\Framework\Api\AbstractSimpleObject implements GiftCardOptionExtensionInterface
{
    /**
     * @return string[]|null
     */
    public function getGiftcardCreatedCodes()
    {
        return $this->_get('giftcard_created_codes');
    }

    /**
     * @param string[] $giftcardCreatedCodes
     * @return $this
     */
    public function setGiftcardCreatedCodes($giftcardCreatedCodes)
    {
        $this->setData('giftcard_created_codes', $giftcardCreatedCodes);
        return $this;
    }
}
