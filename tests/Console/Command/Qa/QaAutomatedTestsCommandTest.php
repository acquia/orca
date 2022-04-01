<?php

namespace Acquia\Orca\Tests\Console\Command\Qa;

use Acquia\Orca\Console\Command\Qa\QaAutomatedTestsCommand;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Domain\Tool\TestRunner;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Server\ChromeDriverServer $chromedriver
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Clock $clock
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Tool\Phpunit\PhpUnitTask $phpunit
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Task\TaskRunner $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Tool\TestRunner $testRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Domain\Server\WebServer $webServer
 */
class QaAutomatedTestsCommandTest extends CommandTestBase {

  protected function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->testRunner = $this->prophesize(TestRunner::class);
    $this->testRunner->run();
  }

  protected function createCommand(): Command {
    $fixture_path_handler = $this->fixture->reveal();
    $package_manager = $this->packageManager->reveal();
    $test_runner = $this->testRunner->reveal();
    return new QaAutomatedTestsCommand($fixture_path_handler, $package_manager, $test_runner);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $exception, $status_code, $display): void {
    $this->packageManager
      ->exists(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('PackageManager::exists', $methods_called))
      ->willReturn(@$args['--sut'] === self::VALID_PACKAGE);
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes((int) in_array('Fixture::exists', $methods_called))
      ->willReturn($fixture_exists);
    $this->testRunner
      ->setSut(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('setSut', $methods_called));
    $this->testRunner
      ->setSutOnly(TRUE)
      ->shouldBeCalledTimes((int) in_array('setSutOnly', $methods_called));
    $this->testRunner
      ->setRunServers(FALSE)
      ->shouldBeCalledTimes((int) in_array('setRunServers', $methods_called));
    $this->testRunner
      ->run()
      ->shouldBeCalledTimes((int) in_array('run', $methods_called));
    if ($exception) {
      $this->testRunner
        ->run()
        ->willThrow(OrcaException::class);
    }

    $this->executeCommand($args);

    self::assertEquals($display, $this->getDisplay(), 'Displayed correct output.');
    self::assertEquals($status_code, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand(): array {
    return [
      [FALSE, [], ['Fixture::exists'], 0, StatusCodeEnum::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['Fixture::exists', 'run'], 0, StatusCodeEnum::OK, ''],
      [TRUE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], 0, StatusCodeEnum::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [TRUE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'run', 'setSut'], 0, StatusCodeEnum::OK, ''],
      [TRUE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'run', 'setSut', 'setSutOnly'], 0, StatusCodeEnum::OK, ''],
      [TRUE, ['--no-servers' => TRUE], ['Fixture::exists', 'run', 'setRunServers'], 0, StatusCodeEnum::OK, ''],
      [TRUE, [], ['Fixture::exists', 'run'], 1, StatusCodeEnum::ERROR, ''],
      [TRUE, ['--sut-only' => TRUE], [], 0, StatusCodeEnum::ERROR, "Error: Cannot run SUT-only tests without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  /**
   * @dataProvider providerFrameworkFlags
   */
  public function testFrameworkFlags($args, $call_set_run_phpunit): void {
    $this->testRunner
      ->setRunPhpunit(TRUE)
      ->shouldBeCalledTimes($call_set_run_phpunit);
    $this->testRunner
      ->run()
      ->shouldBeCalled();

    $this->executeCommand($args);
  }

  public function providerFrameworkFlags(): array {
    return [
      [[], 0],
      [['--phpunit' => 1], 1],
    ];
  }

  /**
   * @dataProvider providerAllOption
   */
  public function testAllOption($args, $call_count): void {
    $this->testRunner
      ->setRunAllTests(TRUE)
      ->shouldBeCalledTimes($call_count);

    $this->executeCommand($args);
  }

  public function providerAllOption(): array {
    return [
      [
        'args' => [],
        'call_count' => 0,
      ],
      [
        'args' => ['--all' => TRUE],
        'call_count' => 1,
      ],
    ];
  }

}
