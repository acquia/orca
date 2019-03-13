<?php

namespace Acquia\Orca\Task\StaticAnalysisTool;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Task\TaskBase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Validates composer.json files.
 */
class ComposerValidateTask extends TaskBase {

  /**
   * The path to the composer.json being validated.
   *
   * @var string|null
   */
  private $composerJson;

  /**
   * Whether or not there have been any failures.
   *
   * @var bool
   */
  private $failures = FALSE;

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
    /** @var \Symfony\Component\Finder\SplFileInfo $info_file */
    foreach ($this->getModuleInfoFiles() as $info_file) {
      try {
        $this->getComposerJsonFrom($info_file);
        $this->validate();
        $this->normalize();
      }
      catch (OrcaException $e) {
        continue;
      }
    }
    if ($this->failures) {
      throw new TaskFailureException();
    }
  }

  /**
   * Finds all module info files.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder query for all module info files.
   */
  private function getModuleInfoFiles() {
    return (new Finder())
      ->files()
      ->followLinks()
      ->in($this->getPath())
      ->notPath(['tests', 'vendor'])
      ->name('*.info.yml');
  }

  /**
   * Gets the composer.json file corresponding to the given module info file.
   *
   * @param \Symfony\Component\Finder\SplFileInfo $info_file
   *   A module info file.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If there is no corresponding composer.json file.
   */
  private function getComposerJsonFrom(SplFileInfo $info_file) {
    $this->composerJson = str_replace($info_file->getFilename(), 'composer.json', $info_file->getPathname());
    if (!$this->filesystem->exists($this->composerJson)) {
      $this->failures = TRUE;
      $this->output->error("Missing required {$this->composerJson}.");
      throw new OrcaException();
    }
  }

  /**
   * Validates the composer.json file.
   */
  private function validate(): void {
    try {
      $this->processRunner->runOrcaVendorBin([
        'composer',
        '--ansi',
        'validate',
        $this->composerJson,
      ]);
    }
    catch (ProcessFailedException $e) {
      $this->failures = TRUE;
    }
  }

  /**
   * Tests whether the composer.json file is normalized.
   */
  private function normalize(): void {
    try {
      $this->processRunner->runOrcaVendorBin([
        'composer',
        '--ansi',
        'normalize',
        '--dry-run',
        realpath($this->composerJson),
      ], $this->projectDir);
    }
    catch (ProcessFailedException $e) {
      $this->failures = TRUE;
    }
  }

}
