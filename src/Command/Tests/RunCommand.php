<?php

namespace Acquia\Orca\Command\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Task\BehatTask;
use Acquia\Orca\Task\PhpUnitTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\WebServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 *
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\Task\TaskRunner $taskRunner
 * @property \Acquia\Orca\WebServer $webServer
 */
class RunCommand extends Command {

  protected static $defaultName = 'tests:run';

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\BehatTask $behat
   *   The Behat task.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Task\PhpUnitTask $phpunit
   *   The PHPUnit task.
   * @param \Acquia\Orca\Task\TaskRunner $task_runner
   *   The task runner.
   * @param \Acquia\Orca\WebServer $web_server
   *   The web server.
   */
  public function __construct(BehatTask $behat, Fixture $fixture, PhpUnitTask $phpunit, TaskRunner $task_runner, WebServer $web_server) {
    $this->fixture = $fixture;
    $this->taskRunner = (clone($task_runner))
      ->addTask($phpunit)
      ->addTask($behat);
    $this->webServer = $web_server;
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

    $this->webServer->start();
    $status_code = $this->taskRunner
      ->setPath($this->fixture->testsDirectory())
      ->run();
    $this->webServer->stop();

    return $status_code;
  }

}
