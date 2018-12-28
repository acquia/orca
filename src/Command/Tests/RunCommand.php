<?php

namespace Acquia\Orca\Command\Tests;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Task\TestFramework\TestRunner;
use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * The test runner.
   *
   * @var \Acquia\Orca\Task\TestFramework\TestRunner
   */
  private $testRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Task\TestFramework\TestRunner $test_runner
   *   The test runner.
   */
  public function __construct(Fixture $fixture, PackageManager $package_manager, TestRunner $test_runner) {
    $this->fixture = $fixture;
    $this->packageManager = $package_manager;
    $this->testRunner = $test_runner;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['test'])
      ->setDescription('Runs automated tests')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"')
      ->addOption('sut-only', NULL, InputOption::VALUE_NONE, 'Run tests from only the system under test (SUT). Omit tests from all other Acquia product modules');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $sut = $input->getOption('sut');
    $sut_only = $input->getOption('sut-only');

    if (!$this->isValidInput($sut, $sut_only, $output)) {
      return StatusCodes::ERROR;
    }

    if (!$this->fixture->exists()) {
      $output->writeln([
        "Error: No fixture exists at {$this->fixture->getPath()}.",
        'Hint: Use the "fixture:init" command to create one.',
      ]);
      return StatusCodes::ERROR;
    }

    $this->setSut($sut);
    $this->setSutOnly($sut_only);

    try {
      $this->testRunner->run();
    }
    catch (OrcaException $e) {
      return StatusCodes::ERROR;
    }

    return StatusCodes::OK;
  }

  /**
   * Determines whether the command input is valid.
   *
   * @param string|string[]|bool|null $sut
   *   The "sut" option value.
   * @param string|string[]|bool|null $sut_only
   *   The "sut-only" option value.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   */
  private function isValidInput($sut, $sut_only, OutputInterface $output): bool {
    if ($sut_only && !$sut) {
      $output->writeln([
        'Error: Cannot run SUT-only tests without a SUT.',
        'Hint: Use the "--sut" option to specify the SUT.',
      ]);
      return FALSE;
    }

    if ($sut && !$this->packageManager->exists($sut)) {
      $output->writeln(sprintf('Error: Invalid value for "--sut" option: "%s".', $sut));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sets the SUT.
   *
   * @param string|string[]|bool|null $sut
   *   The SUT.
   */
  private function setSut($sut): void {
    if ($sut) {
      $this->testRunner->setSut($sut);
    }
  }

  /**
   * Sets the SUT-only flag.
   *
   * @param string|string[]|bool|null $sut_only
   *   The SUT-only flag.
   */
  private function setSutOnly($sut_only): void {
    if ($sut_only) {
      $this->testRunner->setSutOnly(TRUE);
    }
  }

}
