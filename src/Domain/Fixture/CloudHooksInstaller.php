<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Installs Acquia Cloud Hooks.
 */
class CloudHooksInstaller {

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Domain\Git\GitFacade
   */
  private $git;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Git\GitFacade $git
   *   The Git facade.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, GitFacade $git, ProcessRunner $process_runner) {
    $this->fixture = $fixture_path_handler;
    $this->git = $git;
    $this->processRunner = $process_runner;
  }

  /**
   * Installs Acquia Cloud Hooks.
   *
   * @see https://github.com/acquia/cloud-hooks#installing-cloud-hooks
   */
  public function install(): void {
    $tarball = 'hooks.tar.gz';

    $this->processRunner->runExecutable('curl', [
      '-f',
      '-L',
      '-o',
      $tarball,
      'https://github.com/acquia/cloud-hooks/tarball/master',
    ]);
    $this->processRunner->runExecutable('tar', [
      'xzf',
      $tarball,
    ]);
    $this->processRunner->runExecutable('rm', [
      $tarball,
    ]);
    $mv_command = Process::fromShellCommandline('mv acquia-cloud-hooks-* hooks', $this->fixture->getPath());
    $this->processRunner->run($mv_command);

    $this->git->commitCodeChanges('Installed Cloud Hooks.');
  }

}
