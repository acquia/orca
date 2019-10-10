<?php

namespace Acquia\Orca\Tests\Command\Qa;

use Acquia\Orca\Command\Qa\QaStaticAnalysisCommand;
use Acquia\Orca\Enum\PhpcsStandard;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask;
use Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask $composerValidate
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask $phpMessDetector
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask $phpCodeSniffer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask $phpLint
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask $phpLoc
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask $yamlLint
 */
class QaStaticAnalysisCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  private $defaultPhpcsStandard = PhpcsStandard::DEFAULT;

  protected function setUp() {
    $this->composerValidate = $this->prophesize(ComposerValidateTask::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->phpCodeSniffer = $this->prophesize(PhpCodeSnifferTask::class);
    $this->phpLint = $this->prophesize(PhpLintTask::class);
    $this->phpLoc = $this->prophesize(PhpLocTask::class);
    $this->phpMessDetector = $this->prophesize(PhpMessDetectorTask::class);
    $this->taskRunner = $this->prophesize(TaskRunner::class);
    $this->yamlLint = $this->prophesize(YamlLintTask::class);
  }

  protected function createCommand(): Command {
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask $composer_validate */
    $composer_validate = $this->composerValidate->reveal();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask $php_code_sniffer */
    $php_code_sniffer = $this->phpCodeSniffer->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask $php_lint */
    $php_lint = $this->phpLint->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask $php_loc */
    $php_loc = $this->phpLoc->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask $php_mess_detector */
    $php_mess_detector = $this->phpMessDetector->reveal();
    /** @var \Acquia\Orca\Task\TaskRunner $task_runner */
    $task_runner = $this->taskRunner->reveal();
    /** @var \Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask $yaml_lint */
    $yaml_lint = $this->yamlLint->reveal();
    return new QaStaticAnalysisCommand($composer_validate, $this->defaultPhpcsStandard, $filesystem, $php_code_sniffer, $php_lint, $php_loc, $php_mess_detector, $task_runner, $yaml_lint);
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
      ->addTask($this->phpLoc->reveal())
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

    $this->executeCommand(['path' => self::SUT_PATH]);

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, 1, StatusCode::OK, ''],
      [TRUE, 1, StatusCode::ERROR, ''],
      [FALSE, 0, StatusCode::ERROR, sprintf("Error: No such path: %s.\n", self::SUT_PATH)],
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
      ->willReturn(StatusCode::OK);

    $this->executeCommand($args);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerTaskFiltering() {
    return [
      [['--composer' => 1], 'composerValidate'],
      [['--phpcs' => 1], 'phpCodeSniffer'],
      [['--phplint' => 1], 'phpLint'],
      [['--phploc' => 1], 'phpLoc'],
      [['--phpmd' => 1], 'phpMessDetector'],
      [['--yamllint' => 1], 'yamlLint'],
    ];
  }

  /**
   * @dataProvider providerPhpcsStandardOption
   */
  public function testPhpcsStandardOption($args, $standard) {
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn(TRUE);
    $this->phpCodeSniffer
      ->setStandard(new PhpcsStandard($standard))
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->addTask($this->phpCodeSniffer->reveal())
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->run()
      ->shouldBeCalledOnce();
    $args['--phpcs'] = 1;
    $args['path'] = self::SUT_PATH;

    $this->executeCommand($args);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerPhpcsStandardOption() {
    return [
      [[], $this->defaultPhpcsStandard],
      [['--phpcs-standard' => PhpcsStandard::ACQUIA_PHP], PhpcsStandard::ACQUIA_PHP],
      [['--phpcs-standard' => PhpcsStandard::ACQUIA_DRUPAL_TRANSITIONAL], PhpcsStandard::ACQUIA_DRUPAL_TRANSITIONAL],
      [['--phpcs-standard' => PhpcsStandard::ACQUIA_DRUPAL_STRICT], PhpcsStandard::ACQUIA_DRUPAL_STRICT],
    ];
  }

  /**
   * @dataProvider providerPhpcsStandardEnvVar
   */
  public function testPhpcsStandardEnvVar($standard) {
    $this->defaultPhpcsStandard = $standard;
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn(TRUE);
    $this->phpCodeSniffer
      ->setStandard(new PhpcsStandard($this->defaultPhpcsStandard))
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->addTask($this->phpCodeSniffer->reveal())
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->run()
      ->shouldBeCalledOnce();
    $args = [
      '--phpcs' => 1,
      'path' => self::SUT_PATH,
    ];

    $this->executeCommand($args);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerPhpcsStandardEnvVar() {
    return [
      [PhpcsStandard::ACQUIA_PHP],
      [PhpcsStandard::ACQUIA_DRUPAL_TRANSITIONAL],
      [PhpcsStandard::ACQUIA_DRUPAL_STRICT],
    ];
  }

  /**
   * @dataProvider providerInvalidPhpcsStandard
   */
  public function testInvalidPhpcsStandard($args, $default_standard, $display) {
    $this->defaultPhpcsStandard = $default_standard;
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn(TRUE);
    $this->taskRunner
      ->run()
      ->shouldNotBeCalled();
    $args['--phpcs'] = 1;
    $args['path'] = self::SUT_PATH;

    $this->executeCommand($args);

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerInvalidPhpcsStandard() {
    return [
      [['--phpcs-standard' => 'invalid'], $this->defaultPhpcsStandard, 'Error: Invalid value for "--phpcs-standard" option: "invalid".' . PHP_EOL],
      [[], 'invalid', 'Error: Invalid value for $ORCA_PHPCS_STANDARD environment variable: "invalid".' . PHP_EOL],
    ];
  }

}
