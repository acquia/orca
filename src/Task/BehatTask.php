<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Exception\TaskFailureException;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs Behat stories.
 */
class BehatTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Running Behat stories';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      /** @var \Symfony\Component\Finder\SplFileInfo $config_file */
      foreach ($this->getBehatConfigFiles() as $config_file) {
        $process = $this->processRunner->createOrcaVendorBinProcess([
          'behat',
          '--colors',
          "--config={$config_file->getPathname()}",
          "--tags=orca_public",
        ]);
        $this->processRunner->run($process, $this->fixture->getPath());
      }
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

  /**
   * Finds all Behat config files.
   *
   * @return \Symfony\Component\Finder\Finder
   */
  private function getBehatConfigFiles() {
    return $this->finder
      ->files()
      ->followLinks()
      ->in($this->fixture->getTestsPath())
      ->notPath('vendor')
      ->name('behat.yml');
  }

}
