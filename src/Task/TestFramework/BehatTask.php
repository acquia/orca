<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Task\TaskBase;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs Behat tests.
 */
class BehatTask extends TaskBase implements TestFrameworkInterface {

  use SutSettingsTrait;

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Running Behat tests';
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
          "--tags={$this->getTags()}",
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
      ->in($this->getPath())
      ->notPath('vendor')
      ->name('behat.yml');
  }

  /**
   * Gets the string of tags to pass to Behat.
   *
   * @return string
   */
  private function getTags(): string {
    $tags = ['~@orca_ignore'];
    if ($this->isSutOnly) {
      $tags[] = '@orca_public';
    }
    return implode(',', $tags);
  }

}
