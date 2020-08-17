<?php

namespace Acquia\Orca\Task\DeprecatedCodeScanner;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Log\TelemetryClient;
use Acquia\Orca\Package\PackageManager;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Scans for deprecated code with PhpStan.
 */
class PhpStanTask {

  use SutSettingsTrait;

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
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The "scan contrib" flag.
   *
   * @var bool
   */
  private $scanContrib = FALSE;

  /**
   * The status code.
   *
   * @var int
   */
  private $status = StatusCode::OK;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The telemetry client.
   *
   * @var \Acquia\Orca\Log\TelemetryClient
   */
  private $telemetryClient;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Log\TelemetryClient $telemetry_client
   *   The telemetry client.
   */
  public function __construct(Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, SymfonyStyle $output, PackageManager $package_manager, ProcessRunner $process_runner, TelemetryClient $telemetry_client) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->output = $output;
    $this->packageManager = $package_manager;
    $this->processRunner = $process_runner;
    $this->telemetryClient = $telemetry_client;
  }

  /**
   * Executes the test.
   *
   * @return int
   *   The exit status code.
   */
  public function execute(): int {
    $this->command = $this->createCommand();
    try {
      $this->runCommand();
    }
    catch (ProcessFailedException $e) {
      $this->status = StatusCode::ERROR;
    }
    $this->logResults();
    return $this->status;
  }

  /**
   * Sets the "scan contrib" flag.
   *
   * @param bool $scan_contrib
   *   TRUE to scan contrib or FALSE not to.
   */
  public function setScanContrib(bool $scan_contrib): void {
    $this->scanContrib = $scan_contrib;
  }

  /**
   * Creates the command array.
   *
   * @return array
   *   The command array.
   */
  private function createCommand(): array {
    $command = [
      'phpstan',
      'analyse',
      "--configuration={$this->orca->getPath('resources/phpstan.neon')}",
    ];
    if ($this->sut) {
      $command[] = $this->sut->getInstallPathAbsolute();
    }
    if ($this->scanContrib) {
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
   * Runs Phpstan and sends output to the console.
   */
  protected function runCommand(): void {
    $this->processRunner->runFixtureVendorBin($this->command);
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
    $this->command[] = '--error-format=prettyJson';
    $process = $this->processRunner->createFixtureVendorBinProcess($this->command);
    $process->setWorkingDirectory($this->fixture->getPath());
    $process->run();

    // Write the output to the log file.
    $this->filesystem->dumpFile($file, trim($process->getOutput()));
  }

}
