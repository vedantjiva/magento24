<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\Config;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\ValidationStateInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ForeignKey\Config\Converter;
use Magento\Framework\ForeignKey\Config\DbReader;
use Magento\Framework\ForeignKey\Config\Processor;
use Magento\Framework\ForeignKey\Config\Reader;
use Magento\Framework\ForeignKey\Config\SchemaLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReaderTest extends TestCase
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var MockObject|ResourceConnection
     */
    protected $resourcesMock;

    /**
     * @var MockObject|DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var MockObject|Processor
     */
    protected $processorMock;

    /**
     * @var MockObject
     */
    protected $dbReaderMock;

    /**
     * @var MockObject|AdapterInterface
     */
    protected $connectionMock;

    /**
     * @var MockObject|FileResolverInterface
     */
    protected $fileResolverMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->resourcesMock = $this->createMock(ResourceConnection::class);
        $this->processorMock = $this->createMock(Processor::class);
        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->dbReaderMock = $this->createMock(DbReader::class);
        $this->fileResolverMock = $this->getMockForAbstractClass(FileResolverInterface::class);
        $this->reader = new Reader(
            $this->fileResolverMock,
            $this->createMock(Converter::class),
            $this->createMock(SchemaLocator::class),
            $this->getMockForAbstractClass(ValidationStateInterface::class),
            $this->resourcesMock,
            $this->deploymentConfig,
            $this->processorMock,
            $this->dbReaderMock
        );
    }

    /**
     * Test to Load configuration scope
     */
    public function testRead()
    {
        $connectionConfig['default'] = [
            'host' => 'localhost',
            'dbname' => 'example',
            'username' => 'root',
            'password' => '',
            'model' => 'mysql4',
            'initStatements' => 'SET NAMES utf8;',
            'active' => 1,
        ];
        $tables = ['prefix_prefix_table'];
        $databaseTables['prefix_table'] = [
            'name' => 'prefix_table',
            'prefixed_name' => 'prefix_prefix_table',
            'connection' => 'default',
        ];
        $this->deploymentConfig
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX, null, 'prefix_'],
                [ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS, null, $connectionConfig]
            ]);
        $this->resourcesMock
            ->expects($this->once())
            ->method('getConnectionByName')
            ->with('default')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())->method('getTables')->willReturn($tables);
        $databaseConstraints = [];
        $this->dbReaderMock->expects($this->once())->method('read')->willReturn($databaseConstraints);
        $this->processorMock->expects($this->once())
            ->method('process')
            ->with([], $databaseConstraints, $databaseTables);
        $this->fileResolverMock->expects($this->once())
            ->method('get')
            ->with('constraints.xml', 'global')
            ->willReturn([]);
        $this->reader->read();
    }
}
