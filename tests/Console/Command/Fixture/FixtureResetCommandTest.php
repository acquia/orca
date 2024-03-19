<?php

namespace Acquia\Orca\Tests\Console\Command\Fixture;

use Acquia\Orca\Console\Command\Fixture\FixtureResetCommand;
use Acquia\Orca\Domain\Fixture\FixtureResetter;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Fixture\FixtureResetter $fixtureResetter
 */
class FixtureResetCommandTest extends CommandTestBase {

  protected ObjectProphecy|FixturePathHandler $fixture;
  protected ObjectProphecy|FixtureResetter $fixtureResetter;

  protected function setUp(): void {
    $this->fixtureResetter = $this->prophesize(FixtureResetter::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
  }

  protected function createCommand(): Command {
    $fixture_resetter = $this->fixtureResetter->reveal();
    $fixture_path_handler = $this->fixture->reveal();
    return new FixtureResetCommand($fixture_path_handler, $fixture_resetter);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $inputs, $remove_called, $status_code, $display): void {
    $this->fixture
      ->exists()
      ->shouldBeCalled()
      ->willReturn($fixture_exists);
    $this->fixtureResetter
      ->reset()
      ->shouldBeCalledTimes($remove_called);

    $this->executeCommand($args, $inputs);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public static function providerCommand(): array {
    return [
      [FALSE, [], [], 0, StatusCodeEnum::ERROR, sprintf("Error: No fixture exists at %s.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['n'], 0, StatusCodeEnum::USER_CANCEL, 'Are you sure you want to reset the test fixture at /var/www/orca-build? '],
      [TRUE, [], ['y'], 1, StatusCodeEnum::OK, 'Are you sure you want to reset the test fixture at /var/www/orca-build? '],
      [TRUE, ['-n' => TRUE], [], 0, StatusCodeEnum::USER_CANCEL, ''],
      [TRUE, ['-f' => TRUE], [], 1, StatusCodeEnum::OK, ''],
      [TRUE, ['-f' => TRUE, '-n' => TRUE], [], 1, StatusCodeEnum::OK, ''],
    ];
  }

}
