<?php

namespace Acquia\Orca\Tests\Facade;

use Acquia\Orca\Facade\GitFacade;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy
 *   $processRunner
 */
class GitFacadeTest extends TestCase {

  protected function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->git(Argument::any())
      ->willReturn(0);
  }

  public function testEnsureFixtureRepo(): void {
    $this->processRunner
      ->git(['init'])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->git(['config', 'user.name', 'ORCA'])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->git(['config', 'user.email', 'no-reply@acquia.com'])
      ->shouldBeCalledOnce();

    $git = $this->createGitFacade();

    $git->ensureFixtureRepo();
  }

  public function testBackupFixtureState(): void {
    // Ensure fixture repo.
    $this->processRunner
      ->git(['init'])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->git(['config', 'user.name', 'ORCA'])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->git(['config', 'user.email', 'no-reply@acquia.com'])
      ->shouldBeCalledOnce();
    // Perform backup operation itself.
    $this->processRunner
      ->git(['add', '--all'])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->gitCommit('Backed up the fixture.')
      ->shouldBeCalledOnce();
    $this->processRunner
      ->git([
        'tag',
        '--force',
        GitFacade::FRESH_FIXTURE_TAG,
      ])->shouldBeCalledOnce();

    $git = $this->createGitFacade();

    $git->backupFixtureState();
  }

  public function createGitFacade(): GitFacade {
    $process_runner = $this->processRunner->reveal();
    return new GitFacade($process_runner);
  }

}
