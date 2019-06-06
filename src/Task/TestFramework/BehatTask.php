<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Runs Behat tests.
 */
class BehatTask extends TestFrameworkBase {

  use SutSettingsTrait;

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    $which = ($this->isPublicTestsOnly()) ? 'public' : 'all';
    return "Running {$which} Behat tests";
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      /** @var \Symfony\Component\Finder\SplFileInfo $config_file */
      foreach ($this->getBehatConfigFiles() as $config_file) {
        $this->processRunner->runOrcaVendorBin([
          'behat',
          '-vv',
          '--colors',
          "--config={$config_file->getPathname()}",
          "--tags={$this->getTags()}",
        ], $this->fixture->getPath());
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
   *   A Finder query for all Behat config files.
   */
  private function getBehatConfigFiles() {
    return (new Finder())
      ->files()
      ->followLinks()
      ->in($this->getPath())
      ->depth('== 0')
      ->notPath('vendor')
      ->name('behat.yml');
  }

  /**
   * Gets the string of tags to pass to Behat.
   *
   * @return string
   *   The string of tags to pass to Behat.
   */
  private function getTags(): string {
    $tags = [];
    if ($this->isPublicTestsOnly()) {
      $tags[] = '@orca_public';
    }
    $tags[] = '~@orca_ignore';
    return implode('&&', $tags);
  }

}
