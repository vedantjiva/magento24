<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\Config;

use Magento\Framework\App\ResourceConnection\ConfigInterface;
use Magento\Framework\ForeignKey\Config\Converter;
use Magento\Framework\Stdlib\BooleanUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    /**
     * @var Converter
     */
    private $model;

    /**
     * @var  MockObject
     */
    private $resourceConfigMock;

    /**
     * @var string
     */
    private $fixturePath;

    protected function setUp(): void
    {
        $this->resourceConfigMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->model = new Converter($this->resourceConfigMock, new BooleanUtils());
        $this->fixturePath = realpath(__DIR__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR;
    }

    public function testConvert()
    {
        $this->resourceConfigMock->expects($this->any())
            ->method('getConnectionName')
            ->willReturnCallback(function ($resourceName) {
                return $resourceName;
            });
        $dom = new \DOMDocument();
        $dom->load($this->fixturePath . 'constraints.xml');
        $constraints = $this->model->convert($dom);
        $expectedResult = require $this->fixturePath . 'constraints.php';
        $this->assertEquals($expectedResult, $constraints);
    }
}
