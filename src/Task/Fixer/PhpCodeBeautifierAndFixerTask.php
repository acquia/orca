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
  public function statusMessage(): string {
    return 'Fixing coding standards violations';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->overrideConfig();
    try {
      $this->runPhpcbf();
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
    finally {
      $this->restoreConfig();
    }
  }

  /**
   * Runs phpcbf.
   */
  public function runPhpcbf(): void {
    $this->processRunner->runOrcaVendorBin(['phpcbf'], $this->getPath());
  }

  /**
   * Overrides the active configuration.
   */
  public function overrideConfig(): void {
    $this->configFileOverrider->setPaths(
      "{$this->projectDir}/phpcs.xml.dist",
      "{$this->getPath()}/phpcs.xml"
    );
    $this->configFileOverrider->override();
  }

  /**
   * Restores the previous configuration.
   */
  public function restoreConfig(): void {
    $this->configFileOverrider->restore();
  }

}
