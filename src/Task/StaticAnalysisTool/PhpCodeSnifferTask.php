<?php

namespace Acquia\Orca\Task\StaticAnalysisTool;

use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Task\TaskBase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Sniffs for coding standards violations.
 */
class PhpCodeSnifferTask extends TaskBase {

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
  public function runPhpcs(): void {
    $this->processRunner->runOrcaVendorBin([
      'phpcs',
      '-s',
      realpath($this->getPath()),
    ], __DIR__);
  }

}
