<?php

namespace Acquia\Orca\Tests\Command\Qa;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Task\TestFramework\TestRunner;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Qa\QaAutomatedTestsCommand;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TestFramework\BehatTask $behat
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\ChromeDriverServer $chromedriver
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\Clock $clock
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TestFramework\PhpUnitTask $phpunit
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\PackageManager $packageManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TestFramework\TestRunner $testRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\WebServer $webServer
 */
class QaAutomatedTestsCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(TRUE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->testRunner = $this->prophesize(TestRunner::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $exception, $status_code, $display) {
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
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, QaAutomatedTestsCommand::getDefaultName(), $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, [], ['Fixture::exists'], 0, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['Fixture::exists', 'run'], 0, StatusCodes::OK, ''],
      [TRUE, ['--sut' => self::INVALID_PACKAGE], ['PackageManager::exists'], 0, StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [TRUE, ['--sut' => self::VALID_PACKAGE], ['PackageManager::exists', 'Fixture::exists', 'run', 'setSut'], 0, StatusCodes::OK, ''],
      [TRUE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['PackageManager::exists', 'Fixture::exists', 'run', 'setSut', 'setSutOnly'], 0, StatusCodes::OK, ''],
      [TRUE, ['--no-servers' => TRUE], ['Fixture::exists', 'run', 'setRunServers'], 0, StatusCodes::OK, ''],
      [TRUE, [], ['Fixture::exists', 'run'], 1, StatusCodes::ERROR, ''],
      [TRUE, ['--sut-only' => TRUE], [], 0, StatusCodes::ERROR, "Error: Cannot run SUT-only tests without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  /**
   * @dataProvider providerFrameworkFlags
   */
  public function testFrameworkFlags($args, $call_set_run_behat, $call_set_run_phpunit) {
    $this->testRunner
      ->setRunBehat(FALSE)
      ->shouldBeCalledTimes($call_set_run_behat);
    $this->testRunner
      ->setRunPhpunit(FALSE)
      ->shouldBeCalledTimes($call_set_run_phpunit);
    $this->testRunner
      ->run()
      ->shouldBeCalled();

    $tester = $this->createCommandTester();

    $this->executeCommand($tester, QaAutomatedTestsCommand::getDefaultName(), $args);
  }

  public function providerFrameworkFlags() {
    return [
      [[], 0, 0],
      [['--behat' => 1], 0, 1],
      [['--phpunit' => 1], 1, 0],
      [['--behat' => 1, '--phpunit' => 1], 0, 0],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\PackageManager $package_manager */
    $package_manager = $this->packageManager->reveal();
    /** @var \Acquia\Orca\Task\TestFramework\TestRunner $test_runner */
    $test_runner = $this->testRunner->reveal();
    $application->add(new QaAutomatedTestsCommand($fixture, $package_manager, $test_runner));
    /** @var \Acquia\Orca\Command\Qa\QaAutomatedTestsCommand $command */
    $command = $application->find(QaAutomatedTestsCommand::getDefaultName());
    $this->assertInstanceOf(QaAutomatedTestsCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
