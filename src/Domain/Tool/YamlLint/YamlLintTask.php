<?php

namespace Acquia\Orca\Domain\Tool\YamlLint;

use Acquia\Orca\Domain\Tool\TaskBase;
use Acquia\Orca\Exception\OrcaTaskFailureException;
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
    $yaml_files = $this->getYamlFiles();
    if (!$yaml_files) {
      return;
    }
    try {
      $process = new Process(array_merge([
        $this->orca->getPath('bin/orca'),
        'internal:lint-yaml',
      ], $yaml_files));
      $this->processRunner->run($process);
    }
    catch (ProcessFailedException $e) {
      throw new OrcaTaskFailureException();
    }
  }

  /**
   * Finds all YAML files.
   *
   * @return string[]
   *   An indexed array of YAML file paths.
   */
  private function getYamlFiles(): array {
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
