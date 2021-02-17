<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey\Test\Unit\Config;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ForeignKey\Config\DbReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class to test Foreign key data reader functionality
 */
class DbReaderTest extends TestCase
{
    /**
     * @var DbReader
     */
    private $model;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourcesMock;

    /**
     * @var DeploymentConfig|MockObject
     */
    protected $deploymentConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resourcesMock = $this->createMock(ResourceConnection::class);
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->model = new DbReader($this->resourcesMock, $this->deploymentConfig);
    }

    /**
     * Test to Load constraint configuration from all related databases
     */
    public function testRead()
    {
        $dbConfig = [
            'default' => [
                'host' => '127.0.0.1',
                'dbname' => 'magento',
                'username' => 'root',
                'password' => 'root',
                'model' => 'mysql4',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ]
        ];
        $this->deploymentConfig->expects($this->once())
            ->method('get')
            ->with('db/connection')
            ->willReturn($dbConfig);
        $connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourcesMock->expects($this->once())
            ->method('getConnectionByName')
            ->with('default')
            ->willReturn($connection);

        $testForeignKey = [
            'FK_NAME' => 'some_fk',
            'TABLE_NAME' => 'some_table',
            'REF_TABLE_NAME' => 'some_table_two',
            'COLUMN_NAME' => 'field_one',
            'REF_COLUMN_NAME' => 'field_two',
            'ON_DELETE' => 'CASCADE',
        ];
        $connection->method('getTables')
            ->willReturn([$testForeignKey['TABLE_NAME']]);
        $connection->method('getForeignKeys')
            ->with($testForeignKey['TABLE_NAME'])
            ->willReturn([$testForeignKey]);

        $expected = [
            [
                'name' => 'some_fk',
                'delete_strategy' => 'DB CASCADE',
                'table_name' => 'some_table',
                'reference_table_name' => 'some_table_two',
                'field_name' => 'field_one',
                'reference_field_name' => 'field_two',
                'connection' => 'default',
                'reference_connection' => 'default'
            ]
        ];
        $actual = $this->model->read();
        $this->assertEquals($expected, array_values($actual));
    }
}
