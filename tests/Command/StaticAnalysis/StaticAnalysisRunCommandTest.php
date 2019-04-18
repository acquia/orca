<?php

namespace Acquia\Orca\Tests\Command\StaticAnalysis;

use Acquia\Orca\Command\StaticAnalysis\StaticAnalysisRunCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask $composerValidate
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask $phpMessDetector
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask $phpCodeSniffer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask $phpLint
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask $yamlLint
 */
class StaticAnalysisRunCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  protected function setUp() {
    $this->composerValidate = $this->prophesize(ComposerValidateTask::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->phpCodeSniffer = $this->prophesize(PhpCodeSnifferTask::class);
    $this->phpLint = $this->prophesize(PhpLintTask::class);
    $this->phpMessDetector = $this->prophesize(PhpMessDetectorTask::class);
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
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->phpCodeSniffer->reveal())
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->phpLint->reveal())
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->phpMessDetector->reveal())
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->yamlLint->reveal())
      ->shouldBeCalledTimes($run_called)
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

  /**
   * @dataProvider providerTaskFiltering
   */
  public function testTaskFiltering($args, $task) {
    $args['path'] = self::SUT_PATH;
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn(TRUE);
    $this->taskRunner
      ->addTask($this->$task->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->run()
      ->shouldBeCalledTimes(1)
      ->willReturn(StatusCodes::OK);
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, StaticAnalysisRunCommand::getDefaultName(), $args);

    $this->assertEquals('', $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::OK, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerTaskFiltering() {
    return [
      [['--composer' => 1], 'composerValidate'],
      [['--phpcs' => 1], 'phpCodeSniffer'],
      [['--phplint' => 1], 'phpLint'],
      [['--phpmd' => 1], 'phpMessDetector'],
      [['--yamllint' => 1], 'yamlLint'],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask $composer_validate */
    $composer_validate = $this->composerValidate->reveal();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask $php_code_sniffer */
    $php_code_sniffer = $this->phpCodeSniffer->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask $php_lint */
    $php_lint = $this->phpLint->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask $php_mess_detector */
    $php_mess_detector = $this->phpMessDetector->reveal();
    /** @var \Acquia\Orca\Task\TaskRunner $task_runner */
    $task_runner = $this->taskRunner->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask $yaml_lint */
    $yaml_lint = $this->yamlLint->reveal();
    $application->add(new StaticAnalysisRunCommand($composer_validate, $filesystem, $php_code_sniffer, $php_lint, $php_mess_detector, $task_runner, $yaml_lint));
    /** @var \Acquia\Orca\Command\Tests\TestsRunCommand $command */
    $command = $application->find(StaticAnalysisRunCommand::getDefaultName());
    $this->assertInstanceOf(StaticAnalysisRunCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
