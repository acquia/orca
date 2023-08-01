<?php

namespace Acquia\Orca\Tests\Domain\Git;

use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @coversDefaultClass \Acquia\Orca\Domain\Git\GitFacade
 */
class GitFacadeTest extends TestCase {

  private const FRESH_FIXTURE_TAG = 'fresh-fixture';

  protected ProcessRunner|ObjectProphecy $processRunner;

  protected function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runExecutable('git', Argument::any(), Argument::any())
      ->willReturn(0);
  }

  private function createGit(): GitFacade {
    $process_runner = $this->processRunner->reveal();
    return new GitFacade($process_runner);
  }

  /**
   * @dataProvider providerExecute
   */
  public function testExecute($args, $cwd, $status) {
    $git = $this->createGit();
    $this->processRunner
      ->runExecutable('git', $args, $cwd)
      ->willReturn($status)
      ->shouldBeCalledOnce();

    $return = $git->execute($args, $cwd);

    self::assertSame($status, $return, 'Returned correct status code.');
  }

  public static function providerExecute(): array {
    return [
      ['args' => [], 'cwd' => NULL, 'status' => 0],
      ['args' => ['commit'], 'cwd' => '/var/www', 'status' => 1],
    ];
  }

  public function testBackupCodebaseState(): void {
    // Ensure fixture repo.
    $this->processRunner
      ->runExecutable('git', ['init'], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', ['config', 'user.name', 'ORCA'], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', ['config', 'user.email', 'no-reply@acquia.com'], NULL)
      ->shouldBeCalledOnce();
    // Perform backup operation itself.
    $this->processRunner
      ->runExecutable('git', ['add', '--all'], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', [
        'commit',
        "--message=Backed up the fixture.",
        '--quiet',
        '--allow-empty',
      ], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', [
        'tag',
        '--force',
        GitFacade::FRESH_FIXTURE_TAG,
      ], NULL)
      ->shouldBeCalledOnce();
    $git = $this->createGit();

    $git->backupFixtureRepo();
  }

  /**
   * @dataProvider providerCommitCodeChanges
   */
  public function testCommitCodeChanges($message): void {
    $this->processRunner
      ->runExecutable('git', ['add', '--all'], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', [
        'commit',
        "--message={$message}",
        '--quiet',
        '--allow-empty',
      ], NULL)
      ->shouldBeCalledOnce();
    $git = $this->createGit();

    $git->commitCodeChanges($message);
  }

  public static function providerCommitCodeChanges(): array {
    return [
      ['Lorem'],
      ['Ipsum'],
    ];
  }

  public function testEnsureFixtureRepo(): void {
    $this->processRunner
      ->runExecutable('git', ['init'], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', ['config', 'user.name', 'ORCA'], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', ['config', 'user.email', 'no-reply@acquia.com'], NULL)
      ->shouldBeCalledOnce();

    $git = $this->createGit();

    $git->ensureFixtureRepo();
  }

  public function testResetRepoState(): void {
    $this->processRunner
      ->runExecutable('git', [
        'checkout',
        '--force',
        self::FRESH_FIXTURE_TAG,
      ], NULL)
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('git', ['clean', '--force', '-d'], NULL)
      ->shouldBeCalledOnce();

    $git = $this->createGit();

    $git->resetRepoState();
  }

}
