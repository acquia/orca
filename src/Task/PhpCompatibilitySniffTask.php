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
  public function statusMessage(): string {
    return 'Sniffing for PHP version compatibility';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $process = $this->processRunner->createOrcaVendorBinProcess([
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
      $this->processRunner->run($process);
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

}
