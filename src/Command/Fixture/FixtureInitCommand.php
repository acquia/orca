<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureInitCommand extends Command {

  const DEFAULT_PROFILE = 'minimal';

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:init';

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The fixture creator.
   *
   * @var \Acquia\Orca\Fixture\FixtureCreator
   */
  private $fixtureCreator;

  /**
   * The fixture remover.
   *
   * @var \Acquia\Orca\Fixture\FixtureRemover
   */
  private $fixtureRemover;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureCreator $fixture_creator
   *   The fixture creator.
   * @param \Acquia\Orca\Fixture\FixtureRemover $fixture_remover
   *   The fixture remover.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(Fixture $fixture, FixtureCreator $fixture_creator, FixtureRemover $fixture_remover, PackageManager $package_manager) {
    $this->fixtureCreator = $fixture_creator;
    $this->fixture = $fixture;
    $this->packageManager = $package_manager;
    $this->fixtureRemover = $fixture_remover;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['init'])
      ->setDescription('Creates the test fixture')
      ->setHelp('Creates a BLT-based Drupal site build, includes the system under test using Composer, optionally includes all other Acquia packages, and installs Drupal.')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"')
      ->addOption('sut-only', NULL, InputOption::VALUE_NONE, 'Add only the system under test (SUT). Omit all other non-required Acquia packages')
      ->addOption('core', NULL, InputOption::VALUE_REQUIRED, 'Change the version of Drupal core installed, e.g., "8.6.0", "~8.6", or "8.6.x-dev"')
      ->addOption('dev', NULL, InputOption::VALUE_NONE, 'Use dev (HEAD) branches instead of stable releases of Drupal core and Acquia packages')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If the fixture already exists, remove it first without confirmation')
      ->addOption('profile', NULL, InputOption::VALUE_REQUIRED, 'The Drupal installation profile to use, e.g., "lightning"', self::DEFAULT_PROFILE)
      ->addOption('no-sqlite', NULL, InputOption::VALUE_NONE, 'Use the default BLT database includes instead of SQLite');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $sut = $input->getOption('sut');
    $sut_only = $input->getOption('sut-only');

    if (!$this->isValidInput($sut, $sut_only, $output)) {
      return StatusCodes::ERROR;
    }

    if ($this->fixture->exists()) {
      if (!$input->getOption('force')) {
        $output->writeln([
          "Error: Fixture already exists at {$this->fixture->getPath()}.",
          'Hint: Use the "--force" option to remove it and proceed.',
        ]);
        return StatusCodes::ERROR;
      }

      $this->fixtureRemover->remove();
    }

    $this->setSut($sut);
    $this->setSutOnly($sut_only);
    $this->setDev($input->getOption('dev'));
    $this->setCore($input->getOption('core'));
    $this->setProfile($input->getOption('profile'));
    $this->setSqlite($input->getOption('no-sqlite'));

    try {
      $this->fixtureCreator->create();
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
   *   TRUE if the command input is valid or FALSE if not.
   */
  private function isValidInput($sut, $sut_only, OutputInterface $output): bool {
    if ($sut_only && !$sut) {
      $output->writeln([
        'Error: Cannot create a SUT-only fixture without a SUT.',
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
      $this->fixtureCreator->setSut($sut);
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
      $this->fixtureCreator->setSutOnly(TRUE);
    }
  }

  /**
   * Sets the dev flag.
   *
   * @param string|string[]|bool|null $dev
   *   The dev flag.
   */
  private function setDev($dev): void {
    if ($dev) {
      $this->fixtureCreator->setDev($dev);
    }
  }

  /**
   * Sets the Drupal core version.
   *
   * @param string|string[]|bool|null $version
   *   The version string.
   */
  private function setCore($version): void {
    if ($version) {
      $this->fixtureCreator->setCoreVersion($version);
    }
  }

  /**
   * Sets the installation profile.
   *
   * @param string|string[]|bool|null $profile
   *   The installation profile.
   */
  private function setProfile($profile): void {
    if ($profile !== self::DEFAULT_PROFILE) {
      $this->fixtureCreator->setProfile($profile);
    }
  }

  /**
   * Sets the SQLite flag.
   *
   * @param string|string[]|bool|null $no_sqlite
   *   The no-sqlite flag.
   */
  private function setSqlite($no_sqlite): void {
    if ($no_sqlite) {
      $this->fixtureCreator->setSqlite(FALSE);
    }
  }

}
