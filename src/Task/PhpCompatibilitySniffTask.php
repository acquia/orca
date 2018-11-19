<?php

namespace Acquia\Orca\Task;

use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Sniffs for PHP version compatibility.
 */
class PhpCompatibilitySniffTask extends TaskBase {

  private const TEST_VERSION = '7.1-';

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $this->processRunner->runVendorBinProcess([
        'phpcs',
        '-p',
        '--colors',
        '--config-set',
        'testVersion',
        self::TEST_VERSION,
        '--standard=PHPCompatibility',
        '--parallel=10',
        '--extensions=inc,install,module,php,profile,test,theme',
        $this->getPath(),
      ]);
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

}
