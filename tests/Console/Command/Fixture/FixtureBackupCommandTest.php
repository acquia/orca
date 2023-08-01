<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureBackupCommand;
use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Orca\Domain\Git\GitFacade|\Prophecy\Prophecy\ObjectProphecy $git
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 */
class FixtureBackupCommandTest extends CommandTestBase {

  protected GitFacade|ObjectProphecy $git;
  protected FixturePathHandler|ObjectProphecy $fixture;

  protected function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->git = $this->prophesize(GitFacade::class);
  }

  protected function createCommand(): Command {
    $fixture = $this->fixture->reveal();
    $git = $this->git->reveal();
    return new FixtureBackupCommand($fixture, $git);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $inputs, $remove_called, $status_code, $display): void {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->git
      ->backupFixtureRepo()
      ->shouldBeCalledTimes($remove_called);

    $this->executeCommand($args, $inputs);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public static function providerCommand(): array {
    return [
      [FALSE, [], [], 0, StatusCodeEnum::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, StatusCodeEnum::USER_CANCEL, 'Are you sure you want to overwrite the backup of the test fixture at /var/www/orca-build? '],
      [TRUE, [], ['y'], 1, StatusCodeEnum::OK, 'Are you sure you want to overwrite the backup of the test fixture at /var/www/orca-build? '],
      [TRUE, ['-n' => TRUE], [], 0, StatusCodeEnum::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, StatusCodeEnum::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, StatusCodeEnum::OK, ''],
    ];
  }

}
