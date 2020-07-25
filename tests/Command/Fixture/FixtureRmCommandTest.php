<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureRmCommand;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureRemover $fixtureRemover
 */
class FixtureRmCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixtureRemover = $this->prophesize(FixtureRemover::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  protected function createCommand(): Command {
    $fixture_remover = $this->fixtureRemover->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    return new FixtureRmCommand($fixture_path_handler, $fixture_remover);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $inputs, $remove_called, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->fixtureRemover
      ->remove()
      ->shouldBeCalledTimes($remove_called);

    $this->executeCommand($args, $inputs);

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, [], [], 0, StatusCode::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, StatusCode::USER_CANCEL, 'Are you sure you want to remove the test fixture at /var/www/orca-build? '],
      [TRUE, [], ['y'], 1, StatusCode::OK, 'Are you sure you want to remove the test fixture at /var/www/orca-build? '],
      [TRUE, ['-n' => TRUE], [], 0, StatusCode::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, StatusCode::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, StatusCode::OK, ''],
    ];
  }

}
