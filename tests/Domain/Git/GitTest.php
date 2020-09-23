<?php

namespace Acquia\Orca\Tests\Domain\Git;

use Acquia\Orca\Domain\Git\Git;
use Acquia\Orca\Helper\Process\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Domain\Git\Git
 */
class GitTest extends TestCase {

  private const FRESH_FIXTURE_TAG = 'fresh-fixture';

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

  public function testBackupCodebaseState(): void {
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

    $git->backupFixtureRepo();
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

  public function testResetRepoState(): void {
    $this->processRunner
      ->git([
        'checkout',
        '--force',
        self::FRESH_FIXTURE_TAG,
      ])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->git(['clean', '--force', '-d'])
      ->shouldBeCalledOnce();

    $git = $this->createGit();

    $git->resetRepoState();
  }

}
