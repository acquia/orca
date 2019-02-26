<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Exception\TaskFailureException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Validates composer.json files.
 */
class ComposerValidateTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Validating composer.json files';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      /** @var \Symfony\Component\Finder\SplFileInfo $composer_json */
      foreach ($this->getComposerJsonFiles() as $composer_json) {
        $this->processRunner->runOrcaVendorBin([
          'composer',
          '--ansi',
          'validate',
          $composer_json->getPathname(),
        ]);
      }
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

  /**
   * Finds all composer.json files.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder query for all composer.json files.
   */
  private function getComposerJsonFiles() {
    return (new Finder())
      ->files()
      ->followLinks()
      ->in($this->getPath())
      ->notPath('vendor')
      ->name('composer.json');
  }

}
