<?php

namespace Acquia\Orca\Facade;

use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Utility\ProcessRunner;

/**
 * Provides a facade for encapsulating PHPLOC interactions.
 */
class PhplocFacade {

  public const JSON_LOG_PATH = 'var/log/phploc.json';

  /**
   * PHP filename extensions as PHPLOC expects them.
   *
   * @see \Acquia\Orca\Task\CodeCoverageReportBuilder::PHP_NAME_PATTERNS
   */
  private const PHP_EXTENSIONS = '*.php,*.module,*.theme,*.inc,*.install,*.profile,*.engine';

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(OrcaPathHandler $orca_path_handler, ProcessRunner $process_runner) {
    $this->orca = $orca_path_handler;
    $this->processRunner = $process_runner;
  }

  /**
   * Executes PHPLOC.
   *
   * @param string $path
   *   The path to execute on.
   */
  public function execute(string $path): void {
    $this->processRunner->runOrcaVendorBin([
      'phploc',
      '--names=' . self::PHP_EXTENSIONS,
      '--exclude=tests',
      '--exclude=var',
      '--exclude=vendor',
      '--exclude=docroot',
      "--log-json={$this->orca->getPath(self::JSON_LOG_PATH)}",
      '.',
    ], $path);
  }

}
