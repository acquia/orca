<?php

namespace Acquia\Orca\Command\Qa;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Task\Fixer\ComposerNormalizeTask;
use Acquia\Orca\Task\Fixer\PhpCodeBeautifierAndFixerTask;
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
class QaFixerCommand extends Command {

  /**
   * The Composer normalize task.
   *
   * @var \Acquia\Orca\Task\Fixer\ComposerNormalizeTask
   */
  private $composerNormalize;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The PHP Code Beautifier and Fixer task.
   *
   * @var \Acquia\Orca\Task\Fixer\PhpCodeBeautifierAndFixerTask
   */
  private $phpCodeBeautifierAndFixer;

  /**
   * The task runner.
   *
   * @var \Acquia\Orca\Task\TaskRunner
   */
  private $taskRunner;

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'qa:fixer';

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\Fixer\ComposerNormalizeTask $composer_normalize
   *   The Composer normalize task.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Task\Fixer\PhpCodeBeautifierAndFixerTask $php_code_beautifier_and_fixer
   *   The PHP Code Beautifier and Fixer task.
   * @param \Acquia\Orca\Task\TaskRunner $task_runner
   *   The task runner.
   */
  public function __construct(ComposerNormalizeTask $composer_normalize, Filesystem $filesystem, PhpCodeBeautifierAndFixerTask $php_code_beautifier_and_fixer, TaskRunner $task_runner) {
    $this->composerNormalize = $composer_normalize;
    $this->filesystem = $filesystem;
    $this->phpCodeBeautifierAndFixer = $php_code_beautifier_and_fixer;
    $this->taskRunner = $task_runner;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['fix'])
      ->setDescription('Fixes issues found by static analysis tools')
      ->setHelp('Tools can be specified individually or in combination. If none are specified, all will be run.')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to fix issues in')
      ->addOption('composer', NULL, InputOption::VALUE_NONE, 'Run the Composer Normalizer tool')
      ->addOption('phpcbf', NULL, InputOption::VALUE_NONE, 'Run the PHP Code Beautifier and Fixer tool');
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
    $phpcbf = $input->getOption('phpcbf');
    $all = !$composer && !$phpcbf;

    if ($all || $composer) {
      $this->taskRunner->addTask($this->composerNormalize);
    }
    if ($all || $phpcbf) {
      $this->taskRunner->addTask($this->phpCodeBeautifierAndFixer);
    }
  }

}
