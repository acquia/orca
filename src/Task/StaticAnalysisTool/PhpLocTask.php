<?php

namespace Acquia\Orca\Task\StaticAnalysisTool;

use Acquia\Orca\Task\TaskBase;

/**
 * Measures the size of a PHP project.
 */
class PhpLocTask extends TaskBase {

  public const JSON_LOG_PATH = 'var/phploc.json';

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
      sprintf('--log-json=%s/%s', $this->projectDir, self::JSON_LOG_PATH),
      '.',
    ], $this->getPath());
  }

}
