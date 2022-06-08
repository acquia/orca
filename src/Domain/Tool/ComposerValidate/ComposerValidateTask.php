<?php

namespace Acquia\Orca\Domain\Tool\ComposerValidate;

use Acquia\Orca\Domain\Tool\TaskBase;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\OrcaTaskFailureException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Validates composer.json files.
 */
class ComposerValidateTask extends TaskBase {

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
    return 'Composer Validate';
  }

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
    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($this->getFiles() as $file) {
      try {
        if ($file->getExtension() === 'yml') {
          $this->checkForMissingComposerJson($file);
          continue;
        }
        $path = realpath($file->getPathname());
        $this->validate($path);
        $this->normalize($path);
      }
      catch (OrcaException $e) {
        continue;
      }
    }
    if ($this->failures) {
      throw new OrcaTaskFailureException();
    }
  }

  /**
   * Finds all module info files.
   *
   * @return \Symfony\Component\Finder\Finder
   *   A Finder query for all module info files.
   */
  private function getFiles(): Finder {
    return (new Finder())->files()
      ->followLinks()
      ->in($this->getPath())
      ->notPath(['tests', 'vendor'])
      ->name(['composer.json', '*.info.yml']);
  }

  /**
   * Gets the composer.json file corresponding to the given info file.
   *
   * @param \Symfony\Component\Finder\SplFileInfo $info_file
   *   An info file.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If there is no corresponding composer.json file.
   */
  private function checkForMissingComposerJson(SplFileInfo $info_file): void {
    $composer_json =
      str_replace($info_file->getFilename(), 'composer.json', $info_file->getPathname());
    if (!$this->filesystem->exists($composer_json)) {
      $this->failures = TRUE;
      $this->output->error("Missing required {$composer_json}.");
      throw new OrcaException();
    }
  }

  /**
   * Validates the composer.json file.
   *
   * @param string $path
   *   The absolute path to the file.
   */
  private function validate(string $path): void {
    try {
      $this->composerFacade->validate($path);
    }
    catch (ProcessFailedException $e) {
      $this->failures = TRUE;
    }
  }

  /**
   * Tests whether the composer.json file is normalized.
   *
   * @param string $path
   *   The absolute path to the file.
   */
  private function normalize(string $path): void {
    try {
      $this->processRunner->runOrcaVendorBin([
        'composer',
        '--ansi',
        'normalize',
        '--dry-run',
        '--indent-size=4',
        '--indent-style=space',
        $path,
        // The cwd must be the ORCA project directory in order for Composer to
        // find the "normalize" command.
      ], $this->orca->getPath());
    }
    catch (ProcessFailedException $e) {
      $this->failures = TRUE;
    }
  }

}
