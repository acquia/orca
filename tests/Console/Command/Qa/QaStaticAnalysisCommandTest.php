<?php

namespace Acquia\Orca\Tests\Console\Command\Qa;

use Acquia\Orca\Console\Command\Qa\QaStaticAnalysisCommand;
use Acquia\Orca\Domain\Tool\ComposerValidate\ComposerValidateTask;
use Acquia\Orca\Domain\Tool\Coverage\CoverageTask;
use Acquia\Orca\Domain\Tool\Phpcs\PhpcsTask;
use Acquia\Orca\Domain\Tool\PhpLint\PhpLintTask;
use Acquia\Orca\Domain\Tool\Phpmd\PhpmdTask;
use Acquia\Orca\Domain\Tool\YamlLint\YamlLintTask;
use Acquia\Orca\Enum\PhpcsStandardEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Helper\Task\TaskRunner;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Acquia\Orca\Domain\Tool\ComposerValidate\ComposerValidateTask|\Prophecy\Prophecy\ObjectProphecy $composerValidate
 * @property \Acquia\Orca\Domain\Tool\Coverage\CoverageTask|\Prophecy\Prophecy\ObjectProphecy $coverage
 * @property \Acquia\Orca\Domain\Tool\Phpcs\PhpcsTask|\Prophecy\Prophecy\ObjectProphecy $phpCodeSniffer
 * @property \Acquia\Orca\Domain\Tool\PhpLint\PhpLintTask|\Prophecy\Prophecy\ObjectProphecy $phpLint
 * @property \Acquia\Orca\Domain\Tool\Phpmd\PhpmdTask|\Prophecy\Prophecy\ObjectProphecy $phpMessDetector
 * @property \Acquia\Orca\Domain\Tool\YamlLint\YamlLintTask|\Prophecy\Prophecy\ObjectProphecy $yamlLint
 * @property \Acquia\Orca\Helper\Task\TaskRunner|\Prophecy\Prophecy\ObjectProphecy $taskRunner
 * @property \Symfony\Component\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy $filesystem
 * @coversDefaultClass \Acquia\Orca\Console\Command\Qa\QaStaticAnalysisCommand
 */
class QaStaticAnalysisCommandTest extends CommandTestBase {

  private const COMMAND_OPTIONS = [
    'composer',
    'coverage',
    'phpcs',
    'phpcs-standard',
    'phplint',
    'phpmd',
    'yamllint',
  ];

  private const SUT_PATH = '/var/www/example';

  private $defaultPhpcsStandard = PhpcsStandardEnum::DEFAULT;
  protected ComposerValidateTask|ObjectProphecy $composerValidate;
  protected CoverageTask|ObjectProphecy $coverage;
  protected PhpcsTask|ObjectProphecy $phpCodeSniffer;
  protected PhpLintTask|ObjectProphecy $phpLint;
  protected PhpmdTask|ObjectProphecy $phpMessDetector;
  protected YamlLintTask|ObjectProphecy $yamlLint;
  protected TaskRunner|ObjectProphecy $taskRunner;
  protected Filesystem|ObjectProphecy $filesystem;

