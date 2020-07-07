<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureStatusCommand;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Fixture\FixtureInspector;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureInspector $fixtureInspector
 */
class FixtureStatusCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->fixtureInspector = $this->prophesize(FixtureInspector::class);
  }

  protected function createCommand(): Command {
    /** @var \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler */
    $fixture_path_handler = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\FixtureInspector $fixture_inspector */
    $fixture_inspector = $this->fixtureInspector->reveal();
    return new FixtureStatusCommand($fixture_path_handler, $fixture_inspector);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $get_overview_called, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->fixtureInspector
      ->getOverview()
      ->shouldBeCalledTimes($get_overview_called)
      ->willReturn([
        ['Key one', 'Value one'],
        ['Key two', 'Value two'],
      ]);

    $this->executeCommand();

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, 0, StatusCode::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, 1, StatusCode::OK, "\n Key one : Value one \n Key two : Value two \n\n"],
    ];
  }

}
