<?php

namespace Acquia\Orca\Tests\Command\StaticAnalysis;

use Acquia\Orca\Command\StaticAnalysis\RunCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\ComposerValidateTask;
use Acquia\Orca\Task\PhpCompatibilitySniffTask;
use Acquia\Orca\Task\PhpLintTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\ComposerValidateTask $composerValidate
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\PhpCompatibilitySniffTask $phpCompatibilitySniff
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\PhpLintTask $phpLint
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 */
class RunCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  protected function setUp() {
    $this->composerValidate = $this->prophesize(ComposerValidateTask::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->phpCompatibilitySniff = $this->prophesize(PhpCompatibilitySniffTask::class);
    $this->phpLint = $this->prophesize(PhpLintTask::class);
    $this->taskRunner = $this->prophesize(TaskRunner::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($path_exists, $run_called, $status_code, $display) {
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn($path_exists);
    $this->taskRunner
      ->addTask($this->composerValidate->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->phpCompatibilitySniff->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->phpLint->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->run()
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
    /** @var \Acquia\Orca\Task\ComposerValidateTask $composer_validate */
    $composer_validate = $this->composerValidate->reveal();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Task\PhpCompatibilitySniffTask $php_compatibility_sniff */
    $php_compatibility_sniff = $this->phpCompatibilitySniff->reveal();
    /** @var \Acquia\Orca\Task\PhpLintTask $php_lint */
    $php_lint = $this->phpLint->reveal();
    /** @var \Acquia\Orca\Task\TaskRunner $task_runner */
    $task_runner = $this->taskRunner->reveal();
    $application->add(new RunCommand($composer_validate, $filesystem, $php_compatibility_sniff, $php_lint, $task_runner));
    /** @var \Acquia\Orca\Command\Tests\RunCommand $command */
    $command = $application->find(RunCommand::getDefaultName());
    $this->assertInstanceOf(RunCommand::class, $command);
    return new CommandTester($command);
  }

}
