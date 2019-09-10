<?php

namespace Acquia\Orca\Task\Fixer;

use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Task\TaskBase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Automatically fixes coding standards violations.
 */
class PhpCodeBeautifierAndFixerTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHP Code Beautifier and Fixer (PHPCBF)';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Fixing coding standards violations';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $this->runPhpcbf();
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

  /**
   * Runs phpcbf.
   */
  public function runPhpcbf(): void {
    $this->processRunner->runOrcaVendorBin([
      'phpcbf',
      realpath($this->getPath()),
    ], "{$this->projectDir}/resources");
  }

}
