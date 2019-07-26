<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureEnableExtensionsCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\AcquiaExtensionEnabler;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

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

  protected function createCommand(): Command {
    /** @var \Acquia\Orca\Fixture\AcquiaExtensionEnabler $acquia_extension_enabler */
    $acquia_extension_enabler = $this->acquiaModuleEnabler->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    return new FixtureEnableExtensionsCommand($acquia_extension_enabler, $fixture);
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
        ->willThrow($exception);
    }

    $this->executeCommand();

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, 0, FALSE, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, 1, new OrcaException('Oops.'), StatusCodes::ERROR, "\n [ERROR] Oops.                                                                  \n\n"],
      [TRUE, 1, FALSE, StatusCodes::OK, ''],
    ];
  }

}
