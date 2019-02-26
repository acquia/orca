<?php

namespace Acquia\Orca\Command\StaticAnalysis;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Task\ComposerValidateTask;
use Acquia\Orca\Task\PhpCodeSnifferTask;
use Acquia\Orca\Task\PhpLintTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Task\YamlLintTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a command.
 */
class StaticAnalysisRunCommand extends Command {

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

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
  protected static $defaultName = 'static-analysis:run';

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\ComposerValidateTask $composer_validate
   *   The Composer validate task.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Task\PhpCodeSnifferTask $php_code_sniffer
   *   The PHP Code Sniffer task.
   * @param \Acquia\Orca\Task\PhpLintTask $php_lint
   *   The PHP lint task.
   * @param \Acquia\Orca\Task\TaskRunner $task_runner
   *   The task runner.
   * @param \Acquia\Orca\Task\YamlLintTask $yaml_lint
   *   The YAML lint task.
   */
  public function __construct(ComposerValidateTask $composer_validate, Filesystem $filesystem, PhpCodeSnifferTask $php_code_sniffer, PhpLintTask $php_lint, TaskRunner $task_runner, YamlLintTask $yaml_lint) {
    $this->filesystem = $filesystem;
    $this->taskRunner = $task_runner
      ->addTask($composer_validate)
      ->addTask($php_lint)
      ->addTask($php_code_sniffer)
      ->addTask($yaml_lint);
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['analyze'])
      ->setDescription('Runs static analysis tools')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to analyze.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $path = $input->getArgument('path');
    if (!$this->filesystem->exists($path)) {
      $output->writeln(sprintf('Error: No such path: %s.', $path));
      return StatusCodes::ERROR;
    }
    return $this->taskRunner
      ->setPath($path)
      ->run();
  }

}
