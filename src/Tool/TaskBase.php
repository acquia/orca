<?php

namespace Acquia\Orca\Tool;

use Acquia\Orca\Helper\Config\ConfigFileOverrider;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tool\Phpcs\PhpcsConfigurator;
use LogicException;
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
  protected $cloverCoverage;

  /**
   * The config file overrider.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigFileOverrider
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
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  protected $fixture;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  protected $orca;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  /**
   * A filesystem path.
   *
   * @var string
   */
  protected $path;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  protected $processRunner;

  /**
   * The PHPCS configurator.
   *
   * @var \Acquia\Orca\Tool\Phpcs\PhpcsConfigurator
   */
  protected $phpcsConfigurator;

  /**
   * Constructs an instance.
   *
   * @param string $clover_coverage
   *   The Clover coverage XML path.
   * @param \Acquia\Orca\Helper\Config\ConfigFileOverrider $config_file_overrider
   *   The config file overrider.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Tool\Phpcs\PhpcsConfigurator $phpcs_configurator
   *   The PHPCS configurator.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(string $clover_coverage, ConfigFileOverrider $config_file_overrider, Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, SymfonyStyle $output, PhpcsConfigurator $phpcs_configurator, ProcessRunner $process_runner) {
    $this->configFileOverrider = $config_file_overrider;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->output = $output;

    // @todo The injection of these services in a base class like this
    //   constitutes a violation of the interface segregation principle because
    //   not all of its its children use them. This is an indication for
    //   refactoring to use some form of composition instead of inheritance.
    $this->cloverCoverage = $clover_coverage;
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
      throw new LogicException(sprintf('Path not set in %s:%s().', get_class($this), debug_backtrace()[1]['function']));
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
