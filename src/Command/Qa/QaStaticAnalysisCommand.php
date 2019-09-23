<?php

namespace Acquia\Orca\Command\Qa;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask;
use Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask;
use Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask;
use Acquia\Orca\Task\TaskRunner;
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
  private $phpLint;

  /**
   * The PHP LOC task.
   *
   * @var \Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask
   */
  private $phpLoc;

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
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\StaticAnalysisTool\ComposerValidateTask $composer_validate
   *   The Composer validate task.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpCodeSnifferTask $php_code_sniffer
   *   The PHP Code Sniffer task.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpLintTask $php_lint
   *   The PHP lint task.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpLocTask $php_loc
   *   The PHP LOC task.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\PhpMessDetectorTask $php_mess_detector
   *   The PHP Mess Detector task.
   * @param \Acquia\Orca\Task\TaskRunner $task_runner
   *   The task runner.
   * @param \Acquia\Orca\Task\StaticAnalysisTool\YamlLintTask $yaml_lint
   *   The YAML lint task.
   */
  public function __construct(ComposerValidateTask $composer_validate, Filesystem $filesystem, PhpCodeSnifferTask $php_code_sniffer, PhpLintTask $php_lint, PhpLocTask $php_loc, PhpMessDetectorTask $php_mess_detector, TaskRunner $task_runner, YamlLintTask $yaml_lint) {
    $this->composerValidate = $composer_validate;
    $this->filesystem = $filesystem;
    $this->phpCodeSniffer = $php_code_sniffer;
    $this->phpLint = $php_lint;
    $this->phpLoc = $php_loc;
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
      ->addOption('phplint', NULL, InputOption::VALUE_NONE, 'Run the PHP Lint tool')
      ->addOption('phploc', NULL, InputOption::VALUE_NONE, 'Run the PHP LOC tool')
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
    $this->configureTaskRunner($input);
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
    $all = !$composer && !$phpcs && !$phplint && !$phploc && !$phpmd && !$yamllint;

    if ($all || $composer) {
      $this->taskRunner->addTask($this->composerValidate);
    }
    if ($all || $phpcs) {
      $this->taskRunner->addTask($this->phpCodeSniffer);
    }
    if ($all || $phploc) {
      $this->taskRunner->addTask($this->phpLoc);
    }
    if ($all || $phplint) {
      $this->taskRunner->addTask($this->phpLint);
    }
    if ($all || $phpmd) {
      $this->taskRunner->addTask($this->phpMessDetector);
    }
    if ($all || $yamllint) {
      $this->taskRunner->addTask($this->yamlLint);
    }
  }

}
