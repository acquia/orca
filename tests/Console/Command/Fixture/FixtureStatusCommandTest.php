<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureStatusCommand;
use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Fixture\FixtureInspector;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
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
    $fixture_path_handler = $this->fixture->reveal();
    $fixture_inspector = $this->fixtureInspector->reveal();
    return new FixtureStatusCommand($fixture_path_handler, $fixture_inspector);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $get_overview_called, $status_code, $display): void {
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

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [FALSE, 0, StatusCode::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, 1, StatusCode::OK, "\n Key one : Value one \n Key two : Value two \n\n"],
    ];
  }

}
