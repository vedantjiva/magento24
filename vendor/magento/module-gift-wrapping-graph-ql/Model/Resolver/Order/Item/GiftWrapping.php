<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrappingGraphQl\Model\Resolver\Order\Item;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftWrapping\Api\WrappingRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class gets data about gift wrapping for order items
 */
class GiftWrapping implements ResolverInterface
{
    /**
     * @var WrappingRepositoryInterface
     */
    private $wrappingRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @param WrappingRepositoryInterface  $wrappingRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        WrappingRepositoryInterface $wrappingRepository,
        OrderItemRepositoryInterface $orderItemRepository
    ) {
        $this->wrappingRepository = $wrappingRepository;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * Get gift wrapping data for order items
     *
     * @param Field            $field
     * @param ContextInterface $context
     * @param ResolveInfo      $info
     * @param array|null       $value
     * @param array|null       $args
     *
     * @return array|Value|mixed|null
     *
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['id'])) {
            throw new GraphQlInputException(__('"id" value should be specified'));
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $orderItemId = (int)base64_decode($value['id']) ?: (int)$value['id'];

        try {
            /** @var $orderItem  OrderItemInterface*/
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__('Can\'t load gift wrapping for order'));
        }

        $giftWrappingId = $orderItem->getGwId();

        if (empty($giftWrappingId)) {
            return null;
        }
        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();

        try {
            $cartGiftWrapping = $this->wrappingRepository->get((int)$giftWrappingId, (int)$store->getStoreId());
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__('Can\'t load gift wrapping for order item.'));
        }

        return [
            'id' => $cartGiftWrapping->getWrappingId() ?? '',
            'design' => $cartGiftWrapping->getDesign() ?? '',
            'price' => [
                'value' => $cartGiftWrapping->getBasePrice() ?? '',
                'currency' => $store->getCurrentCurrencyCode()
            ],
            'image' => [
                'label'=> $cartGiftWrapping->getImageName() ?? '',
                'url'=> $cartGiftWrapping->getImageUrl() ?? ''
            ]
        ];
    }
}
