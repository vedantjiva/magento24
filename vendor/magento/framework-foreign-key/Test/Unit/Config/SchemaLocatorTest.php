<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\Config;

use Magento\Framework\ForeignKey\Config\SchemaLocator;
use PHPUnit\Framework\TestCase;

class SchemaLocatorTest extends TestCase
{
    /**
     * @var SchemaLocator
     */
    protected $schemaLocator;

    protected function setUp(): void
    {
        $this->schemaLocator = new SchemaLocator();
    }

    public function testGetSchema()
    {
        $this->assertMatchesRegularExpression('/etc[\/\\\\]constraints.xsd/', $this->schemaLocator->getSchema());
    }

    public function testGetPerFileSchema()
    {
        $this->assertMatchesRegularExpression('/etc[\/\\\\]constraints.xsd/', $this->schemaLocator->getPerFileSchema());
    }
}
