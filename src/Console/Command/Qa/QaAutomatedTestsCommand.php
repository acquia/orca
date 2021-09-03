<?php

namespace Acquia\Orca\Console\Command\Qa;

use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Domain\Tool\TestRunner;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class QaAutomatedTestsCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'qa:automated-tests';

  /**
   * The "all" command line option.
   *
   * @var bool
   */
  private $all;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
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
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

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
   * @var \Acquia\Orca\Domain\Tool\TestRunner
   */
  private $testRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Domain\Tool\TestRunner $test_runner
   *   The test runner.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, PackageManager $package_manager, TestRunner $test_runner) {
    $this->fixture = $fixture_path_handler;
    $this->packageManager = $package_manager;
    $this->testRunner = $test_runner;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['test'])
      ->setDescription('Runs automated tests')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"')
      ->addOption('sut-only', NULL, InputOption::VALUE_NONE, 'Run tests from only the system under test (SUT). Omit tests from all other company packages')
      ->addOption('all', NULL, InputOption::VALUE_NONE, 'Run all tests, public and private (always true for the system under test (SUT))')
      ->addOption('phpunit', NULL, InputOption::VALUE_NONE, 'Run only PHPUnit tests')
      ->addOption('no-servers', NULL, InputOption::VALUE_NONE, "Don't run the ChromeDriver and web servers");
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $this->noServers = $input->getOption('no-servers');
    $this->sut = $input->getOption('sut');
    $this->sutOnly = $input->getOption('sut-only');
    $this->all = $input->getOption('all');

    if (!$this->isValidInput($output)) {
      return StatusCodeEnum::ERROR;
    }

    if (!$this->fixture->exists()) {
      $output->writeln([
        "Error: No fixture exists at {$this->fixture->getPath()}.",
        'Hint: Use the "fixture:init" command to create one.',
      ]);
      return StatusCodeEnum::ERROR;
    }

    $this->configureTestRunner();

    try {
      $this->testRunner->run();
    }
    catch (OrcaException $e) {
      return StatusCodeEnum::ERROR;
    }

    return StatusCodeEnum::OK;
  }

  /**
   * Determines whether the command input is valid.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   *   TRUE if the command input is valid or FALSE if not.
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

    if ($this->all) {
      $this->testRunner->setRunAllTests(TRUE);
    }

    if ($this->noServers) {
      $this->testRunner->setRunServers(FALSE);
    }
  }

}
