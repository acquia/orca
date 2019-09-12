<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Enum\DrupalCoreVersion;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\SutPreconditionsTester;
use Acquia\Orca\Utility\DrupalCoreVersionFinder;
use Composer\Semver\VersionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides a command.
 */
class FixtureInitCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:init';

  /**
   * The Drupal core version finder.
   *
   * @var \Acquia\Orca\Utility\DrupalCoreVersionFinder
   */
  private $drupalCoreVersionFinder;

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
   * The SUT preconditions tester.
   *
   * @var \Acquia\Orca\Fixture\SutPreconditionsTester
   */
  private $sutPreconditionsTester;

  /**
   * The version parser.
   *
   * @var \Composer\Semver\VersionParser
   */
  private $versionParser;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\DrupalCoreVersionFinder $drupal_core_version_finder
   *   The Drupal core version finder.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureCreator $fixture_creator
   *   The fixture creator.
   * @param \Acquia\Orca\Fixture\FixtureRemover $fixture_remover
   *   The fixture remover.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Fixture\SutPreconditionsTester $sut_preconditions_tester
   *   The SUT preconditions tester.
   * @param \Composer\Semver\VersionParser $version_parser
   *   The version parser.
   */
  public function __construct(DrupalCoreVersionFinder $drupal_core_version_finder, Fixture $fixture, FixtureCreator $fixture_creator, FixtureRemover $fixture_remover, PackageManager $package_manager, SutPreconditionsTester $sut_preconditions_tester, VersionParser $version_parser) {
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
    $this->fixture = $fixture;
    $this->fixtureCreator = $fixture_creator;
    $this->fixtureRemover = $fixture_remover;
    $this->packageManager = $package_manager;
    $this->sutPreconditionsTester = $sut_preconditions_tester;
    $this->versionParser = $version_parser;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function configure() {
    $this
      ->setAliases(['init'])
      ->setDescription('Creates the test fixture')
      ->setHelp('Creates a BLT-based Drupal site build, includes the system under test using Composer, optionally includes all other Acquia packages, and installs Drupal.')

      // Fundamental options.
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If the fixture already exists, remove it first without confirmation')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"')
      ->addOption('sut-only', NULL, InputOption::VALUE_NONE, 'Add only the system under test (SUT). Omit all other non-required Acquia packages')

      // Common options.
      ->addOption('bare', NULL, InputOption::VALUE_NONE, 'Omit all non-required Acquia packages')
      ->addOption('core', NULL, InputOption::VALUE_REQUIRED, implode(PHP_EOL, array_merge(
        ['Change the version of Drupal core installed:'],
        DrupalCoreVersion::commandHelp(),
        ['- Any version string Composer understands, see https://getcomposer.org/doc/articles/versions.md']
      )), DrupalCoreVersion::CURRENT_RECOMMENDED)
      ->addOption('dev', NULL, InputOption::VALUE_NONE, 'Use dev versions of Acquia packages')
      ->addOption('profile', NULL, InputOption::VALUE_REQUIRED, 'The Drupal installation profile to use, e.g., "lightning"', FixtureCreator::DEFAULT_PROFILE)

      // Uncommon options.
      ->addOption('ignore-patch-failure', NULL, InputOption::VALUE_NONE, 'Do not exit on failure to apply Composer patches. (Useful for debugging failures)')
      ->addOption('no-sqlite', NULL, InputOption::VALUE_NONE, 'Use the default BLT database includes instead of SQLite')
      ->addOption('no-site-install', NULL, InputOption::VALUE_NONE, 'Do not install Drupal. Supersedes the "--profile" option')
      ->addOption('prefer-source', NULL, InputOption::VALUE_NONE, 'Force installation of non-Acquia packages from sources when possible, including VCS information. (Acquia packages are always installed from source.) Useful for core and contrib work');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $bare = $input->getOption('bare');
    $sut = $input->getOption('sut');
    $sut_only = $input->getOption('sut-only');
    $core = $input->getOption('core');

    if (!$this->isValidInput($sut, $sut_only, $bare, $core, $output)) {
      return StatusCode::ERROR;
    }

    $this->setSut($sut);
    $this->setSutOnly($sut_only);
    $this->setBare($bare);
    $this->setComposerExitOnPatchFailure($input->getOption('ignore-patch-failure'));
    $this->setCore($core, $input->getOption('dev'));
    $this->setDev($input->getOption('dev'));
    $this->setPreferSource($input->getOption('prefer-source'));
    $this->setProfile($input->getOption('profile'));
    $this->setSiteInstall($input->getOption('no-site-install'));
    $this->setSqlite($input->getOption('no-sqlite'));

    try {
      $this->testPreconditions($sut);
      if ($this->fixture->exists()) {
        if (!$input->getOption('force')) {
          $output->writeln([
            "Error: Fixture already exists at {$this->fixture->getPath()}.",
            'Hint: Use the "--force" option to remove it and proceed.',
          ]);
          return StatusCode::ERROR;
        }
        $this->fixtureRemover->remove();
      }
      $this->fixtureCreator->create();
    }
    catch (OrcaException $e) {
      (new SymfonyStyle($input, $output))
        ->error($e->getMessage());
      return StatusCode::ERROR;
    }

    return StatusCode::OK;
  }

  /**
   * Determines whether or not the command input is valid.
   *
   * @param string|string[]|bool|null $sut
   *   The "sut" option value.
   * @param string|string[]|bool|null $sut_only
   *   The "sut-only" option value.
   * @param string|string[]|bool|null $bare
   *   The "bare" option value.
   * @param string|string[]|bool|null $core
   *   The "core" option value.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   *   TRUE if the command input is valid or FALSE if not.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  private function isValidInput($sut, $sut_only, $bare, $core, OutputInterface $output): bool {
    if ($bare && $sut) {
      $output->writeln('Error: Cannot create a bare fixture with a SUT.');
      return FALSE;
    }

    if ($core && !$this->isValidCoreValue($core)) {
      $output->writeln([
        sprintf('Error: Invalid value for "--core" option: "%s".', $core),
        sprintf('Hint: Acceptable values are "%s", or any version string Composer understands.', implode('", "', DrupalCoreVersion::values())),
      ]);
      return FALSE;
    }

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
   * Determines whether or not the given "core" option value is valid.
   *
   * @param string|string[]|bool|null $core
   *   The "core" option value.
   *
   * @return bool
   *   TRUE if the value is valid or FALSE if not.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  private function isValidCoreValue($core): bool {
    if (DrupalCoreVersion::isValid($core)) {
      return TRUE;
    }
    try {
      $this->versionParser->parseConstraints($core);
      return TRUE;
    }
    catch (\UnexpectedValueException $e) {
      return FALSE;
    }
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
   * Sets the bare flag.
   *
   * @param string|string[]|bool|null $bare
   *   The bare flag.
   */
  private function setBare($bare): void {
    if ($bare) {
      $this->fixtureCreator->setBare(TRUE);
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
   * Sets the Composer exit on failure flag.
   *
   * @param string|string[]|bool|null $ignore
   *   The ignore-patch-failure flag.
   */
  private function setComposerExitOnPatchFailure($ignore): void {
    if ($ignore) {
      $this->fixtureCreator->setComposerExitOnPatchFailure(FALSE);
    }
  }

  /**
   * Sets the Drupal core version.
   *
   * @param string|string[]|bool|null $version
   *   The version string.
   * @param string|string[]|bool|null $dev
   *   The dev flag.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  private function setCore($version, $dev): void {
    if ($dev && !$version) {
      $version = DrupalCoreVersion::CURRENT_DEV;
    }

    if (!$version) {
      return;
    }

    if (DrupalCoreVersion::isValidKey($version)) {
      $version = $this->drupalCoreVersionFinder->get(new DrupalCoreVersion($version));
    }

    $this->fixtureCreator->setCoreVersion($version);
  }

  /**
   * Sets the prefer source flag.
   *
   * @param string|string[]|bool|null $prefer_source
   *   The prefer-source flag.
   */
  private function setPreferSource($prefer_source): void {
    if ($prefer_source) {
      $this->fixtureCreator->setPreferSource($prefer_source);
    }
  }

  /**
   * Sets the installation profile.
   *
   * @param string|string[]|bool|null $profile
   *   The installation profile.
   */
  private function setProfile($profile): void {
    if ($profile !== FixtureCreator::DEFAULT_PROFILE) {
      $this->fixtureCreator->setProfile($profile);
    }
  }

  /**
   * Sets the site install flag.
   *
   * @param string|string[]|bool|null $no_site_install
   *   The no-site-install flag.
   */
  private function setSiteInstall($no_site_install) {
    if ($no_site_install) {
      $this->fixtureCreator->setInstallSite(FALSE);
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

  /**
   * Tests preconditions.
   *
   * @param string|string[]|bool|null $sut
   *   The SUT.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If preconditions are not satisfied.
   */
  private function testPreconditions($sut) {
    if ($sut) {
      $this->sutPreconditionsTester->test($sut);
    }
  }

}
