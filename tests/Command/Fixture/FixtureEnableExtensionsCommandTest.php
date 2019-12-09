<?php

namespace Acquia\Orca\Tests\Command\Fixture;

use Acquia\Orca\Command\Fixture\FixtureEnableExtensionsCommand;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\CompanyExtensionEnabler;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|CompanyExtensionEnabler $companyExtensionEnabler
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 */
class FixtureEnableExtensionsCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->companyExtensionEnabler = $this->prophesize(CompanyExtensionEnabler::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  protected function createCommand(): Command {
    /** @var \Acquia\Orca\Fixture\CompanyExtensionEnabler $company_extension_enabler */
    $company_extension_enabler = $this->companyExtensionEnabler->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    return new FixtureEnableExtensionsCommand($company_extension_enabler, $fixture);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $install_called, $exception, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->companyExtensionEnabler
      ->enable()
      ->shouldBeCalledTimes($install_called);
    if ($exception) {
      $this->companyExtensionEnabler
        ->enable()
        ->willThrow($exception);
    }

    $this->executeCommand();

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, 0, FALSE, StatusCode::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, 1, new OrcaException('Oops.'), StatusCode::ERROR, "\n [ERROR] Oops.                                                                  \n\n"],
      [TRUE, 1, FALSE, StatusCode::OK, ''],
    ];
  }

}
