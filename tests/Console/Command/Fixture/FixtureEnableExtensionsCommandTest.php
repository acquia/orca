<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureEnableExtensionsCommand;
use Acquia\Orca\Domain\Fixture\CompanyExtensionEnabler;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|CompanyExtensionEnabler $companyExtensionEnabler
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
 */
class FixtureEnableExtensionsCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $this->companyExtensionEnabler = $this->prophesize(CompanyExtensionEnabler::class);
    $this->fixture_path_handler = $this->prophesize(FixturePathHandler::class);
    $this->fixture_path_handler->exists()
      ->willReturn(TRUE);
    $this->fixture_path_handler->getPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  protected function createCommand(): Command {
    $company_extension_enabler = $this->companyExtensionEnabler->reveal();
    $fixture = $this->fixture_path_handler->reveal();
    return new FixtureEnableExtensionsCommand($company_extension_enabler, $fixture);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $install_called, $exception, $status_code, $display): void {
    $this->fixture_path_handler
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

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [FALSE, 0, FALSE, StatusCodeEnum::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, 1, new OrcaException('Oops.'), StatusCodeEnum::ERROR, "\n [ERROR] Oops.                                                                  \n\n"],
      [TRUE, 1, FALSE, StatusCodeEnum::OK, ''],
    ];
  }

}
