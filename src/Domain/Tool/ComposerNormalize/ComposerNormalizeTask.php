<?php

namespace Acquia\Orca\Domain\Tool\ComposerNormalize;

use Acquia\Orca\Domain\Tool\TaskBase;
use Acquia\Orca\Exception\OrcaTaskFailureException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Normalizes composer.json files.
 */
class ComposerNormalizeTask extends TaskBase {

  /**
   * Whether or not there have been any failures.
   *
   * @var bool
   */
  private $failures = FALSE;

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Composer Normalize';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Normalizing composer.json files';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($this->getFiles() as $file) {
      $path = realpath($file->getPathname());
      $this->normalize($path);
    }
    if ($this->failures) {
      throw new OrcaTaskFailureException();
    }
  }

  /**
   * Finds all composer.json files.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder query for all module info files.
   */
  private function getFiles(): Finder {
    return (new Finder())
      ->files()
      ->followLinks()
      ->in($this->getPath())
      ->notPath(['tests', 'vendor'])
      ->name(['composer.json']);
  }

  /**
   * Normalizes the composer.json file.
   *
   * @param string $path
   *   The absolute path to the file.
   */
  private function normalize(string $path): void {
    try {
      $this->composerFacade->normalize($path);
    }
    catch (ProcessFailedException $e) {
      $this->failures = TRUE;
    }
  }

}
