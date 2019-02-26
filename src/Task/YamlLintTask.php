<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Exception\TaskFailureException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Lints PHP files.
 */
class YamlLintTask extends TaskBase {

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
    $yaml_files = $this->getYamlFiles();
    if (!$yaml_files) {
      return;
    }
    try {
      $process = new Process(array_merge([
        "{$this->projectDir}/bin/orca",
        'lint:yaml',
      ], $yaml_files));
      $this->processRunner->run($process);
    }
    catch (ProcessFailedException $e) {
      throw new TaskFailureException();
    }
  }

  /**
   * Finds all YAML files.
   *
   * @return string[]
   *   An indexed array of YAML file paths.
   */
  private function getYamlFiles() {
    $files = [];
    $iterator = (new Finder())
      ->files()
      ->followLinks()
      ->in($this->getPath())
      ->notPath('vendor')
      ->name(['*.yml', '*yaml']);
    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($iterator as $file) {
      $files[] = $file->getPathname();
    }
    return $files;
  }

}
