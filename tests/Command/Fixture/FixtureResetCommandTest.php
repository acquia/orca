<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureResetCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureResetter;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureResetter $fixtureResetter
 */
class FixtureResetCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixtureResetter = $this->prophesize(FixtureResetter::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  protected function createCommand(): Command {
    /** @var \Acquia\Orca\Fixture\FixtureResetter $fixture_resetter */
    $fixture_resetter = $this->fixtureResetter->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    return new FixtureResetCommand($fixture, $fixture_resetter);
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

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, [], [], 0, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, StatusCodes::USER_CANCEL, 'Are you sure you want to reset the test fixture at /var/www/orca-build? '],
      [TRUE, [], ['y'], 1, StatusCodes::OK, 'Are you sure you want to reset the test fixture at /var/www/orca-build? '],
      [TRUE, ['-n' => TRUE], [], 0, StatusCodes::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, StatusCodes::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, StatusCodes::OK, ''],
    ];
  }

}
