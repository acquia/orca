<?php

namespace Acquia\Orca\Tests\Command\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Command\Tests\RunCommand;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Task\BehatTask;
use Acquia\Orca\Task\PhpUnitTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\TestRunner;
use Acquia\Orca\Tests\Command\CommandTestBase;
use Acquia\Orca\WebServer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy $behat
 * @property \Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy $phpunit
 * @property \Prophecy\Prophecy\ObjectProphecy $taskRunner
 * @property \Prophecy\Prophecy\ObjectProphecy $webServer
 */
class RunCommandTest extends CommandTestBase {

  private const FIXTURE_ROOT = '/var/www/orca-build';

  private const TESTS_DIR = '/var/www/orca-build/docroot/modules/contrib/acquia';

  protected function setUp() {
    $this->behat = $this->prophesize(BehatTask::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixture->exists()
      ->willReturn(FALSE);
    $this->fixture->rootPath()
      ->willReturn(self::FIXTURE_ROOT);
    $this->fixture->testsDirectory()
      ->willReturn(self::TESTS_DIR);
    $this->phpunit = $this->prophesize(PhpUnitTask::class);
    $this->taskRunner = $this->prophesize(TaskRunner::class);
    $this->webServer = $this->prophesize(WebServer::class);
  }

  /**
   * @dataProvider providerCommand
   */
  public function testCommand($fixture_exists, $run_called, $status_code, $display) {
    $this->fixture
      ->exists()
      ->shouldBeCalledTimes(1)
      ->willReturn($fixture_exists);
    $this->taskRunner
      ->addTask($this->phpunit->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->addTask($this->behat->reveal())
      ->shouldBeCalledTimes(1)
      ->willReturn($this->taskRunner);
    $this->webServer
      ->start()
      ->shouldBeCalledTimes($run_called);
    $this->taskRunner
      ->setPath(self::TESTS_DIR)
      ->shouldBeCalledTimes($run_called)
      ->willReturn($this->taskRunner);
    $this->taskRunner
      ->run()
      ->shouldBeCalledTimes($run_called)
      ->willReturn($status_code);
    $this->webServer
      ->stop()
      ->shouldBeCalledTimes($run_called);
    $tester = $this->createCommandTester();

    $this->executeCommand($tester, RunCommand::getDefaultName(), []);

    $this->assertEquals($display, $tester->getDisplay(), 'Displayed correct output.');
    $this->assertEquals($status_code, $tester->getStatusCode(), 'Returned correct status code.');
  }

  public function providerCommand() {
    return [
      [TRUE, 1, StatusCodes::OK, ''],
      [TRUE, 1, StatusCodes::ERROR, ''],
      [FALSE, 0, StatusCodes::ERROR, sprintf("Error: No fixture exists at %s.\nHint: Use the \"fixture:init\" command to create one.\n", self::FIXTURE_ROOT)],
    ];
  }

  private function createCommandTester(): CommandTester {
    $application = new Application();
    /** @var \Acquia\Orca\Task\BehatTask $behat */
    $behat = $this->behat->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Task\PhpUnitTask $phpunit */
    $phpunit = $this->phpunit->reveal();
    /** @var \Acquia\Orca\Task\TaskRunner $task_runner */
    $task_runner = $this->taskRunner->reveal();
    /** @var \Acquia\Orca\WebServer $web_server */
    $web_server = $this->webServer->reveal();
    $application->add(new RunCommand($behat, $fixture, $phpunit, $task_runner, $web_server));
    /** @var \Acquia\Orca\Command\Tests\RunCommand $command */
    $command = $application->find(RunCommand::getDefaultName());
    $this->assertInstanceOf(RunCommand::class, $command);
    return new CommandTester($command);
  }

}
