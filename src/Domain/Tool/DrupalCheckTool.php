<?php

namespace Acquia\Orca\Domain\Tool;

use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Log\TelemetryClient;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs drupal-check.
 */
class DrupalCheckTool {

  public const JSON_LOG_PATH = 'var/log/phpstan.json';

  /**
   * The command array.
   *
   * @var array
   */
  private $command = [];

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The status code.
   *
   * @var int
   */
  private $status = StatusCodeEnum::OK;

  /**
   * The telemetry client.
   *
   * @var \Acquia\Orca\Helper\Log\TelemetryClient
   */
  private $telemetryClient;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Helper\Log\TelemetryClient $telemetry_client
   *   The telemetry client.
   */
  public function __construct(Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, SymfonyStyle $output, PackageManager $package_manager, ProcessRunner $process_runner, TelemetryClient $telemetry_client) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->packageManager = $package_manager;
    $this->processRunner = $process_runner;
    $this->telemetryClient = $telemetry_client;
    $this->output = $output;
  }

  /**
   * Runs drupal-check.
   *
   * @param string|null $sut_name
   *   The SUT name if available (e.g., "drupal/example") or NULL if not.
   * @param bool $scan_contrib
   *   TRUE to scan contrib packages or FALSE not to.
   *
   * @return int
   *   The exit status code.
   */
  public function run(?string $sut_name, bool $scan_contrib): int {
    $this->command = $this->createCommand($sut_name, $scan_contrib);
    try {
      $this->runCommand();
    }
    catch (ProcessFailedException $e) {
      $this->status = StatusCodeEnum::ERROR;
    }
    $this->logResults();
    return $this->status;
  }

  /**
   * Creates the command array.
   *
   * @param string|null $sut_name
   *   The SUT name if available (e.g., "drupal/example") or NULL if not.
   * @param bool $scan_contrib
   *   TRUE to scan contrib packages or FALSE not to.
   *
   * @return string[]
   *   The command array.
   */
  private function createCommand(?string $sut_name, bool $scan_contrib): array {
    $command = [
      'drupal-check',
      '-d',
      "--drupal-root={$this->fixture->getPath()}",
    ];
    if ($sut_name) {
      $sut = $this->packageManager->get($sut_name);
      $command[] = $sut->getInstallPathAbsolute();
    }
    if ($scan_contrib) {
      $command[] = $this->getAndEnsurePath('docroot/modules/contrib');
      $command[] = $this->getAndEnsurePath('docroot/profiles/contrib');
      $command[] = $this->getAndEnsurePath('docroot/themes/contrib');
      $command[] = $this->getAndEnsurePath('vendor/acquia');
    }
    return $command;
  }

  /**
   * Gets a fixture path and ensures its presence.
   *
   * @param string $path
   *   The path to ensure.
   *
   * @return string
   *   The absolute path.
   */
  private function getAndEnsurePath(string $path): string {
    $absolute_path = $this->fixture->getPath($path);
    $this->filesystem->mkdir($absolute_path);
    return $absolute_path;
  }

  /**
   * Runs drupal-check and sends output to the console.
   */
  private function runCommand(): void {
    $this->processRunner->runOrcaVendorBin($this->command);
  }

  /**
   * Runs and logs the output to a file.
   */
  protected function logResults(): void {
    if (!$this->telemetryClient->isReady()) {
      return;
    }

    $this->output->comment('Logging results...');

    // Prepare the log file.
    $file = $this->orca->getPath(self::JSON_LOG_PATH);
    $this->filesystem->remove($file);

    // Run the command.
    $this->command[] = '--format=json';
    $process = $this->processRunner->createOrcaVendorBinProcess($this->command);
    $process->setWorkingDirectory($this->fixture->getPath());
    $process->run();

    // Write the output to the log file.
    $this->filesystem->dumpFile($file, trim($process->getOutput()));
  }

}
