<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\ConfigFileOverrider;
use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a base task implementation.
 */
abstract class TaskBase implements TaskInterface {

  /**
   * The Clover coverage XML path.
   *
   * @var string
   */
  protected $cloverCoveragePath;

  /**
   * The config file overrider.
   *
   * @var \Acquia\Orca\Utility\ConfigFileOverrider
   */
  protected $configFileOverrider;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $filesystem;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  protected $fixture;

  /**
   * A filesystem path.
   *
   * @var string
   */
  protected $path;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  protected $processRunner;

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  protected $projectDir;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  /**
   * The PHPCS configurator.
   *
   * @var \Acquia\Orca\Task\PhpcsConfigurator
   */
  protected $phpcsConfigurator;

  /**
   * Constructs an instance.
   *
   * @param string $clover_coverage_path
   *   The Clover coverage XML path.
   * @param \Acquia\Orca\Utility\ConfigFileOverrider $config_file_overrider
   *   The config file overrider.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Task\PhpcsConfigurator $phpcs_configurator
   *   The PHPCS configurator.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(string $clover_coverage_path, ConfigFileOverrider $config_file_overrider, Filesystem $filesystem, Fixture $fixture, SymfonyStyle $output, PhpcsConfigurator $phpcs_configurator, ProcessRunner $process_runner, string $project_dir) {
    $this->configFileOverrider = $config_file_overrider;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture;
    $this->output = $output;

    // @todo The injection of these services in a base class like this
    //   constitutes a violation of the interface segregation principle because
    //   not all of its children use it. This is an indication for refactoring
    //   to use composition instead of inheritance.
    $this->cloverCoveragePath = $clover_coverage_path;
    $this->phpcsConfigurator = $phpcs_configurator;

    $this->processRunner = $process_runner;
    $this->projectDir = $project_dir;
  }

  /**
   * Gets the path.
   *
   * @return string
   *   The path.
   */
  public function getPath(): string {
    if (!$this->path) {
      throw new \LogicException(sprintf('Path not set in %s:%s().', get_class($this), debug_backtrace()[1]['function']));
    }
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath(?string $path): TaskInterface {
    $this->path = $path;
    return $this;
  }

}
