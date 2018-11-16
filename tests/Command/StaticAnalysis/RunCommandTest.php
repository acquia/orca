<?php

namespace Acquia\Orca\Tests\Command\StaticAnalysis;

use Acquia\Orca\Command\StaticAnalysis\RunCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\StaticAnalysisRunner;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\StaticAnalysisRunner $staticAnalysisRunner
 */
class RunCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->staticAnalysisRunner = $this->prophesize(StaticAnalysisRunner::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($path_exists, $run_called, $status_code, $display) {
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn($path_exists);
    $this->staticAnalysisRunner
      ->run(self::SUT_PATH)
      ->shouldBeCalledTimes($run_called)
      ->willReturn($status_code);
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, RunCommand::getDefaultName(), ['path' => self::SUT_PATH]);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, 1, StatusCodes::OK, ''],
      [TRUE, 1, StatusCodes::ERROR, ''],
      [FALSE, 0, StatusCodes::ERROR, sprintf("Error: No such path: %s.\n", self::SUT_PATH)],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\StaticAnalysisRunner $static_analysis_runner */
    $static_analysis_runner = $this->staticAnalysisRunner->reveal();
    $application->add(new RunCommand($filesystem, $static_analysis_runner));
    /** @var \Acquia\Orca\Command\Tests\RunCommand $command */
    $command = $application->find(RunCommand::getDefaultName());
    $this->assertInstanceOf(RunCommand::class, $command);
    return new CommandTester($command);
  }

}
