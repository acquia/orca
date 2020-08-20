<?php

namespace Acquia\Orca\Tests\Console\Command\Qa;

use Acquia\Orca\Console\Command\Qa\QaFixerCommand;
use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Helper\Task\TaskRunner;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Acquia\Orca\Tool\ComposerNormalize\ComposerNormalizeTask;
use Acquia\Orca\Tool\Helper\PhpcsStandard;
use Acquia\Orca\Tool\Phpcbf\PhpcbfTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Tool\ComposerNormalize\ComposerNormalizeTask composerNormalize
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Tool\Phpcbf\PhpcbfTask phpCodeBeautifierAndFixer
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Task\TaskRunner $taskRunner
 */
class QaFixerCommandTest extends CommandTestBase {

  private const SUT_PATH = '/var/www/example';

  private $defaultPhpcsStandard = PhpcsStandard::DEFAULT;

  protected function setUp() {
    $this->composerNormalize = $this->prophesize(ComposerNormalizeTask::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->phpCodeBeautifierAndFixer = $this->prophesize(PhpcbfTask::class);
    $this->taskRunner = $this->prophesize(TaskRunner::class);
  }

  protected function createCommand(): Command {
    $composer_normalize = $this->composerNormalize->reveal();
    $filesystem = $this->filesystem->reveal();
    $php_code_beautifier_and_fixer = $this->phpCodeBeautifierAndFixer->reveal();
    $task_runner = $this->taskRunner->reveal();
    return new QaFixerCommand($composer_normalize, $this->defaultPhpcsStandard, $filesystem, $php_code_beautifier_and_fixer, $task_runner);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($path_exists, $run_called, $status_code, $display): void {
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

    $this->executeCommand(['path' => self::SUT_PATH]);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [TRUE, 1, StatusCode::OK, ''],
      [TRUE, 1, StatusCode::ERROR, ''],
      [FALSE, 0, StatusCode::ERROR, sprintf("Error: No such path: %s.\n", self::SUT_PATH)],
    ];
  }

  /**
   * @dataProvider providerTaskFiltering
   */
  public function testTaskFiltering($args, $task): void {
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

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerTaskFiltering(): array {
    return [
      [['--composer' => 1], 'composerNormalize'],
      [['--phpcbf' => 1], 'phpCodeBeautifierAndFixer'],
    ];
  }

  /**
   * @dataProvider providerPhpcsStandardOption
   */
  public function testPhpcsStandardOption($args, $standard): void {
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn(TRUE);
    $this->phpCodeBeautifierAndFixer
      ->setStandard(new PhpcsStandard($standard))
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->addTask($this->phpCodeBeautifierAndFixer->reveal())
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->run()
      ->shouldBeCalledOnce();
    $args['--phpcbf'] = 1;
    $args['path'] = self::SUT_PATH;

    $this->executeCommand($args);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerPhpcsStandardOption(): array {
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
  public function testPhpcsStandardEnvVar($standard): void {
    $this->defaultPhpcsStandard = $standard;
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn(TRUE);
    $this->phpCodeBeautifierAndFixer
      ->setStandard(new PhpcsStandard($this->defaultPhpcsStandard))
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->addTask($this->phpCodeBeautifierAndFixer->reveal())
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
      '--phpcbf' => 1,
      'path' => self::SUT_PATH,
    ];

    $this->executeCommand($args);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerPhpcsStandardEnvVar(): array {
    return [
      [PhpcsStandard::ACQUIA_PHP],
      [PhpcsStandard::ACQUIA_DRUPAL_TRANSITIONAL],
      [PhpcsStandard::ACQUIA_DRUPAL_STRICT],
    ];
  }

  /**
   * @dataProvider providerInvalidPhpcsStandard
   */
  public function testInvalidPhpcsStandard($args, $default_standard, $display): void {
    $this->defaultPhpcsStandard = $default_standard;
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledOnce()
      ->willReturn(TRUE);
    $this->taskRunner
      ->run()
      ->shouldNotBeCalled();
    $args['--phpcbf'] = 1;
    $args['path'] = self::SUT_PATH;

    $this->executeCommand($args);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCode::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerInvalidPhpcsStandard(): array {
    return [
      [['--phpcs-standard' => 'invalid'], $this->defaultPhpcsStandard, 'Error: Invalid value for "--phpcs-standard" option: "invalid".' . PHP_EOL],
      [[], 'invalid', 'Error: Invalid value for $ORCA_PHPCS_STANDARD environment variable: "invalid".' . PHP_EOL],
    ];
  }

}
