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

  public function testCommand() {
    $this->tester
      ->test()
      ->shouldBeCalledTimes(1);
    $this->facade
      ->getTester()
      ->shouldBeCalledTimes(1);
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, []);

    $this->assertEquals('', $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCodes::OK, $tester->getStatusCode(), 'Returned correct status code.');
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Tests\Tester $tester */
    $tester = $this->tester->reveal();
    $this->facade->getTester()
      ->willReturn($tester);
    /** @var \Acquia\Orca\Fixture\Facade $facade */
    $facade = $this->facade->reveal();
    $application->add(new RunCommand($facade));
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
