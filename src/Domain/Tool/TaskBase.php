<?php

namespace Acquia\Orca\Domain\Tool;

use Acquia\Orca\Domain\Composer\ComposerFacade;
use Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator;
use Acquia\Orca\Helper\Config\ConfigFileOverrider;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
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
  protected $coberturaCoverage;

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
   * The JUNIT XML path.
   *
   * @var string
   */
  protected $junitLog;

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
   * The PHPCBF tool.
   *
   * @var \Acquia\Orca\Domain\Tool\PhpcbfTool
   */
  protected $phpcbfTool;

  /**
   * The PHPCS configurator.
   *
   * @var \Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator
   */
  protected $phpcsConfigurator;

  /**
   * The PHP lint tool.
   *
   * @var \Acquia\Orca\Domain\Tool\PhpLintTool
   */
  protected $phpLintTool;

  /**
   * The PHPMD tool.
   *
   * @var \Acquia\Orca\Domain\Tool\PhpmdTool
   */
  protected $phpmdTool;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  protected $processRunner;

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Domain\Composer\ComposerFacade
   */
  protected $composerFacade;

  /**
   * Constructs an instance.
   *
   * @param string $cobertura_coverage
   *   The Clover coverage XML path.
   * @param \Acquia\Orca\Helper\Config\ConfigFileOverrider $config_file_overrider
   *   The config file overrider.
   * @param \Acquia\Orca\Domain\Composer\ComposerFacade $composer_facade
   *   The composer facade.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param string $junit_log
   *   The Junit XML path.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Domain\Tool\PhpcbfTool $phpcbf_tool
   *   The PHPCBF tool.
   * @param \Acquia\Orca\Domain\Tool\Phpcs\PhpcsConfigurator $phpcs_configurator
   *   The PHPCS configurator.
   * @param \Acquia\Orca\Domain\Tool\PhpLintTool $php_lint_tool
   *   The PHP lint tool.
   * @param \Acquia\Orca\Domain\Tool\PhpmdTool $phpmd_tool
   *   The PHPMD tool.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(string $cobertura_coverage, ConfigFileOverrider $config_file_overrider, ComposerFacade $composer_facade, Filesystem $filesystem, FixturePathHandler $fixture_path_handler, string $junit_log, OrcaPathHandler $orca_path_handler, SymfonyStyle $output, PhpcbfTool $phpcbf_tool, PhpcsConfigurator $phpcs_configurator, PhpLintTool $php_lint_tool, PhpmdTool $phpmd_tool, ProcessRunner $process_runner) {
    $this->configFileOverrider = $config_file_overrider;
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->output = $output;

    // @todo The injection of these services in a base class like this
    //   constitutes a violation of the interface segregation principle because
    //   not all of its its children use them. This is an indication for
    //   refactoring to use some form of composition instead of inheritance.
    $this->coberturaCoverage = $cobertura_coverage;
    $this->composerFacade = $composer_facade;
    $this->junitLog = $junit_log;
    $this->phpcsConfigurator = $phpcs_configurator;

    $this->phpcbfTool = $phpcbf_tool;
    $this->phpLintTool = $php_lint_tool;
    $this->phpmdTool = $phpmd_tool;
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
