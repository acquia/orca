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
   * The "behat" command line option.
   *
   * @var string|string[]|bool|null
   */
  private $behat;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The "no-servers" command line option.
   *
   * @var string|string[]|bool|null
   */
  private $noServers;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * The "phpunit" command line option.
   *
   * @var string|string[]|bool|null
   */
  private $phpunit;

  /**
   * The "sut" command line option.
   *
   * @var string|string[]|bool|null
   */
  private $sut;

  /**
   * The "sut-only" command line option.
   *
   * @var string|string[]|bool|null
   */
  private $sutOnly;

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
      ->addOption('sut-only', NULL, InputOption::VALUE_NONE, 'Run tests from only the system under test (SUT). Omit tests from all other Acquia packages')
      ->addOption('behat', NULL, InputOption::VALUE_NONE, 'Run only PHPUnit tests.')
      ->addOption('phpunit', NULL, InputOption::VALUE_NONE, 'Run only Behat tests.')
      ->addOption('no-servers', NULL, InputOption::VALUE_NONE, "Don't run the ChromeDriver and web servers.");
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $this->behat = $input->getOption('behat');
    $this->noServers = $input->getOption('no-servers');
    $this->phpunit = $input->getOption('phpunit');
    $this->sut = $input->getOption('sut');
    $this->sutOnly = $input->getOption('sut-only');

    if (!$this->isValidInput($output)) {
      return StatusCodes::ERROR;
    }

    if (!$this->fixture->exists()) {
      $output->writeln([
        "Error: No fixture exists at {$this->fixture->getPath()}.",
        'Hint: Use the "fixture:init" command to create one.',
      ]);
      return StatusCodes::ERROR;
    }

    $this->configureTestRunner();

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
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   */
  private function isValidInput(OutputInterface $output): bool {
    if ($this->sutOnly && !$this->sut) {
      $output->writeln([
        'Error: Cannot run SUT-only tests without a SUT.',
        'Hint: Use the "--sut" option to specify the SUT.',
      ]);
      return FALSE;
    }

    if ($this->sut && !$this->packageManager->exists($this->sut)) {
      $output->writeln(sprintf('Error: Invalid value for "--sut" option: "%s".', $this->sut));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Configures the test runner.
   */
  private function configureTestRunner(): void {
    if ($this->sut) {
      $this->testRunner->setSut($this->sut);
    }

    if ($this->sutOnly) {
      $this->testRunner->setSutOnly(TRUE);
    }

    if ($this->noServers) {
      $this->testRunner->setRunServers(FALSE);
    }

    if (!$this->behat && $this->phpunit) {
      $this->testRunner->setRunBehat(FALSE);
    }
    if ($this->behat && !$this->phpunit) {
      $this->testRunner->setRunPhpunit(FALSE);
    }
  }

}
