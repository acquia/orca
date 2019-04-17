<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\DrupalCoreVersionFinder;
use Composer\Semver\VersionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureInitCommand extends Command {

  public const CORE_OPTION_SPECIAL_VALUES = [
    self::PREVIOUS_MINOR,
    self::CURRENT_RECOMMENDED,
    self::CURRENT_DEV,
    self::LATEST_PRERELEASE,
  ];

  public const PREVIOUS_MINOR = 'PREVIOUS_MINOR';

  public const CURRENT_RECOMMENDED = 'CURRENT_RECOMMENDED';

  public const CURRENT_DEV = 'CURRENT_DEV';

  public const LATEST_PRERELEASE = 'LATEST_PRERELEASE';

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
   * @param \Composer\Semver\VersionParser $version_parser
   *   The version parser.
   */
  public function __construct(DrupalCoreVersionFinder $drupal_core_version_finder, Fixture $fixture, FixtureCreator $fixture_creator, FixtureRemover $fixture_remover, PackageManager $package_manager, VersionParser $version_parser) {
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
    $this->fixture = $fixture;
    $this->fixtureCreator = $fixture_creator;
    $this->fixtureRemover = $fixture_remover;
    $this->packageManager = $package_manager;
    $this->versionParser = $version_parser;
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
      ->addOption('core', NULL, InputOption::VALUE_REQUIRED, implode(PHP_EOL, [
        'Change the version of Drupal core installed:',
        sprintf('- %s: The latest stable release of the previous minor version, e.g., "8.5.14" if the current minor version is "8.6"', self::PREVIOUS_MINOR),
        sprintf('- %s: The current recommended release, e.g., "8.6.14"', self::CURRENT_RECOMMENDED),
        sprintf('- %s: The current development version, e.g., "8.6.x-dev"', self::CURRENT_DEV),
        sprintf('- %s: The latest pre-release version, e.g., "8.7.0-beta2". Note: This could be newer OR older than the current recommended release', self::LATEST_PRERELEASE),
        '- Any version string Composer understands, see https://getcomposer.org/doc/articles/versions.md',
      ]))
      ->addOption('dev', NULL, InputOption::VALUE_NONE, 'Use dev versions of Acquia packages')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If the fixture already exists, remove it first without confirmation')
      ->addOption('profile', NULL, InputOption::VALUE_REQUIRED, 'The Drupal installation profile to use, e.g., "lightning"', FixtureCreator::DEFAULT_PROFILE)
      ->addOption('no-sqlite', NULL, InputOption::VALUE_NONE, 'Use the default BLT database includes instead of SQLite')
      ->addOption('no-site-install', NULL, InputOption::VALUE_NONE, 'Do not install Drupal. Supersedes the "--profile" option');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $sut = $input->getOption('sut');
    $sut_only = $input->getOption('sut-only');
    $core = $input->getOption('core');

    if (!$this->isValidInput($sut, $sut_only, $core, $output)) {
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
    $this->setCore($core, $input->getOption('dev'));
    $this->setProfile($input->getOption('profile'));
    $this->setSiteInstall($input->getOption('no-site-install'));
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
   * Determines whether or not the command input is valid.
   *
   * @param string|string[]|bool|null $sut
   *   The "sut" option value.
   * @param string|string[]|bool|null $sut_only
   *   The "sut-only" option value.
   * @param string|string[]|bool|null $core
   *   The "core" option value.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   *   TRUE if the command input is valid or FALSE if not.
   */
  private function isValidInput($sut, $sut_only, $core, OutputInterface $output): bool {
    if ($core && !$this->isValidCoreValue($core)) {
      $output->writeln([
        sprintf('Error: Invalid value for "--core" option: "%s".', $core),
        sprintf('Hint: Acceptable values are "%s", "%s", "%s", "%s", or any version string Composer understands.', self::PREVIOUS_MINOR, self::CURRENT_RECOMMENDED, self::CURRENT_DEV, self::LATEST_PRERELEASE),
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
   */
  private function isValidCoreValue($core): bool {
    if (in_array($core, self::CORE_OPTION_SPECIAL_VALUES)) {
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
   * @param string|string[]|bool|null $dev
   *   The dev flag.
   */
  private function setCore($version, $dev): void {
    if ($dev && !$version) {
      $version = self::CURRENT_DEV;
    }

    if (!$version) {
      return;
    }

    switch ($version) {
      case self::PREVIOUS_MINOR:
        $version = $this->drupalCoreVersionFinder->getPreviousMinorVersion();
        break;

      case self::CURRENT_RECOMMENDED:
        $version = $this->drupalCoreVersionFinder->getCurrentRecommendedVersion();
        break;

      case self::CURRENT_DEV:
        $version = $this->drupalCoreVersionFinder->getCurrentDevVersion();
        break;

      case self::LATEST_PRERELEASE:
        $version = $this->drupalCoreVersionFinder->getLatestPreReleaseVersion();
        break;
    }
    $this->fixtureCreator->setCoreVersion($version);
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

}
