<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Console\Command;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Console\Command\BackupCodeCommand;
use Magento\Support\Helper\Shell;
use Magento\Support\Model\Backup\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCodeCommandTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Shell|MockObject
     */
    protected $shellHelper;

    /**
     * @var Config|MockObject
     */
    protected $backupConfig;

    /**
     * @var BackupCodeCommand
     */
    protected $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->shellHelper = $this->getMockBuilder(Shell::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backupConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $this->objectManagerHelper->getObject(
            BackupCodeCommand::class,
            [
                'shellHelper' => $this->shellHelper,
                'backupConfig' => $this->backupConfig,
                'outputPath' => 'var/output/path',
                'backupName' => 'backup_name'
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $backupCommand = 'nice -n 15 tar -czhf var/output/path/backup_name.tar.gz app bin composer.'
            . '* dev *.php lib pub/*.php pub/errors setup update vendor';
        $inputInterface = $this->getMockBuilder(InputInterface::class)
            ->getMockForAbstractClass();
        $outputInterface = $this->getMockBuilder(OutputInterface::class)
            ->getMockForAbstractClass();
        $this->shellHelper->expects($this->any())->method('setRootWorkingDirectory');
        $this->shellHelper->expects($this->any())->method('getUtility')->willReturnMap([
            ['nice', 'nice'],
            ['tar', 'tar']
        ]);
        $this->shellHelper->expects($this->atLeastOnce())->method('execute')->with($backupCommand)
            ->willReturn($backupCommand);
        $this->backupConfig->expects($this->any())->method('getBackupFileExtension')->with('code')
            ->willReturn('tar.gz');
        $outputInterface->expects($this->at(0))->method('writeln')->with($backupCommand);
        $outputInterface->expects($this->at(1))->method('writeln')->with($backupCommand);
        $outputInterface->expects($this->at(2))->method('writeln')->with('Code dump was created successfully');

        $this->model->run($inputInterface, $outputInterface);
    }
}
