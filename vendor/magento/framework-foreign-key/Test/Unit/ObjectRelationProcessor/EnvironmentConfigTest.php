<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\ObjectRelationProcessor;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResourceConnection\ConfigInterface;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\ForeignKey\ObjectRelationProcessor\EnvironmentConfig;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnvironmentConfigTest extends TestCase
{
    /**
     * @var EnvironmentConfig
     */
    private $model;

    /**
     * @var MockObject
     */
    private $configMock;

    /**
     * @var MockObject
     */
    private $cacheMock;

    /**
     * @var MockObject
     */
    private $jsonDecoderMock;

    /**
     * @var MockObject
     */
    private $jsonEncoderMock;

    /**
     * @var string
     */
    private $cacheId;

    /**
     * @var array
     */
    private $connectionNames;

    protected function setUp(): void
    {
        $this->configMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->cacheMock = $this->getMockForAbstractClass(CacheInterface::class);
        $this->connectionNames = ['connectionName1', 'connectionName2'];
        $this->cacheId = 'connection_config_cache';

        $this->jsonDecoderMock = $this->getMockForAbstractClass(DecoderInterface::class);
        $this->jsonEncoderMock = $this->getMockForAbstractClass(EncoderInterface::class);

        $this->model = new EnvironmentConfig(
            $this->configMock,
            $this->cacheMock,
            $this->jsonDecoderMock,
            $this->jsonEncoderMock,
            $this->connectionNames
        );
    }

    public function testIsScalableEnvironmentIfConnectionNamesIsEmpty()
    {
        $this->model = new EnvironmentConfig(
            $this->configMock,
            $this->cacheMock,
            $this->jsonDecoderMock,
            $this->jsonEncoderMock,
            []
        );
        $this->assertFalse($this->model->isScalable());
    }

    public function testIsScalableEnvironment()
    {
        $this->cacheMock->expects($this->once())->method('load')->with($this->cacheId)->willReturn(false);
        $this->configMock->expects($this->at(0))
            ->method('getConnectionName')
            ->with($this->connectionNames[0])
            ->willReturn($this->connectionNames[0]);

        $this->configMock->expects($this->at(1))
            ->method('getConnectionName')
            ->with($this->connectionNames[1])
            ->willReturn(ResourceConnection::DEFAULT_CONNECTION);

        $connectionConfig = [
            $this->connectionNames[0] => false,
            $this->connectionNames[1] => true
        ];
        $this->jsonEncoderMock->expects($this->once())->method('encode')->willReturn(json_encode($connectionConfig));

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with(
                json_encode($connectionConfig),
                $this->cacheId,
                [Config::CACHE_TAG]
            )
            ->willReturn(true);

        $this->assertTrue($this->model->isScalable());
    }

    public function testIsScalableEnvironmentWhenConnectionConfigCached()
    {
        $connectionConfig = [
            $this->connectionNames[0] => true,
            $this->connectionNames[1] => true
        ];

        $this->jsonDecoderMock->expects($this->once())->method('decode')->willReturn($connectionConfig);

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($this->cacheId)
            ->willReturn(json_encode($connectionConfig));

        $this->assertFalse($this->model->isScalable());
    }
}
