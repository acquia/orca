<?php

namespace Acquia\Orca\Tests\Command\Tests;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\ProjectManager;
use Acquia\Orca\Task\TestFramework\TestRunner;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Tests\RunCommand;
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
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\ProjectManager $projectManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TaskRunner $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Task\TestFramework\TestRunner $testRunner
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Server\WebServer $webServer
 */
class RunCommandTest extends CommandTestBase {

  protected function setUp() {
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(FALSE);
    $this->fixture->getPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->projectManager = $this->prophesize(ProjectManager::class);
    $this->testRunner = $this->prophesize(TestRunner::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $args, $methods_called, $exception, $status_code, $display) {
    $this->projectManager
      ->exists(@$args['--sut'])
      ->shouldBeCalledTimes((int) in_array('ProjectManager::exists', $methods_called))
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
      ->run()
      ->shouldBeCalledTimes((int) in_array('run', $methods_called));
    if ($exception) {
      $this->testRunner
        ->run()
        ->willThrow(OrcaException::class);
    }
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, RunCommand::getDefaultName(), $args);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [FALSE, [], ['Fixture::exists'], 0, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
      [TRUE, [], ['Fixture::exists', 'run'], 0, StatusCodes::OK, ''],
      [TRUE, ['--sut' => self::INVALID_PACKAGE], ['ProjectManager::exists'], 0, StatusCodes::ERROR, sprintf("Error: Invalid value for \"--sut\" option: \"%s\".\n", self::INVALID_PACKAGE)],
      [TRUE, ['--sut' => self::VALID_PACKAGE], ['ProjectManager::exists', 'Fixture::exists', 'run', 'setSut'], 0, StatusCodes::OK, ''],
      [TRUE, ['--sut' => self::VALID_PACKAGE, '--sut-only' => TRUE], ['ProjectManager::exists', 'Fixture::exists', 'run', 'setSut', 'setSutOnly'], 0, StatusCodes::OK, ''],
      [TRUE, [], ['Fixture::exists', 'run'], 1, StatusCodes::ERROR, ''],
      [TRUE, ['--sut-only' => TRUE], [], 0, StatusCodes::ERROR, "Error: Cannot run SUT-only tests without a SUT.\nHint: Use the \"--sut\" option to specify the SUT.\n"],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\ProjectManager $project_manager */
    $project_manager = $this->projectManager->reveal();
    /** @var \Acquia\Orca\Task\TestFramework\TestRunner $test_runner */
    $test_runner = $this->testRunner->reveal();
    $application->add(new RunCommand($fixture, $project_manager, $test_runner));
    /** @var \Acquia\Orca\Command\Tests\RunCommand $command */
    $command = $application->find(RunCommand::getDefaultName());
    $this->assertInstanceOf(RunCommand::class, $command, 'Instantiated class.');
    return new CommandTester($command);
  }

}
