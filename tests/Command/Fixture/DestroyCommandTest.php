<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\DestroyCommand;
use Acquia\Orca\Fixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy $fixture
 */
class DestroyCommandTest extends TestCase {

  private const FIXTURE_ROOT = '/var/www/orca-build';

  protected function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->rootPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($exists, $args, $inputs, $destroy_called, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($exists);
    $this->fixture
      ->destroy()
      ->shouldBeCalledTimes($destroy_called);
    $tester = $this->createCommandTester();
    $tester->setInputs($inputs);

    $this->executeCommand($tester, $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, [], [], 0, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, StatusCodes::USER_CANCEL, 'Are you sure you want to destroy the test fixture? '],
      [TRUE, [], ['y'], 1, StatusCodes::OK, 'Are you sure you want to destroy the test fixture? '],
      [TRUE, ['-n' => TRUE], [], 0, StatusCodes::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, StatusCodes::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, StatusCodes::OK, ''],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    $application->add(new DestroyCommand($fixture));
    /** @var \Acquia\Orca\Command\Fixture\DestroyCommand $command */
    $command = $application->find(DestroyCommand::getDefaultName());
    $this->assertInstanceOf(DestroyCommand::class, $command);
    return new CommandTester($command);
  }

  private function executeCommand(CommandTester $tester, array $args = []) {
    $args = array_merge(['command' => DestroyCommand::getDefaultName()], $args);
    $tester->execute($args);
  }

}
