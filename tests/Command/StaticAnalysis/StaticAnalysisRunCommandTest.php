<?php

namespace Acquia\Orca\Tests\Command\StaticAnalysis;

use Acquia\Orca\Command\StaticAnalysis\StaticAnalysisRunCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\ComposerValidateTask;
use Acquia\Orca\Task\PhpCodeSnifferTask;
use Acquia\Orca\Task\PhpLintTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Task\YamlLintTask;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\ComposerValidateTask $composerValidate
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\PhpCodeSnifferTask $phpCodeSniffer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\PhpLintTask $phpLint
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\YamlLintTask $yamlLint
 */
class StaticAnalysisRunCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  protected function setUp() {
    $this->composerValidate = $this->prophesize(ComposerValidateTask::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->phpCodeSniffer = $this->prophesize(PhpCodeSnifferTask::class);
    $this->phpLint = $this->prophesize(PhpLintTask::class);
    $this->taskRunner = $this->prophesize(TaskRunner::class);
    $this->yamlLint = $this->prophesize(YamlLintTask::class);
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
      ->addTask($this->phpCodeSniffer->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->phpLint->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->yamlLint->reveal())
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

    $this->executeCommand($tester, StaticAnalysisRunCommand::getDefaultName(), ['path' => self::SUT_PATH]);

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
    /** @var \Acquia\Orca\Task\PhpCodeSnifferTask $php_code_sniffer */
    $php_code_sniffer = $this->phpCodeSniffer->reveal();
    /** @var \Acquia\Orca\Task\PhpLintTask $php_lint */
    $php_lint = $this->phpLint->reveal();
    /** @var \Acquia\Orca\Task\TaskRunner $task_runner */
    $task_runner = $this->taskRunner->reveal();
    /** @var \Acquia\Orca\Task\YamlLintTask $yaml_lint */
    $yaml_lint = $this->yamlLint->reveal();
    $application->add(new StaticAnalysisRunCommand($composer_validate, $filesystem, $php_code_sniffer, $php_lint, $task_runner, $yaml_lint));
    /** @var \Acquia\Orca\Command\Tests\TestsRunCommand $command */
    $command = $application->find(StaticAnalysisRunCommand::getDefaultName());
    $this->assertInstanceOf(StaticAnalysisRunCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
