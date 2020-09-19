<?php

namespace Acquia\Orca\Tests\Console\Command\Qa;

use Acquia\Orca\Console\Command\Qa\QaDeprecatedCodeScanCommand;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Domain\Tool\Phpstan\PhpstanTask;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Tool\Phpstan\PhpstanTask $phpstan
 */
class QaDeprecatedCodeScanCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->phpstan = $this->prophesize(PhpstanTask::class);
  }

  protected function createCommand(): Command {
    $fixture = $this->fixture->reveal();
    $package_manager = $this->packageManager->reveal();
    $phpstan = $this->phpstan->reveal();
    return new QaDeprecatedCodeScanCommand($fixture, $package_manager, $phpstan);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $status_code, $display): void {
    $this->packageManager
      ->exists(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('PackageManager::exists', $methods_called))
      ->willReturn(@$args['--sut'] === self::VALID_PACKAGE);
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes((int) in_array('Fixture::exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->phpstan
      ->setSut(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('setSut', $methods_called));
    $this->phpstan
      ->setScanContrib(@$args['--contrib'])
      ->shouldBeCalledTimes((int) in_array('setScanContrib', $methods_called));
    $this->phpstan
      ->execute()
      ->shouldBeCalledTimes((int) in_array('execute', $methods_called))
      ->willReturn($status_code);

    $this->executeCommand($args);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [TRUE, [], [], StatusCodeEnum::ERROR, "Error: Nothing to do.\nHint: Use the \"--sut\" and \"--contrib\" options to specify what to scan.\n"],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists'], StatusCodeEnum::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], StatusCodeEnum::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [TRUE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'setSut', 'execute'], StatusCodeEnum::OK, ''],
      [TRUE, ['--contrib' => TRUE], ['Fixture::exists', 'setScanContrib', 'execute'], StatusCodeEnum::OK, ''],
      [TRUE, ['--sut' => self::VALID_PACKAGE, '--contrib' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'setSut', 'setScanContrib', 'execute'], StatusCodeEnum::OK, ''],
    ];
  }

}
