<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\IoTrait;
use Acquia\Orca\ProcessRunner;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Destroys the fixture.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 * @property \Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Acquia\Orca\ProcessRunner $processRunner
 */
class Destroyer {

  use IoTrait;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Facade $facade
   *   The fixture.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Facade $facade, Filesystem $filesystem, ProcessRunner $process_runner) {
    $this->facade = $facade;
    $this->filesystem = $filesystem;
    $this->processRunner = $process_runner;
  }

  /**
   * Destroys the fixture.
   */
  public function destroy(): void {
    $this->io()->section('Destroying fixture');
    $this->prepareFilesForDeletion();
    $this->deleteFixtureDirectory();
    $this->io()->success('Fixture destroyed');
  }

  /**
   * Prepares files for deletion by setting write permissions.
   *
   * Write permissions on some files are removed by the Drupal installer.
   */
  private function prepareFilesForDeletion(): void {
    $path = $this->facade->docrootPath('sites/default');
    if (!$this->filesystem->exists($path)) {
      return;
    }

    // Filesystem::remove() seems like a better choice than a raw Process, but
    // for reasons unknown, it fails due to file permissions.
    $this->processRunner->runExecutableProcess([
      'chmod',
      '-R',
      'u+w',
      '.',
    ], $path);
  }

  /**
   * Deletes the entire fixture directory.
   */
  private function deleteFixtureDirectory(): void {
    $root_path = $this->facade->rootPath();
    $this->io()->comment(sprintf('Removing %s', $root_path));
    $this->filesystem->remove($root_path);
  }

}
