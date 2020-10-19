<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Fixture\FixtureResetter;
use Acquia\Orca\Domain\Git\GitFacade;
use PHPUnit\Framework\TestCase;

class FixtureResetterTest extends TestCase {

  public function testReset(): void {
    $git = $this->prophesize(GitFacade::class);
    $git->resetRepoState()
      ->shouldBeCalledOnce();
    $resetter = new FixtureResetter($git->reveal());

    $resetter->reset();
  }

}
