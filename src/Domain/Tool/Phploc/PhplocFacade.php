<?php

namespace Acquia\Orca\Domain\Tool\Phploc;

use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;

/**
 * Provides a facade for PHPLOC.
 */
class PhplocFacade {

  public const JSON_LOG_PATH = 'var/log/phploc.json';

  /**
   * PHP filename extensions as PHPLOC expects them.
   *
   * @see \Acquia\Orca\Domain\Tool\Coverage\CodeCoverageReportBuilder::PHP_NAME_PATTERNS
   */
  private const PHP_EXTENSIONS = [
    '.php',
    '.module',
    '.theme',
    '.inc',
    '.install',
    '.profile',
    '.engine',
  ];

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
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
    $args = [
      'phploc',
      '--exclude=tests',
      '--exclude=var',
      '--exclude=vendor',
      '--exclude=docroot',
      "--log-json={$this->orca->getPath(self::JSON_LOG_PATH)}",
    ];
    foreach (self::PHP_EXTENSIONS as $extension) {
      $args[] = '--suffix=' . $extension;
    }
    $args[] = '.';
    $this->processRunner->runOrcaVendorBin($args, $path, TRUE);
  }

}
