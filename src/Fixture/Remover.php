<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\ProcessRunner;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Removes the fixture.
 *
 * @property \Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Symfony\Component\Console\Style\SymfonyStyle $output
 * @property \Acquia\Orca\ProcessRunner $processRunner
 */
class Remover {

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(Filesystem $filesystem, Fixture $fixture, SymfonyStyle $output, ProcessRunner $process_runner) {
    $this->fixture = $fixture;
    $this->filesystem = $filesystem;
    $this->output = $output;
    $this->processRunner = $process_runner;
  }

  /**
   * Removes the fixture.
   */
  public function remove(): void {
    $this->output->section('Removing fixture');
    $this->prepareFilesForDeletion();
    $this->deleteFixtureDirectory();
    $this->output->success('Fixture removed');
  }

  /**
   * Prepares files for deletion by setting write permissions.
   *
   * Write permissions on some files are removed by the Drupal installer.
   */
  private function prepareFilesForDeletion(): void {
    $path = $this->fixture->docrootPath('sites/default');
    if (!$this->filesystem->exists($path)) {
      return;
    }

    // Filesystem::remove() seems like a better choice than a raw Process, but
    // for reasons unknown, it fails due to file permissions.
    $process = $this->processRunner->createExecutableProcess([
      'chmod',
      '-R',
      'u+w',
      '.',
    ]);
    $this->processRunner->run($process, $path);
  }

  /**
   * Deletes the entire fixture directory.
   */
  private function deleteFixtureDirectory(): void {
    $root_path = $this->fixture->rootPath();
    $this->output->comment("Removing {$root_path}");
    $this->filesystem->remove($root_path);
  }

}
