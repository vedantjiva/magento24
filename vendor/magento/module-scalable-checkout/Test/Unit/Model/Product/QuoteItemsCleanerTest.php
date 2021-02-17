<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScalableCheckout\Test\Unit\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\ScalableCheckout\Model\Product\QuoteItemsCleaner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteItemsCleanerTest extends TestCase
{
    /**
     * @var QuoteItemsCleaner
     */
    private $model;

    /**
     * @var MockObject|PublisherInterface
     */
    private $publisherMock;

    protected function setUp(): void
    {
        $this->publisherMock = $this->getMockForAbstractClass(PublisherInterface::class);
        $this->model = new QuoteItemsCleaner($this->publisherMock);
    }

    public function testExecute()
    {
        $productMock = $this->getMockForAbstractClass(ProductInterface::class);
        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(QuoteItemsCleaner::TOPIC_NAME, $productMock);
        $this->model->execute($productMock);
    }
}
