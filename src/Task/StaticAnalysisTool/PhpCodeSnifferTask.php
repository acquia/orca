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
    $this->overrideConfig();
    try {
      $this->runPhpcs();
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
    finally {
      $this->restoreConfig();
    }
  }

  /**
   * Runs phpcs.
   */
  public function runPhpcs(): void {
    $this->processRunner->runOrcaVendorBin(['phpcs', '-s'], $this->getPath());
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
