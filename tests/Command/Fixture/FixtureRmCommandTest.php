<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\FixtureRmCommand;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\FixtureRemover $fixtureRemover
 */
class FixtureRmCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixtureRemover = $this->prophesize(FixtureRemover::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
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
    $tester = $this->createCommandTester();
    $tester->setInputs($inputs);

    $this->executeCommand($tester, FixtureRmCommand::getDefaultName(), $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, [], [], 0, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, StatusCodes::USER_CANCEL, 'Are you sure you want to remove the test fixture at /var/www/orca-build? '],
      [TRUE, [], ['y'], 1, StatusCodes::OK, 'Are you sure you want to remove the test fixture at /var/www/orca-build? '],
      [TRUE, ['-n' => TRUE], [], 0, StatusCodes::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, StatusCodes::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, StatusCodes::OK, ''],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\FixtureRemover $fixture_remover */
    $fixture_remover = $this->fixtureRemover->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    $application->add(new FixtureRmCommand($fixture, $fixture_remover));
    /** @var \Acquia\Orca\Command\Fixture\FixtureRmCommand $command */
    $command = $application->find(FixtureRmCommand::getDefaultName());
    $this->assertInstanceOf(FixtureRmCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
