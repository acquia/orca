<?php

namespace Acquia\Orca\Console\Command\Qa;

use Acquia\Orca\Domain\Tool\ComposerValidate\ComposerValidateTask;
use Acquia\Orca\Domain\Tool\Coverage\CoverageTask;
use Acquia\Orca\Domain\Tool\Phpcs\PhpcsTask;
use Acquia\Orca\Domain\Tool\PhpLint\PhpLintTask;
use Acquia\Orca\Domain\Tool\Phploc\PhplocTask;
use Acquia\Orca\Domain\Tool\Phpmd\PhpmdTask;
use Acquia\Orca\Domain\Tool\YamlLint\YamlLintTask;
use Acquia\Orca\Enum\PhpcsStandardEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Task\TaskRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a command.
 */
class QaStaticAnalysisCommand extends Command {

  /**
   * The Composer validate task.
   *
   * @var \Acquia\Orca\Domain\Tool\ComposerValidate\ComposerValidateTask
   */
  private $composerValidate;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The PHP Code Sniffer task.
   *
   * @var \Acquia\Orca\Domain\Tool\Phpcs\PhpcsTask
   */
  private $phpCodeSniffer;

  /**
   * The PHP lint task.
   *
   * @var \Acquia\Orca\Domain\Tool\PhpLint\PhpLintTask
   */
  private $phplint;

  /**
   * The PHPLOC task.
   *
   * @var \Acquia\Orca\Domain\Tool\Phploc\PhplocTask
   */
  private $phploc;

  /**
   * The PHP Mess Detector task.
   *
   * @var \Acquia\Orca\Domain\Tool\Phpmd\PhpmdTask
   */
  private $phpMessDetector;

  /**
   * The task runner.
   *
   * @var \Acquia\Orca\Helper\Task\TaskRunner
   */
  private $taskRunner;

  /**
   * The YAML lint task.
   *
   * @var \Acquia\Orca\Domain\Tool\YamlLint\YamlLintTask
   */
  private $yamlLint;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'qa:static-analysis';

  /**
   * The default PHPCS standard.
   *
   * @var string
   */
  private $defaultPhpcsStandard;

