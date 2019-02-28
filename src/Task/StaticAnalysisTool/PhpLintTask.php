<?php

namespace Acquia\Orca\Task\StaticAnalysisTool;

use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Task\TaskBase;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Lints PHP files.
 */
class PhpLintTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Linting PHP files';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $this->processRunner->runOrcaVendorBin([
        'parallel-lint',
        '-e',
        'inc,install,module,php,profile,test,theme',
        '--exclude',
        'vendor',
        '--colors',
        '--blame',
        $this->getPath(),
      ]);
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

}
