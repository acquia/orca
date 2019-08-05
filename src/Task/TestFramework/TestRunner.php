<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Exception\TaskFailureException;
use Acquia\Orca\Fixture\FixtureResetter;
use Acquia\Orca\Fixture\Package;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Server\ServerStack;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

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
   * A list of test failure descriptions.
   *
   * @var string[]
   */
  private $failures = [];

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The fixture resetter.
   *
   * @var \Acquia\Orca\Fixture\FixtureResetter
   */
  private $fixtureResetter;

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
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * The run Behat flag.
   *
   * @var bool
   */
  private $runBehat = TRUE;

  /**
   * The run PHPUnit flag.
   *
   * @var bool
   */
  private $runPhpunit = TRUE;

  /**
   * The run servers flag.
   *
   * @var bool
   */
  private $runServers = TRUE;

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
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\FixtureResetter $fixture_resetter
   *   The fixture resetter.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Task\TestFramework\PhpUnitTask $phpunit
   *   The PHPUnit task.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Server\ServerStack $server_stack
   *   The server stack.
   */
  public function __construct(BehatTask $behat, Filesystem $filesystem, FixtureResetter $fixture_resetter, SymfonyStyle $output, PhpUnitTask $phpunit, ProcessRunner $process_runner, PackageManager $package_manager, ServerStack $server_stack) {
    $this->behat = $behat;
    $this->filesystem = $filesystem;
    $this->fixtureResetter = $fixture_resetter;
    $this->output = $output;
    $this->phpunit = $phpunit;
    $this->processRunner = $process_runner;
    $this->packageManager = $package_manager;
    $this->serverStack = $server_stack;
  }

  /**
   * Runs the tests.
   *
   * @throws \Acquia\Orca\Exception\TaskFailureException
   */
  public function run(): void {
    if ($this->runServers) {
      $this->startServers();
    }

    if ($this->sut) {
      $this->runSutTests();
    }
    if (!$this->isSutOnly) {
      $this->runNonSutTests();
    }

    if ($this->runServers) {
      $this->stopServers();
    }

    if ($this->failures) {
      $this->output->block(implode(PHP_EOL, $this->failures), 'FAILURES', 'fg=white;bg=red', ' ', TRUE);
      $this->output->writeln('');
      throw new TaskFailureException();
    }
    $this->output->success('Tests passed');
  }

  /**
   * Sets the run Behat flag.
   *
   * @param bool $run_behat
   *   TRUE to run Behat or FALSE not to.
   */
  public function setRunBehat(bool $run_behat) {
    $this->runBehat = $run_behat;
  }

  /**
   * Sets the run PHPUnit flag.
   *
   * @param bool $run_phpunit
   *   TRUE to run PHPUnit or FALSE not to.
   */
  public function setRunPhpunit(bool $run_phpunit) {
    $this->runPhpunit = $run_phpunit;
  }

  /**
   * Sets the run servers flag.
   *
   * @param bool $run_servers
   *   TRUE to run servers or FALSE not to.
   */
  public function setRunServers(bool $run_servers): void {
    $this->runServers = $run_servers;
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
   */
  private function runSutTests(): void {
    $this->output->title('Running SUT tests');
    foreach ($this->getFrameworks() as $framework) {
      $this->execute($framework, $this->sut, FALSE);
    }
  }

  /**
   * Runs tests for packages other than the system under test (SUT).
   */
  private function runNonSutTests(): void {
    $message = ($this->sut) ? 'Running public non-SUT tests' : 'Running all public tests';
    $this->output->title($message);
    foreach ($this->packageManager->getMultiple() as $package) {
      if ($this->sut && $package->getPackageName() === $this->sut->getPackageName()) {
        continue;
      }
      if (!$this->filesystem->exists($package->getInstallPathAbsolute())) {
        $this->output->warning(sprintf('Package %s absent from expected location: %s', $package->getPackageName(), $package->getInstallPathAbsolute()));
        continue;
      }
      foreach ($this->getFrameworks() as $framework) {
        $this->execute($framework, $package, TRUE);
      }
    }
  }

  /**
   * Executes the given test framework on the specified package.
   *
   * @param \Acquia\Orca\Task\TestFramework\TestFrameworkInterface $framework
   *   The test framework to execute.
   * @param \Acquia\Orca\Fixture\Package $package
   *   The package to test.
   * @param bool $public
   *   TRUE to limit to public tests or FALSE not to.
   */
  private function execute(TestFrameworkInterface $framework, Package $package, bool $public): void {
    try {
      $this->output->section("{$framework->statusMessage()} for {$package->getPackageName()}");
      $this->output->comment('Resetting test fixture');
      $this->fixtureResetter->reset();
      $this->output->comment('Running tests');
      $framework->setPath($package->getInstallPathAbsolute());
      $framework->limitToPublicTests($public);
      $framework->execute();
    }
    catch (TaskFailureException $e) {
      $failure = "{$package->getPackageName()}: {$framework->label()}";
      $this->output->block($failure, 'FAILURE', 'fg=white;bg=red');
      $this->failures[] = $failure;
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
   *   An indexed array of framework tasks.
   */
  private function getFrameworks(): array {
    $frameworks = [];
    if ($this->runPhpunit) {
      $frameworks[] = $this->phpunit;
    }
    if ($this->runBehat) {
      $frameworks[] = $this->behat;
    }
    return $frameworks;
  }

}