  /**
   * The code coverage task.
   *
   * @var \Acquia\Orca\Domain\Tool\Coverage\CoverageTask
   */
  private $coverage;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Tool\Coverage\CoverageTask $coverage
   *   The code coverage task.
   * @param \Acquia\Orca\Domain\Tool\ComposerValidate\ComposerValidateTask $composer_validate
   *   The Composer validate task.
   * @param string $default_phpcs_standard
   *   The default PHPCs standard.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Domain\Tool\Phpcs\PhpcsTask $php_code_sniffer
   *   The PHP Code Sniffer task.
   * @param \Acquia\Orca\Domain\Tool\PhpLint\PhpLintTask $phplint
   *   The PHP lint task.
   * @param \Acquia\Orca\Domain\Tool\Phploc\PhplocTask $phploc
   *   The PHPLOC task.
   * @param \Acquia\Orca\Domain\Tool\Phpmd\PhpmdTask $php_mess_detector
   *   The PHP Mess Detector task.
   * @param \Acquia\Orca\Helper\Task\TaskRunner $task_runner
   *   The task runner.
   * @param \Acquia\Orca\Domain\Tool\YamlLint\YamlLintTask $yaml_lint
   *   The YAML lint task.
   */
  public function __construct(CoverageTask $coverage, ComposerValidateTask $composer_validate, string $default_phpcs_standard, Filesystem $filesystem, PhpcsTask $php_code_sniffer, PhpLintTask $phplint, PhplocTask $phploc, PhpmdTask $php_mess_detector, TaskRunner $task_runner, YamlLintTask $yaml_lint) {
    $this->composerValidate = $composer_validate;
    $this->coverage = $coverage;
    $this->defaultPhpcsStandard = $default_phpcs_standard;
    $this->filesystem = $filesystem;
    $this->phpCodeSniffer = $php_code_sniffer;
    $this->phplint = $phplint;
    $this->phploc = $phploc;
    $this->phpMessDetector = $php_mess_detector;
    $this->taskRunner = $task_runner;
    $this->yamlLint = $yaml_lint;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure():void {
    $this
      ->setAliases(['analyze'])
      ->setDescription('Runs static analysis tools')
      ->setHelp('Tools can be specified individually or in combination. If none are specified, all will be run.')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to analyze')
      ->addOption('composer', NULL, InputOption::VALUE_NONE, 'Run the Composer validation tool')
      ->addOption('coverage', NULL, InputOption::VALUE_NONE, 'Run the code coverage estimator. Implies "--phploc"')
      ->addOption('phpcs', NULL, InputOption::VALUE_NONE, 'Run the PHP Code Sniffer tool')
      ->addOption('phpcs-standard', NULL, InputOption::VALUE_REQUIRED, implode(PHP_EOL, array_merge(
        ['Change the PHPCS standard used:'],
        PhpcsStandardEnum::commandHelp()
      )), $this->defaultPhpcsStandard)
      ->addOption('phplint', NULL, InputOption::VALUE_NONE, 'Run the PHP Lint tool')
      ->addOption('phpmd', NULL, InputOption::VALUE_NONE, 'Run the PHP Mess Detector tool')
      ->addOption('yamllint', NULL, InputOption::VALUE_NONE, 'Run the YAML Lint tool');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $path = $input->getArgument('path');
    if (!$this->filesystem->exists($path)) {
      $output->writeln(sprintf('Error: No such path: %s.', $path));
      return StatusCodeEnum::ERROR;
    }
    try {
      $this->configureTaskRunner($input);
    }
    // Catch an invalid command option value.
    catch (\UnexpectedValueException $e) {
      $output->writeln($e->getMessage());
      return StatusCodeEnum::ERROR;
    }
    return $this->taskRunner
      ->setPath($path)
      ->run();
  }

  /**
   * Configures the task runner.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   */
  private function configureTaskRunner(InputInterface $input): void {
    $composer = $input->getOption('composer');
    $coverage = $input->getOption('coverage');
    $phpcs = $input->getOption('phpcs');
    $phplint = $input->getOption('phplint');
    $phploc = $input->getOption('phploc');
    $phpmd = $input->getOption('phpmd');
    $yamllint = $input->getOption('yamllint');
    // If NO tasks are specified, they are ALL implied.
    $all = !$composer && !$coverage && !$phpcs && !$phplint && !$phploc && !$phpmd && !$yamllint;

    if ($all || $composer) {
      $this->taskRunner->addTask($this->composerValidate);
    }
    if ($all || $phpcs) {
      $this->phpCodeSniffer->setStandard($this->getStandard($input));
      $this->taskRunner->addTask($this->phpCodeSniffer);
    }
    if ($all || $coverage) {
      $this->taskRunner->addTask($this->coverage);
    }
    if ($all || $phplint) {
      $this->taskRunner->addTask($this->phplint);
    }
    if ($all || $phpmd) {
      $this->taskRunner->addTask($this->phpMessDetector);
    }
    if ($all || $yamllint) {
      $this->taskRunner->addTask($this->yamlLint);
    }
  }

  /**
   * Gets the PHPCS standard to use.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The command input.
   *
   * @return \Acquia\Orca\Enum\PhpcsStandardEnum
   *   The PHPCS standard.
   */
  private function getStandard(InputInterface $input): PhpcsStandardEnum {
    $standard = $input->getOption('phpcs-standard') ?? $this->defaultPhpcsStandard;
    try {
      $standard = new PhpcsStandardEnum($standard);
    }
    catch (\UnexpectedValueException $e) {
      $error_message = sprintf('Error: Invalid value for "--phpcs-standard" option: "%s".', $standard);
      if (!$input->getParameterOption('--phpcs-standard')) {
        $error_message = sprintf('Error: Invalid value for $ORCA_PHPCS_STANDARD environment variable: "%s".', $standard);
      }
      throw new \UnexpectedValueException($error_message, 0, $e);
    }
    return $standard;
  }

}
