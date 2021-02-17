<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRuleGraphQl\Model\Resolver\Batch;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\RelatedProductGraphQl\Model\Resolver\Batch\CrossSellProducts as ResolverCrossSellProducts;
use Magento\TargetRule\Model\Rule;

/**
 * Target Rule CrossSell Products Resolver
 */
class CrossSellProducts implements BatchResolverInterface
{
    /**
     * Query node
     */
    const NODE = 'crosssell_products';

    /**
     * @var TargetRuleProducts
     */
    private $targetRuleProducts;

    /**
     * @var ResolverCrossSellProducts
     */
    private $relatedResolver;

    /**
     * @param TargetRuleProducts $targetRuleProducts
     * @param ResolverCrossSellProducts $relatedResolver
     */
    public function __construct(
        TargetRuleProducts $targetRuleProducts,
        ResolverCrossSellProducts $relatedResolver
    ) {
        $this->targetRuleProducts = $targetRuleProducts;
        $this->relatedResolver = $relatedResolver;
    }

    /**
     * @inheritdoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $responses = $this->relatedResolver->resolve($context, $field, $requests);

        return $this->targetRuleProducts->applyTargetRuleResponses(
            $context,
            $requests,
            $responses,
            self::NODE,
            Rule::CROSS_SELLS
        );
    }
}
