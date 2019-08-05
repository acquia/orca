<?php

namespace Acquia\Orca\Task\StaticAnalysisTool;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Task\TaskBase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Sniffs for coding standards violations.
 */
class PhpCodeSnifferTask extends TaskBase {

  public const JSON_LOG_PATH = 'var/phpcs.json';

  /**
   * The status code.
   *
   * @var int
   */
  private $status = StatusCodes::OK;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHP Code Sniffer (PHPCS)';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Sniffing for coding standards violations';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $this->runPhpcs();
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

  /**
   * Runs phpcs.
   */
  public function runPhpcs(): int {
    try {
      $this->runCommand();
    }
    catch (ProcessFailedException $e) {
      $this->status = StatusCodes::ERROR;
    }
    $this->logResults();
    return $this->status;
  }

  /**
   * Runs phpcs and sends output to the console.
   */
  private function runCommand(): void {
    $this->processRunner->runOrcaVendorBin([
      'phpcs',
      '-s',
      realpath($this->getPath()),
    ], __DIR__);
  }

  /**
   * Runs and logs the output to a file.
   */
  private function logResults(): void {
    $this->output->comment('Logging results...');

    $this->processRunner->runOrcaVendorBin([
      'phpcs',
      '-s',
      '--report=json',
      sprintf('--report-file=%s/%s', $this->projectDir, self::JSON_LOG_PATH),
      realpath($this->getPath()),
    ], __DIR__);
  }

}
