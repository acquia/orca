<?php

namespace Acquia\Orca\Tests\Command\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Tests\RunCommand;
use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\Tests\Tester;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy $facade
 * @property \Prophecy\Prophecy\ObjectProphecy $tester
 */
class RunCommandTest extends TestCase {

  private const FIXTURE_ROOT = '/var/www/orca-build';

  protected function setUp() {
    $this->tester = $this->prophesize(Tester::class);
    $this->facade = $this->prophesize(Facade::class);
    $this->facade->exists()
      ->willReturn(FALSE);
    $this->facade->rootPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $test_called, $status_code, $display) {
    $this->facade
      ->exists()
      ->shouldBeCalledTimes(1)
      ->willReturn($fixture_exists);
    $this->tester
      ->test()
      ->shouldBeCalledTimes($test_called);
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, []);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, 1, StatusCodes::OK, ''],
      [FALSE, 0, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:create\" command to create one.\n", self::FIXTURE_ROOT)],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Tests\Tester $tester */
    $tester = $this->tester->reveal();
    /** @var \Acquia\Orca\Fixture\Facade $facade */
    $facade = $this->facade->reveal();
    $application->add(new RunCommand($facade, $tester, '/var/www/orca-build'));
    /** @var \Acquia\Orca\Command\Tests\RunCommand $command */
    $command = $application->find(RunCommand::getDefaultName());
    $this->assertInstanceOf(RunCommand::class, $command);
    return new CommandTester($command);
  }

  private function executeCommand(CommandTester $tester, array $args = []) {
    $args = array_merge(['command' => RunCommand::getDefaultName()], $args);
    $tester->execute($args);
  }

}
