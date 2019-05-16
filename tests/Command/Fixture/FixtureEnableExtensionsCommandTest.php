<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureEnableExtensionsCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\AcquiaExtensionEnabler;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|AcquiaExtensionEnabler $acquiaModuleEnabler
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 */
class FixtureEnableExtensionsCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->acquiaModuleEnabler = $this->prophesize(AcquiaExtensionEnabler::class);
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

    $this->executeCommand($tester, FixtureEnableExtensionsCommand::getDefaultName());

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
    /** @var \Acquia\Orca\Fixture\AcquiaExtensionEnabler $acquia_extension_enabler */
    $acquia_extension_enabler = $this->acquiaModuleEnabler->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    $application->add(new FixtureEnableExtensionsCommand($acquia_extension_enabler, $fixture));
    /** @var \Acquia\Orca\Command\Fixture\FixtureEnableExtensionsCommand $command */
    $command = $application->find(FixtureEnableExtensionsCommand::getDefaultName());
    $this->assertInstanceOf(FixtureEnableExtensionsCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
