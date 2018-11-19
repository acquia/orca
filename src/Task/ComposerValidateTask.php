<?php

namespace Acquia\Orca\Task;

use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Validates composer.json files.
 */
class ComposerValidateTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      /** @var \Symfony\Component\Finder\SplFileInfo $composer_json */
      foreach ($this->getComposerJsonFiles() as $composer_json) {
        $this->processRunner->runExecutableProcess([
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
   */
  private function getComposerJsonFiles() {
    return $this->finder
      ->files()
      ->followLinks()
      ->in($this->getPath())
      ->notPath('vendor')
      ->name('composer.json');
  }

}
