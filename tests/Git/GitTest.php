<?php

namespace Acquia\Orca\Tests\Git;

use Acquia\Orca\Git\Git;
use Acquia\Orca\Helper\Process\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Git\Git
 */
class GitTest extends TestCase {

  protected function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->git(Argument::any())
      ->willReturn(0);
  }

  private function createGit(): Git {
    $process_runner = $this->processRunner->reveal();
    return new Git($process_runner);
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

    $git = $this->createGit();

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
        Git::FRESH_FIXTURE_TAG,
      ])->shouldBeCalledOnce();

    $git = $this->createGit();

    $git->backupFixtureState();
  }

}
