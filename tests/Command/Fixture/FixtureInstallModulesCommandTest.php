<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureInstallModulesCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Fixture\FixtureRmCommand;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\AcquiaModuleInstaller;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|AcquiaModuleInstaller $acquiaModuleInstaller
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 */
class FixtureInstallModulesCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->acquiaModuleInstaller = $this->prophesize(AcquiaModuleInstaller::class);
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
    $this->acquiaModuleInstaller
      ->install()
      ->shouldBeCalledTimes($install_called);
    if ($exception) {
      $this->acquiaModuleInstaller
        ->install()
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
    /** @var \Acquia\Orca\Fixture\AcquiaModuleInstaller $acquia_module_installer */
    $acquia_module_installer = $this->acquiaModuleInstaller->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    $application->add(new FixtureInstallModulesCommand($acquia_module_installer, $fixture));
    /** @var \Acquia\Orca\Command\Fixture\FixtureInstallModulesCommand $command */
    $command = $application->find(FixtureInstallModulesCommand::getDefaultName());
    $this->assertInstanceOf(FixtureInstallModulesCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
