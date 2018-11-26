<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\RmCommand;
use Acquia\Orca\Fixture\Remover;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Remover $remover
 */
class RmCommandTest extends CommandTestBase {

  private const FIXTURE_ROOT = '/var/www/orca-build';

  protected function setUp() {
    $this->remover = $this->prophesize(Remover::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->rootPath()
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
    $this->remover
      ->remove()
      ->shouldBeCalledTimes($remove_called);
    $tester = $this->createCommandTester();
    $tester->setInputs($inputs);

    $this->executeCommand($tester, RmCommand::getDefaultName(), $args);

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
    /** @var \Acquia\Orca\Fixture\Remover $remover */
    $remover = $this->remover->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    $application->add(new RmCommand($fixture, $remover));
    /** @var \Acquia\Orca\Command\Fixture\RmCommand $command */
    $command = $application->find(RmCommand::getDefaultName());
    $this->assertInstanceOf(RmCommand::class, $command, 'Successfully instantiated class.');
    return new CommandTester($command);
  }

}
