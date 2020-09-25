<?php

namespace Acquia\Orca\Domain\Tool;

use Acquia\Orca\Helper\Config\ConfigFileOverrider;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;

/**
 * Runs PHPMD.
 */
class PhpmdTool implements ToolInterface {

  /**
   * The config file overrider.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigFileOverrider
   */
  private $configFileOverrider;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The path to run the tool on.
   *
   * @var string
   */
  private $path;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Config\ConfigFileOverrider $config_file_overrider
   *   The config file overrider.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca
   *   The ORCA path handler.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(ConfigFileOverrider $config_file_overrider, OrcaPathHandler $orca, ProcessRunner $process_runner) {
    $this->configFileOverrider = $config_file_overrider;
    $this->processRunner = $process_runner;
    $this->orca = $orca;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHP Mess Detector (PHPMD)';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Looking for potential problems in PHP source code';
  }

  /**
   * {@inheritdoc}
   */
  public function run(string $path = ''): void {
    $this->path = $path;
    $this->overrideConfig();
    $this->runPhpmd();
    $this->restoreConfig();
  }

  /**
   * Overrides the active configuration.
   */
  private function overrideConfig(): void {
    $this->configFileOverrider->setPaths(
      $this->orca->getPath('phpmd.xml.dist'),
      "{$this->path}/phpmd.xml"
    );
    $this->configFileOverrider->override();
  }

  /**
   * Runs phpmd.
   */
  private function runPhpmd(): void {
    $this->processRunner->runOrcaVendorBin([
      'phpmd',
      '.',
      'text',
      $this->orca->getPath('phpmd.xml.dist'),
      // Emit output but don't fail builds.
      '--ignore-violations-on-exit',
    ], $this->path);
  }

  /**
   * Restores the previous configuration.
   */
  private function restoreConfig(): void {
    $this->configFileOverrider->restore();
  }

}
