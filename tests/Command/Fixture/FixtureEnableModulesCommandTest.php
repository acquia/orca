<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureEnableModulesCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\FixtureRmCommand;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\AcquiaModuleEnabler;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|AcquiaModuleEnabler $acquiaModuleEnabler
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 */
class FixtureEnableModulesCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->acquiaModuleEnabler = $this->prophesize(AcquiaModuleEnabler::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $install_called, $exception, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->acquiaModuleEnabler
      ->enable()
      ->shouldBeCalledTimes($install_called);
    if ($exception) {
      $this->acquiaModuleEnabler
        ->enable()
        ->willThrow(OrcaException::class);
    }
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, FixtureRmCommand::getDefaultName());

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, 0, FALSE, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, 1, TRUE, StatusCodes::ERROR, ''],
      [TRUE, 1, FALSE, StatusCodes::OK, ''],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\AcquiaModuleEnabler $acquia_module_enabler */
    $acquia_module_enabler = $this->acquiaModuleEnabler->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    $application->add(new FixtureEnableModulesCommand($acquia_module_enabler, $fixture));
    /** @var \Acquia\Orca\Command\Fixture\FixtureEnableModulesCommand $command */
    $command = $application->find(FixtureEnableModulesCommand::getDefaultName());
    $this->assertInstanceOf(FixtureEnableModulesCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
