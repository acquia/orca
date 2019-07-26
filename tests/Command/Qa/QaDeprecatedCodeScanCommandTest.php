<?php

namespace Acquia\Orca\Tests\Command\Qa;

use Acquia\Orca\Command\Qa\QaDeprecatedCodeScanCommand;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Task\DeprecatedCodeScanner\PhpStanTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\DeprecatedCodeScanner\PhpStanTask $phpstan
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 */
class QaDeprecatedCodeScanCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->phpstan = $this->prophesize(PhpStanTask::class);
    $this->taskRunner = $this->prophesize(TaskRunner::class);
  }

  protected function createCommand(): Command {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    /** @var \Acquia\Orca\Task\DeprecatedCodeScanner\PhpStanTask $phpstan */
    $phpstan = $this->phpstan->reveal();
    /** @var \Acquia\Orca\Task\TaskRunner $task_runner */
    $task_runner = $this->taskRunner->reveal();
    return new QaDeprecatedCodeScanCommand($fixture, $package_manager, $phpstan, $task_runner);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $status_code, $display) {
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

    $this->assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, [], [], StatusCodes::ERROR, "Error: Nothing to do.\nHint: Use the \"--sut\" and \"--contrib\" options to specify what to scan.\n"],
      [FALSE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists'], StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [TRUE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'setSut', 'execute'], StatusCodes::OK, ''],
      [TRUE, ['--contrib' => TRUE], ['Fixture::exists', 'setScanContrib', 'execute'], StatusCodes::OK, ''],
      [TRUE, ['--sut' => self::VALID_PACKAGE, '--contrib' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'setSut', 'setScanContrib', 'execute'], StatusCodes::OK, ''],
    ];
  }

}
