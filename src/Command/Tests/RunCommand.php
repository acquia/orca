<?php

namespace Acquia\Orca\Command\Tests;

use Acquia\Orca\Utility\Clock;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Server\ChromeDriverServer;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Task\BehatTask;
use Acquia\Orca\Task\PhpUnitTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Server\WebServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class RunCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'tests:run';

  /**
   * The clock.
   *
   * @var \Acquia\Orca\Utility\Clock
   */
  private $clock;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The servers to run before tests.
   *
   * @var \Acquia\Orca\Server\ServerInterface[]
   */
  private $servers;

  /**
   * The task runner.
   *
   * @var \Acquia\Orca\Task\TaskRunner
   */
  private $taskRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\BehatTask $behat
   *   The Behat task.
   * @param \Acquia\Orca\Server\ChromeDriverServer $chrome_driver_server
   *   The ChromeDriver server.
   * @param \Acquia\Orca\Utility\Clock $clock
   *   The clock.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Task\PhpUnitTask $phpunit
   *   The PHPUnit task.
   * @param \Acquia\Orca\Task\TaskRunner $task_runner
   *   The task runner.
   * @param \Acquia\Orca\Server\WebServer $web_server
   *   The web server.
   */
  public function __construct(BehatTask $behat, ChromeDriverServer $chrome_driver_server, Clock $clock, Fixture $fixture, PhpUnitTask $phpunit, TaskRunner $task_runner, WebServer $web_server) {
    $this->clock = $clock;
    $this->fixture = $fixture;
    $this->servers = [
      $web_server,
      $chrome_driver_server,
    ];
    $this->taskRunner = (clone($task_runner))
      ->addTask($phpunit)
      ->addTask($behat);
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['test'])
      ->setDescription('Runs automated tests');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln([
        "Error: No fixture exists at {$this->fixture->rootPath()}.",
        'Hint: Use the "fixture:init" command to create one.',
      ]);
      return StatusCodes::ERROR;
    }

    $this->startServers();
    $status_code = $this->runTests();
    $this->stopServers();

    return $status_code;
  }

  /**
   * Starts servers.
   */
  protected function startServers(): void {
    foreach ($this->servers as $server) {
      $server->start();
    }
    // Give the servers a chance to bootstrap before releasing the thread to
    // tasks that will depend on them.
    $this->clock->sleep(3);
  }

  /**
   * Runs tests.
   *
   * @return int
   *   A status code.
   *
   * @see \Acquia\Orca\Command\StatusCodes
   */
  protected function runTests(): int {
    return $this->taskRunner
      ->setPath($this->fixture->testsDirectory())
      ->run();
  }

  /**
   * Stops servers.
   */
  protected function stopServers(): void {
    foreach ($this->servers as $server) {
      $server->stop();
    }
  }

}
