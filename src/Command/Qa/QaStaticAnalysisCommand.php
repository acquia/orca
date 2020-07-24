<?php

namespace Acquia\Orca\Command\Qa;

use Acquia\Orca\Enum\PhpcsStandard;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhplocTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask;
use Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask;
use Acquia\Orca\Task\TaskRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use UnexpectedValueException;

/**
 * Provides a command.
 */
class QaStaticAnalysisCommand extends Command {

  /**
   * The Composer validate task.
   *
   * @var \Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask
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
   * @var \Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask
   */
  private $phpCodeSniffer;

  /**
   * The PHP lint task.
   *
   * @var \Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask
   */
  private $phplint;

  /**
   * The PHPLOC task.
   *
   * @var \Acquia\Orca\Task\StaticAnalysisTool\PhplocTask
   */
  private $phploc;

  /**
   * The PHP Mess Detector task.
   *
   * @var \Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask
   */
  private $phpMessDetector;

  /**
   * The task runner.
   *
   * @var \Acquia\Orca\Task\TaskRunner
   */
  private $taskRunner;

  /**
   * The YAML lint task.
   *
   * @var \Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask
   */
  private $yamlLint;

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'qa:static-analysis';

  /**
   * The default PHPCS standard.
   *
   * @var string
   */
  private $defaultPhpcsStandard;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask $composer_validate
   *   The Composer validate task.
   * @param string $default_phpcs_standard
   *   The default PHPCs standard.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask $php_code_sniffer
   *   The PHP Code Sniffer task.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask $phplint
   *   The PHP lint task.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhplocTask $phploc
   *   The PHPLOC task.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask $php_mess_detector
   *   The PHP Mess Detector task.
   * @param \Acquia\Orca\Task\TaskRunner $task_runner
   *   The task runner.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask $yaml_lint
   *   The YAML lint task.
   */
  public function __construct(ComposerValidateTask $composer_validate, string $default_phpcs_standard, Filesystem $filesystem, PhpCodeSnifferTask $php_code_sniffer, PhpLintTask $phplint, PhplocTask $phploc, PhpMessDetectorTask $php_mess_detector, TaskRunner $task_runner, YamlLintTask $yaml_lint) {
    $this->composerValidate = $composer_validate;
    $this->defaultPhpcsStandard = $default_phpcs_standard;
    $this->filesystem = $filesystem;
    $this->phpCodeSniffer = $php_code_sniffer;
    $this->phplint = $phplint;
    $this->phploc = $phploc;
    $this->phpMessDetector = $php_mess_detector;
    $this->taskRunner = $task_runner;
    $this->yamlLint = $yaml_lint;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['analyze'])
      ->setDescription('Runs static analysis tools')
      ->setHelp('Tools can be specified individually or in combination. If none are specified, all will be run.')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to analyze')
      ->addOption('composer', NULL, InputOption::VALUE_NONE, 'Run the Composer validation tool')
      ->addOption('phpcs', NULL, InputOption::VALUE_NONE, 'Run the PHP Code Sniffer tool')
      ->addOption('phpcs-standard', NULL, InputOption::VALUE_REQUIRED, implode(PHP_EOL, array_merge(
        ['Change the PHPCS standard used:'],
        PhpcsStandard::commandHelp()
      )), $this->defaultPhpcsStandard)
      ->addOption('phplint', NULL, InputOption::VALUE_NONE, 'Run the PHP Lint tool')
      ->addOption('phploc', NULL, InputOption::VALUE_NONE, 'Run the PHPLOC tool')
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
      return StatusCode::ERROR;
    }
    try {
      $this->configureTaskRunner($input);
    }
    catch (UnexpectedValueException $e) {
      $output->writeln($e->getMessage());
      return StatusCode::ERROR;
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
  private function configureTaskRunner(InputInterface $input) {
    $composer = $input->getOption('composer');
    $phpcs = $input->getOption('phpcs');
    $phplint = $input->getOption('phplint');
    $phploc = $input->getOption('phploc');
    $phpmd = $input->getOption('phpmd');
    $yamllint = $input->getOption('yamllint');
    // If NO tasks are specified, they are ALL implied.
    $all = !$composer && !$phpcs && !$phplint && !$phploc && !$phpmd && !$yamllint;

    if ($all || $composer) {
      $this->taskRunner->addTask($this->composerValidate);
    }
    if ($all || $phpcs) {
      $this->phpCodeSniffer->setStandard($this->getStandard($input));
      $this->taskRunner->addTask($this->phpCodeSniffer);
    }
    if ($all || $phploc) {
      $this->taskRunner->addTask($this->phploc);
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
   * @return \Acquia\Orca\Enum\PhpcsStandard
   *   The PHPCS standard.
   */
  private function getStandard(InputInterface $input): PhpcsStandard {
    $standard = $input->getOption('phpcs-standard') ?? $this->defaultPhpcsStandard;
    try {
      $standard = new PhpcsStandard($standard);
    }
    catch (UnexpectedValueException $e) {
      $error_message = sprintf('Error: Invalid value for "--phpcs-standard" option: "%s".', $standard);
      if (!$input->getParameterOption('--phpcs-standard')) {
        $error_message = sprintf('Error: Invalid value for $ORCA_PHPCS_STANDARD environment variable: "%s".', $standard);
      }
      throw new UnexpectedValueException($error_message, NULL, $e);
    }
    return $standard;
  }

}
