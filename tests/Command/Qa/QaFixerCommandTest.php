<?php

namespace Acquia\Orca\Tests\Command\Qa;

use Acquia\Orca\Command\Qa\QaFixerCommand;
use Acquia\Orca\Command\Qa\QaStaticAnalysisCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\Fixer\ComposerNormalizeTask;
use Acquia\Orca\Task\Fixer\PhpCodeBeautifierAndFixerTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\Fixer\ComposerNormalizeTask composerNormalize
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\Fixer\PhpCodeBeautifierAndFixerTask phpCodeBeautifierAndFixer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 */
class QaFixerCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  protected function setUp() {
    $this->composerNormalize = $this->prophesize(ComposerNormalizeTask::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->phpCodeBeautifierAndFixer = $this->prophesize(PhpCodeBeautifierAndFixerTask::class);
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
      ->addTask($this->composerNormalize->reveal())
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->phpCodeBeautifierAndFixer->reveal())
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

    $this->executeCommand($tester, QaFixerCommand::getDefaultName(), ['path' => self::SUT_PATH]);

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

    $this->executeCommand($tester, QaStaticAnalysisCommand::getDefaultName(), $args);

    $this->assertEquals('', $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::OK, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerTaskFiltering() {
    return [
      [['--composer' => 1], 'composerNormalize'],
      [['--phpcbf' => 1], 'phpCodeBeautifierAndFixer'],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Task\Fixer\ComposerNormalizeTask $composer_normalize */
    $composer_normalize = $this->composerNormalize->reveal();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Task\Fixer\PhpCodeBeautifierAndFixerTask $php_code_beautifier_and_fixer */
    $php_code_beautifier_and_fixer = $this->phpCodeBeautifierAndFixer->reveal();
    /** @var \Acquia\Orca\Task\TaskRunner $task_runner */
    $task_runner = $this->taskRunner->reveal();
    $application->add(new QaFixerCommand($composer_normalize, $filesystem, $php_code_beautifier_and_fixer, $task_runner));
    /** @var \Acquia\Orca\Command\Qa\QaAutomatedTestsCommand $command */
    $command = $application->find(QaFixerCommand::getDefaultName());
    $this->assertInstanceOf(QaFixerCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
