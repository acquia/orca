<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Fixture\ProjectManager;
use Acquia\Orca\Server\ServerStack;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Runs automated tests.
 */
class TestRunner {

  use SutSettingsTrait;

  /**
   * The Behat task.
   *
   * @var \Acquia\Orca\Task\TestFramework\BehatTask
   */
  private $behat;

  /**
   * The finder.
   *
   * @var \Symfony\Component\Finder\Finder
   */
  private $finder;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The PHPUnit task.
   *
   * @var \Acquia\Orca\Task\TestFramework\PhpUnitTask
   */
  private $phpunit;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The project manager.
   *
   * @var \Acquia\Orca\Fixture\ProjectManager
   */
  private $projectManager;

  /**
   * The server stack.
   *
   * @var \Acquia\Orca\Server\ServerStack
   */
  private $serverStack;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Task\TestFramework\BehatTask $behat
   *   The Behat task.
   * @param \Symfony\Component\Finder\Finder $finder
   *   The finder.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Task\TestFramework\PhpUnitTask $phpunit
   *   The PHPUnit task.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\ProjectManager $project_manager
   *   The project manager.
   * @param \Acquia\Orca\Server\ServerStack $server_stack
   *   The server stack.
   */
  public function __construct(BehatTask $behat, Finder $finder, SymfonyStyle $output, PhpUnitTask $phpunit, ProcessRunner $process_runner, ProjectManager $project_manager, ServerStack $server_stack) {
    $this->behat = $behat;
    $this->finder = $finder;
    $this->output = $output;
    $this->phpunit = $phpunit;
    $this->processRunner = $process_runner;
    $this->projectManager = $project_manager;
    $this->serverStack = $server_stack;
  }

  /**
   * Runs the tests.
   *
   * @throws \Acquia\Orca\Exception\TaskFailureException
   */
  public function run(): void {
    $this->startServers();
    if ($this->sut) {
      $this->runSutTests();
    }
    if (!$this->isSutOnly) {
      $this->runNonSutTests();
    }
    $this->stopServers();
  }

  /**
   * Starts servers.
   */
  private function startServers(): void {
    $this->output->comment('Starting servers');
    $this->serverStack->start();
  }

  /**
   * Runs tests for the system under test (SUT).
   *
   * @throws \Acquia\Orca\Exception\TaskFailureException
   */
  private function runSutTests(): void {
    $this->output->title('Running SUT tests');
    foreach ($this->getTestFrameworks() as $task) {
      $this->output->section("{$task->statusMessage()} for {$this->sut->getPackageName()}");
      $task->setPath($this->sut->getInstallPathAbsolute());
      $task->execute();
    }
  }

  /**
   * Runs tests for projects other than the system under test (SUT).
   *
   * @throws \Acquia\Orca\Exception\TaskFailureException
   */
  private function runNonSutTests(): void {
    $message = ($this->sut) ? 'Running public non-SUT tests' : 'Running all public tests';
    $this->output->title($message);
    foreach ($this->projectManager->getMultiple() as $project) {
      if ($this->sut && $project->getPackageName() === $this->sut->getPackageName()) {
        continue;
      }
      foreach ($this->getTestFrameworks() as $task) {
        $this->output->section("{$task->statusMessage()} for {$project->getPackageName()}");
        $task->setPath($project->getInstallPathAbsolute());
        $task->execute();
      }
    }
  }

  /**
   * Stops servers.
   */
  private function stopServers(): void {
    $this->output->comment('Stopping servers');
    $this->serverStack->stop();
  }

  /**
   * Gets the test framework tasks.
   *
   * @return \Acquia\Orca\Task\TestFramework\TestFrameworkInterface[]
   */
  private function getTestFrameworks(): array {
    return [
      clone $this->phpunit,
      clone $this->behat,
    ];
  }

}
