<?php

namespace Acquia\Orca\Task\StaticAnalysisTool;

use Acquia\Orca\Task\TaskBase;

/**
 * Measures the size of a PHP project.
 */
class PhpLocTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Measuring the size of the PHP project';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->processRunner->runOrcaVendorBin([
      'phploc',
      '--exclude=var',
      '--exclude=vendor',
      '--exclude=docroot',
      "--log-json={$this->getJsonLogPath()}",
      '.',
    ], $this->getPath());
  }

  /**
   * Gets the path to the JSON log file.
   *
   * @return string
   *   The path.
   */
  public function getJsonLogPath(): string {
    return "{$this->projectDir}/var/phploc.json";
  }

}
