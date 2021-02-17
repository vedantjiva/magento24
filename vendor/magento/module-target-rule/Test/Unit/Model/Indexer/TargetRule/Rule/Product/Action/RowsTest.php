<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Rule\Product\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows;
use PHPUnit\Framework\TestCase;

class RowsTest extends TestCase
{
    /**
     * @var Rows
     */
    protected $_model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->_model = $objectManager->getObject(
            Rows::class
        );
    }

    public function testEmptyIds()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Could not rebuild index for empty products array');
        $this->_model->execute(null);
    }
}
