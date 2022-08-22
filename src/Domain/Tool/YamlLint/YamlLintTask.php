<?php

namespace Acquia\Orca\Domain\Tool\YamlLint;

use Acquia\Orca\Domain\Tool\TaskBase;
use Acquia\Orca\Exception\OrcaTaskFailureException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Lints PHP files.
 */
class YamlLintTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'YAML Lint';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Linting YAML files';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $process = new Process(array_merge([
        $this->orca->getPath('bin/orca'),
        'internal:lint-yaml',
      ], [$this->getPath()]));
      $this->processRunner->run($process);
    }
    catch (ProcessFailedException $e) {
      throw new OrcaTaskFailureException();
    }
  }

}
