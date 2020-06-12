<?php

namespace Acquia\Orca\Tests\Facade;

use Acquia\Orca\Facade\GitFacade;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class GitFacadeTest extends TestCase {

  protected function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
  }

  public function createGitFacade(): GitFacade {
    $process_runner = $this->processRunner->reveal();
    return new GitFacade($process_runner);
  }

  public function testInstantiation() {
    $object = $this->createGitFacade();

    $this->assertInstanceOf(GitFacade::class, $object);
  }

}
