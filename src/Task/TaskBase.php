<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Utility\ConfigFileOverrider;
use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides a base task implementation.
 */
abstract class TaskBase implements TaskInterface {

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
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  protected $fixture;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Filesystem\OrcaPathHandler
   */
  protected $orca;

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
   * @param \Acquia\Orca\Utility\ConfigFileOverrider $config_file_overrider
   *   The config file overrider.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Task\PhpcsConfigurator $phpcs_configurator
   *   The PHPCS configurator.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(ConfigFileOverrider $config_file_overrider, Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, SymfonyStyle $output, PhpcsConfigurator $phpcs_configurator, ProcessRunner $process_runner) {
    $this->configFileOverrider = $config_file_overrider;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->output = $output;

    // @todo The injection of this service in a base class like this constitutes
    //   a violation of the interface segregation principle because not all of
    //   its children use it. This is an indication for refactoring to use
    //   composition instead of inheritance.
    $this->phpcsConfigurator = $phpcs_configurator;

    $this->processRunner = $process_runner;
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
