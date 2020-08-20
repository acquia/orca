<?php

namespace Acquia\Orca\Tests\Console\Command\Qa;

use Acquia\Orca\Console\Command\Qa\QaAutomatedTestsCommand;
use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Helper\Exception\OrcaException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Package\PackageManager;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Acquia\Orca\Tool\TestRunner;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\ChromeDriverServer $chromedriver
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Clock $clock
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Tool\Phpunit\PhpUnitTask $phpunit
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Package\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Helper\Task\TaskRunner $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Tool\TestRunner $testRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\WebServer $webServer
 */
class QaAutomatedTestsCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->testRunner = $this->prophesize(TestRunner::class);
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
      [FALSE, [], ['Fixture::exists'], 0, StatusCode::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['Fixture::exists', 'run'], 0, StatusCode::OK, ''],
      [TRUE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], 0, StatusCode::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [TRUE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'run', 'setSut'], 0, StatusCode::OK, ''],
      [TRUE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'run', 'setSut', 'setSutOnly'], 0, StatusCode::OK, ''],
      [TRUE, ['--no-servers' => TRUE], ['Fixture::exists', 'run', 'setRunServers'], 0, StatusCode::OK, ''],
      [TRUE, [], ['Fixture::exists', 'run'], 1, StatusCode::ERROR, ''],
      [TRUE, ['--sut-only' => TRUE], [], 0, StatusCode::ERROR, "Error: Cannot run SUT-only tests without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  /**
   * @dataProvider providerFrameworkFlags
   */
  public function testFrameworkFlags($args, $call_set_run_phpunit): void {
    $this->testRunner
      ->setRunPhpunit(FALSE)
      ->shouldBeCalledTimes($call_set_run_phpunit);
    $this->testRunner
      ->run()
      ->shouldBeCalled();

    $this->executeCommand($args);
  }

  public function providerFrameworkFlags(): array {
    return [
      [[], 0],
      [['--phpunit' => 1], 0],
    ];
  }

}
