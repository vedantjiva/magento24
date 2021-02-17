<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Helper;

use Magento\CatalogStaging\Helper\ReindexPool;
use Magento\Framework\Indexer\AbstractProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexPoolTest extends TestCase
{
    /**
     * @var AbstractProcessor|MockObject
     */
    private $indexerProcessor;

    /**
     * @var ReindexPool
     */
    private $helper;

    protected function setUp(): void
    {
        $this->indexerProcessor = $this->getMockBuilder(AbstractProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reindexList'])
            ->getMockForAbstractClass();

        $reindexPool = [
            $this->indexerProcessor
        ];

        $this->helper = new ReindexPool($reindexPool);
    }

    public function testReindexList()
    {
        $ids = [1];

        $this->indexerProcessor->expects($this->once())
            ->method('reindexList')
            ->with($ids, true)
            ->willReturnSelf();

        $this->helper->reindexList($ids);
    }
}
