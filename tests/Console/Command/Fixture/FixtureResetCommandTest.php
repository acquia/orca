<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureResetCommand;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Fixture\FixtureResetter;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureResetter $fixtureResetter
 */
class FixtureResetCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixtureResetter = $this->prophesize(FixtureResetter::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  protected function createCommand(): Command {
    $fixture_resetter = $this->fixtureResetter->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    return new FixtureResetCommand($fixture_path_handler, $fixture_resetter);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $inputs, $remove_called, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->fixtureResetter
      ->reset()
      ->shouldBeCalledTimes($remove_called);

    $this->executeCommand($args, $inputs);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, [], [], 0, StatusCode::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, StatusCode::USER_CANCEL, 'Are you sure you want to reset the test fixture at /var/www/orca-build? '],
      [TRUE, [], ['y'], 1, StatusCode::OK, 'Are you sure you want to reset the test fixture at /var/www/orca-build? '],
      [TRUE, ['-n' => TRUE], [], 0, StatusCode::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, StatusCode::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, StatusCode::OK, ''],
    ];
  }

}