  protected function setUp(): void {
    $this->composerValidate = $this->prophesize(ComposerValidateTask::class);
    $this->coverage = $this->prophesize(CoverageTask::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->willReturn(TRUE);
    $this->phpCodeSniffer = $this->prophesize(PhpcsTask::class);
    $this->phpLint = $this->prophesize(PhpLintTask::class);
    $this->phpMessDetector = $this->prophesize(PhpmdTask::class);
    $this->taskRunner = $this->prophesize(TaskRunner::class);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask(Argument::any())
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->run()
      ->willReturn(StatusCodeEnum::OK);
    $this->yamlLint = $this->prophesize(YamlLintTask::class);
  }

  protected function createCommand(): Command {
    $composer_validate = $this->composerValidate->reveal();
    $coverage = $this->coverage->reveal();
    $filesystem = $this->filesystem->reveal();
    $php_code_sniffer = $this->phpCodeSniffer->reveal();
    $phplint = $this->phpLint->reveal();
    $php_mess_detector = $this->phpMessDetector->reveal();
    $task_runner = $this->taskRunner->reveal();
    $yaml_lint = $this->yamlLint->reveal();
    return new QaStaticAnalysisCommand($coverage, $composer_validate, $this->defaultPhpcsStandard, $filesystem, $php_code_sniffer, $phplint, $php_mess_detector, $task_runner, $yaml_lint);
  }

  /**
   * @covers ::__construct
   * @covers ::configure
   */
  public function testBasicConfiguration(): void {
    $command = $this->createCommand();

    $definition = $command->getDefinition();
    $arguments = $definition->getArguments();
    $path_argument = $definition->getArgument('path');
    $options = $definition->getOptions();

    self::assertEquals('qa:static-analysis', $command->getName(), 'Set correct name.');
    self::assertEquals(['analyze'], $command->getAliases(), 'Set correct aliases.');
    self::assertNotEmpty($command->getDescription(), 'Set a description.');
    self::assertEquals(['path'], array_keys($arguments), 'Set correct arguments.');
    self::assertTrue($path_argument->isRequired(), 'Required path argument.');
    self::assertEquals(self::COMMAND_OPTIONS, array_keys($options), 'Set correct options.');
    self::assertCount(count($options), $this->providerOptions(), '::providerOptions() contains all options.');
  }

  /**
   * @dataProvider providerOptions
   *
   * @covers ::configure
   */
  public function testOptions(string $name, $default): void {
    $command = $this->createCommand();

    $definition = $command->getDefinition();
    $option = $definition->getOption($name);

    self::assertNotEmpty($option->getDescription(), 'Set a description');
    self::assertEquals($default, $option->getDefault(), 'Set correct default.');
  }

  public static function providerOptions(): array {
    return [
      ['composer', FALSE],
      ['coverage', FALSE],
      ['phpcs', FALSE],
      ['phpcs-standard', 'AcquiaDrupalTransitional'],
      ['phplint', FALSE],
      ['phpmd', FALSE],
      ['yamllint', FALSE],
    ];
  }

  /**
   * @coversNothing
   */
  public function testProviderOptions(): void {
    self::assertCount(count(self::COMMAND_OPTIONS), $this->providerOptions(), 'Options data provider has the right number of rows.');
  }

  /**
   * @dataProvider providerExecution
   */
  public function testExecution($path_exists, $run_called, $status_code, $display): void {
    $this->filesystem
      ->exists(self::SUT_PATH)
      ->shouldBeCalledTimes(1)
      ->willReturn($path_exists);
    $this->taskRunner
      ->addTask($this->composerValidate->reveal())
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->coverage->reveal())
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

    $this->executeCommand(['path' => self::SUT_PATH]);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public static function providerExecution(): array {
    return [
      [TRUE, 1, StatusCodeEnum::OK, ''],
      [TRUE, 1, StatusCodeEnum::ERROR, ''],
      [FALSE, 0, StatusCodeEnum::ERROR, sprintf("Error: No such path: %s.\n", self::SUT_PATH)],
    ];
  }

  /**
   * @dataProvider providerTaskFiltering
   */
  public function testTaskFiltering(array $args, $task): void {
    $this->taskRunner
      ->addTask(Argument::any())
      ->shouldBeCalledTimes(1);
    $this->taskRunner
      ->addTask($this->$task->reveal())
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledTimes(1);
    $this->taskRunner
      ->run()
      ->shouldBeCalledTimes(1);
    $args['path'] = self::SUT_PATH;

    $this->executeCommand($args);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  /**
   * @see testCoverageOptionSpecialCaseTaskFiltering
   */
  public static function providerTaskFiltering(): array {
    return [
      [['--composer' => 1], 'composerValidate'],
      [['--phpcs' => 1], 'phpCodeSniffer'],
      [['--phplint' => 1], 'phpLint'],
      [['--phpmd' => 1], 'phpMessDetector'],
      [['--yamllint' => 1], 'yamlLint'],
    ];
  }

  /**
   * @dataProvider providerCoverageOptionSpecialCaseTaskFiltering
   */
  public function testCoverageOptionSpecialCaseTaskFiltering(array $args): void {
    $this->taskRunner
      ->addTask(Argument::any())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->coverage->reveal())
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $args['path'] = self::SUT_PATH;

    $this->executeCommand($args);

    self::assertTrue(TRUE);
  }

  public static function providerCoverageOptionSpecialCaseTaskFiltering(): array {
    return [
      [['--coverage' => 1]],
    ];
  }

  /**
   * @dataProvider providerPhpcsStandardOption
   */
  public function testPhpcsStandardOption(array $args, $standard): void {
    $this->phpCodeSniffer
      ->setStandard(new PhpcsStandardEnum($standard))
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->addTask($this->phpCodeSniffer->reveal())
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->run()
      ->shouldBeCalledOnce();
    $args['--phpcs'] = 1;
    $args['path'] = self::SUT_PATH;

    $this->executeCommand($args);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public static function providerPhpcsStandardOption(): array {
    return [
      [[], PhpcsStandardEnum::DEFAULT],
      [['--phpcs-standard' => PhpcsStandardEnum::ACQUIA_PHP], PhpcsStandardEnum::ACQUIA_PHP],
      [['--phpcs-standard' => PhpcsStandardEnum::ACQUIA_DRUPAL_TRANSITIONAL], PhpcsStandardEnum::ACQUIA_DRUPAL_TRANSITIONAL],
      [['--phpcs-standard' => PhpcsStandardEnum::ACQUIA_DRUPAL_STRICT], PhpcsStandardEnum::ACQUIA_DRUPAL_STRICT],
    ];
  }

  /**
   * @dataProvider providerPhpcsStandardEnvVar
   */
  public function testPhpcsStandardEnvVar($standard): void {
    $this->defaultPhpcsStandard = $standard;
    $this->phpCodeSniffer
      ->setStandard(new PhpcsStandardEnum($this->defaultPhpcsStandard))
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->addTask($this->phpCodeSniffer->reveal())
      ->shouldBeCalledOnce()
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->setPath(self::SUT_PATH)
      ->shouldBeCalledOnce();
    $this->taskRunner
      ->run()
      ->shouldBeCalledOnce();
    $args = [
      '--phpcs' => 1,
      'path' => self::SUT_PATH,
    ];

    $this->executeCommand($args);

    self::assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public static function providerPhpcsStandardEnvVar(): array {
    return [
      [PhpcsStandardEnum::ACQUIA_PHP],
      [PhpcsStandardEnum::ACQUIA_DRUPAL_TRANSITIONAL],
      [PhpcsStandardEnum::ACQUIA_DRUPAL_STRICT],
    ];
  }

  /**
   * @dataProvider providerInvalidPhpcsStandard
   */
  public function testInvalidPhpcsStandard(array $args, $default_standard, $display): void {
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

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals(StatusCodeEnum::ERROR, $this->getStatusCode(), 'Returned correct status code.');
  }

  public static function providerInvalidPhpcsStandard(): array {
    return [
      [['--phpcs-standard' => 'invalid'], PhpcsStandardEnum::DEFAULT, 'Error: Invalid value for "--phpcs-standard" option: "invalid".' . PHP_EOL],
      [[], 'invalid', 'Error: Invalid value for $ORCA_PHPCS_STANDARD environment variable: "invalid".' . PHP_EOL],
    ];
  }

  public function testCoverageNoFilesToScan(): void {
    $this->coverage
      ->execute()
      ->willThrow(OrcaFileNotFoundException::class);

    $this->executeCommand(['path' => self::SUT_PATH]);

    self::assertEquals(StatusCodeEnum::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

}
