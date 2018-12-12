<?php

namespace Acquia\Orca\Command\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Chromedriver;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Task\BehatTask;
use Acquia\Orca\Task\PhpUnitTask;
use Acquia\Orca\Task\TaskRunner;
use Acquia\Orca\Fixture\WebServer;
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
   * The Chromedriver.
   *
   * @var \Acquia\Orca\Fixture\Chromedriver
   */
  private $chromedriver;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The task runner.
   *
   * @var \Acquia\Orca\Task\TaskRunner
   */
  private $taskRunner;

  /**
   * The web server.
   *
   * @var \Acquia\Orca\Fixture\WebServer
   */
  private $webServer;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\BehatTask $behat
   *   The Behat task.
   * @param \Acquia\Orca\Fixture\Chromedriver $chromedriver
   *   The Chromedriver.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Task\PhpUnitTask $phpunit
   *   The PHPUnit task.
   * @param \Acquia\Orca\Task\TaskRunner $task_runner
   *   The task runner.
   * @param \Acquia\Orca\Fixture\WebServer $web_server
   *   The web server.
   */
  public function __construct(BehatTask $behat, Chromedriver $chromedriver, Fixture $fixture, PhpUnitTask $phpunit, TaskRunner $task_runner, WebServer $web_server) {
    $this->fixture = $fixture;
    $this->taskRunner = (clone($task_runner))
      ->addTask($phpunit)
      ->addTask($behat);
    $this->webServer = $web_server;
    $this->chromedriver = $chromedriver;
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
    $this->chromedriver->start();

    $status_code = $this->taskRunner
      ->setPath($this->fixture->testsDirectory())
      ->run();

    $this->chromedriver->stop();
    $this->webServer->stop();

    return $status_code;
  }

}
