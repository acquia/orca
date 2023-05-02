<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureInstallSiteCommand;
use Acquia\Orca\Domain\Fixture\FixtureCreator;
use Acquia\Orca\Domain\Fixture\SiteInstaller;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Fixture\SiteInstaller $siteInstaller
 */
class FixtureSiteInstallCommandTest extends CommandTestBase {

  protected ObjectProphecy|FixturePathHandler $fixture;
  protected ObjectProphecy|SiteInstaller $siteInstaller;

  protected function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->siteInstaller = $this->prophesize(SiteInstaller::class);
  }

  protected function createCommand(): Command {
    $fixture = $this->fixture->reveal();
    $site_installer = $this->siteInstaller->reveal();
    return new FixtureInstallSiteCommand($fixture, $site_installer);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $inputs, $install_called, $profile, $status_code, $display): void {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->siteInstaller
      ->install($profile)
      ->shouldBeCalledTimes($install_called);

    $this->executeCommand($args, $inputs);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [FALSE, [], [], 0, FixtureCreator::DEFAULT_PROFILE, StatusCodeEnum::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, FixtureCreator::DEFAULT_PROFILE, StatusCodeEnum::USER_CANCEL, 'Are you sure you want to drop all tables in the database and install a fresh site at /var/www/orca-build? '],
      [TRUE, [], ['y'], 1, FixtureCreator::DEFAULT_PROFILE, StatusCodeEnum::OK, 'Are you sure you want to drop all tables in the database and install a fresh site at /var/www/orca-build? '],
      [TRUE, ['-n' => TRUE], [], 0, FixtureCreator::DEFAULT_PROFILE, StatusCodeEnum::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, FixtureCreator::DEFAULT_PROFILE, StatusCodeEnum::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, FixtureCreator::DEFAULT_PROFILE, StatusCodeEnum::OK, ''],
      [TRUE, ['-f' => TRUE, '--profile' => 'lightning'], [], 1, 'lightning', StatusCodeEnum::OK, ''],
    ];
  }

}
