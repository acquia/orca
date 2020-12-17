<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Console\Helper\StatusTable;
use Acquia\Orca\Domain\Composer\ComposerFacade;
use Acquia\Orca\Domain\Composer\Version\VersionFinder;
use Acquia\Orca\Domain\Composer\Version\VersionGuesser;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Fixture\Helper\DrupalSettingsHelper;
use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\FixtureOptions;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a fixture.
 */
class FixtureCreator {

  public const DEFAULT_PROFILE = 'orca';

  /**
   * The Cloud Hooks installer.
   *
   * @var \Acquia\Orca\Domain\Fixture\CloudHooksInstaller
   */
  private $cloudHooksInstaller;

  /**
   * The codebase creator.
   *
   * @var \Acquia\Orca\Domain\Fixture\CodebaseCreator
   */
  private $codebaseCreator;

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Domain\Composer\ComposerFacade
   */
  private $composer;

  /**
   * The fixture composer.json helper.
   *
   * @var \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper
   */
  private $composerJsonHelper;

  /**
   * The Drupal settings helper.
   *
   * @var \Acquia\Orca\Domain\Fixture\Helper\DrupalSettingsHelper
   */
  private $drupalSettingsHelper;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture inspector.
   *
   * @var \Acquia\Orca\Domain\Fixture\FixtureInspector
   */
  private $fixtureInspector;

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Domain\Git\GitFacade
   */
  private $git;

  /**
   * The fixture options.
   *
   * @var \Acquia\Orca\Options\FixtureOptions
   */
  private $options;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * The site installer.
   *
   * @var \Acquia\Orca\Domain\Fixture\SiteInstaller
   */
  private $siteInstaller;

  /**
   * The subextension manager.
   *
   * @var \Acquia\Orca\Domain\Fixture\SubextensionManager
   */
  private $subextensionManager;

  /**
   * The version finder.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\VersionFinder
   */
  private $versionFinder;

  /**
   * The version guesser.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\VersionGuesser
   */
  private $versionGuesser;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Fixture\CloudHooksInstaller $cloud_hooks_installer
   *   The Cloud Hooks installer.
   * @param \Acquia\Orca\Domain\Fixture\CodebaseCreator $codebase_creator
   *   The codebase creator.
   * @param \Acquia\Orca\Domain\Composer\ComposerFacade $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper $composer_json_helper
   *   The fixture composer.json helper.
   * @param \Acquia\Orca\Domain\Fixture\Helper\DrupalSettingsHelper $drupal_settings_helper
   *   The Drupal settings helper.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Fixture\FixtureInspector $fixture_inspector
   *   The fixture inspector.
   * @param \Acquia\Orca\Domain\Git\GitFacade $git
   *   The Git facade.
   * @param \Acquia\Orca\Domain\Fixture\SiteInstaller $site_installer
   *   The site installer.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Domain\Fixture\SubextensionManager $subextension_manager
   *   The subextension manager.
   * @param \Acquia\Orca\Domain\Composer\Version\VersionFinder $version_finder
   *   The version finder.
   * @param \Acquia\Orca\Domain\Composer\Version\VersionGuesser $version_guesser
   *   The version guesser.
   */
  public function __construct(CloudHooksInstaller $cloud_hooks_installer, CodebaseCreator $codebase_creator, ComposerFacade $composer, ComposerJsonHelper $composer_json_helper, DrupalSettingsHelper $drupal_settings_helper, FixturePathHandler $fixture_path_handler, FixtureInspector $fixture_inspector, GitFacade $git, SiteInstaller $site_installer, SymfonyStyle $output, ProcessRunner $process_runner, PackageManager $package_manager, SubextensionManager $subextension_manager, VersionFinder $version_finder, VersionGuesser $version_guesser) {
    $this->cloudHooksInstaller = $cloud_hooks_installer;
    $this->codebaseCreator = $codebase_creator;
    $this->composer = $composer;
    $this->composerJsonHelper = $composer_json_helper;
    $this->drupalSettingsHelper = $drupal_settings_helper;
    $this->fixture = $fixture_path_handler;
    $this->fixtureInspector = $fixture_inspector;
    $this->git = $git;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->packageManager = $package_manager;
    $this->siteInstaller = $site_installer;
    $this->subextensionManager = $subextension_manager;
    $this->versionFinder = $version_finder;
    $this->versionGuesser = $version_guesser;
  }

  /**
   * Creates the fixture.
   *
   * @param \Acquia\Orca\Options\FixtureOptions $options
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Exception
   */
  public function create(FixtureOptions $options): void {
    $this->options = $options;
    $this->createComposerProject();
    $this->fixDefaultDependencies();
    $this->addCompanyPackages();
    $this->composer->updateLockFile();
    $this->installCloudHooks();
    $this->ensureDrupalSettings();
    $this->installSite();
    $this->setUpFilesDirectories();
    $this->createAndCheckoutBackupTag();
    $this->displayStatus();
  }

  /**
   * Creates a Composer project.
   */
  private function createComposerProject(): void {
    $this->output->section('Creating Composer project');
    $this->codebaseCreator->create($this->options);
  }

  /**
   * Fixes the default dependencies.
   */
  private function fixDefaultDependencies(): void {
    $this->output->section('Fixing default dependencies');

    // Remove unwanted packages.
    $packages = $this->getUnwantedPackageList();
    // The Lightning profile requirement conflicts with individual Lightning
    // component requirements--namely, it prevents them from being symlinked
    // via a local "path" repository.
    array_unshift($packages, 'acquia/lightning');
    $this->composer->removePackages($packages);

    $additions = [];

    if ($this->options->isDev()) {
      // Install the dev version of Drush.
      $additions[] = 'drush/drush:dev-master || 10.x-dev || 9.x-dev || 9.5.x-dev';
    }

    if ($this->shouldRequireDrupalConsole()) {
      // Add Drupal Console as a soft dependency akin to Drush.
      $drupal_console_version = '~1.0';
      if ($this->options->isDev()) {
        $drupal_console_version = 'dev-master';
      }
      $additions[] = "drupal/console:{$drupal_console_version}";
    }

    // Install a specific version of Drupal core.
    if ($this->options->getCore()) {
      $additions[] = "drupal/core:{$this->options->getCore()}";
    }

    if ($this->shouldRequireDrupalCoreDev()) {
      $additions[] = "drupal/core-dev:{$this->options->getCore()}";
    }

    if ($this->shouldRequireProphecyPhpunit()) {
      $additions[] = 'phpspec/prophecy-phpunit:^2';
    }

    if ($this->shouldDowngradePhpunit()) {
      $additions[] = 'phpunit/phpunit:9.4.3';
    }

    // Require additional packages.
    $prefer_source = $this->options->preferSource();
    $no_update = !$this->options->isBare();
    $this->composer->requirePackages($additions, $prefer_source, $no_update);
  }

  /**
   * Determines whether or not to require drupal/console.
   *
   * Only require it for Drupal 8. It's not compatible with Drupal 9 at the time
   * of this writing.
   *
   * @return bool
   *   Returns TRUE if it should be required, or FALSE if not.
   */
  private function shouldRequireDrupalConsole(): bool {
    $version = $this->options->getCoreResolved();
    return Comparator::lessThan($version, '9');
  }

  /**
   * Determines whether or not to require drupal/core-dev.
   *
   * Require it for Drupal 8.8 and later. (Before that BLT required
   * webflo/drupal-core-require-dev, which it supersedes.)
   *
   * @return bool
   *   Returns TRUE if it should be required, or FALSE if not.
   */
  private function shouldRequireDrupalCoreDev(): bool {
    $version = $this->options->getCoreResolved();
    return Comparator::greaterThanOrEqualTo($version, '8.8');
  }

  /**
   * Determines whether or not to require phpspec/prophecy-phpunit.
   *
   * @see https://www.drupal.org/node/3176567
   *
   * @return bool
   *   Returns TRUE if it should be required, or FALSE if not.
   */
  private function shouldRequireProphecyPhpunit(): bool {
    $parser = new VersionParser();
    $core = $this->options->getCoreResolved();

    $required = $parser->parseConstraints('^9.1.0');
    $actual = $parser->parseConstraints($core);

    return $required->matches($actual);
  }

  /**
   * Determines whether or not to downgrade PHPUnit.
   *
   * Workaround for "Call to undefined method ::getAnnotations()" error."
   *
   * @see https://www.drupal.org/project/drupal/issues/3186443
   *
   * @return bool
   *   Returns TRUE if it should be downgraded, or FALSE if not.
   */
  private function shouldDowngradePhpunit(): bool {
    $version = $this->options->getCoreResolved();
    return Comparator::equalTo($version, '9.1.0.0');
  }

  /**
   * Gets the list of unwanted packages.
   *
   * @return array
   *   The list of unwanted packages.
   */
  private function getUnwantedPackageList(): array {
    $packages = $this->packageManager->getAll();
    if ($this->options->isBare() || $this->options->isSutOnly()) {
      // Don't remove BLT because it won't be replaced in a bare or SUT-only
      // fixture, and a fixture cannot be successfully built without it.
      unset($packages['acquia/blt']);
    }
    return array_keys($packages);
  }

  /**
   * Adds company packages to the codebase.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   */
  private function addCompanyPackages(): void {
    if ($this->options->isBare()) {
      return;
    }

    $this->output->section('Adding company packages');
    $this->addTopLevelAcquiaPackages();
    $this->addCompanySubextensions();
    $this->git->commitCodeChanges('Added company packages.');
  }

  /**
   * Adds the top-level company packages to composer.json.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   */
  private function addTopLevelAcquiaPackages(): void {
    $this->addPathRepositories();
    $this->configureComposerForTopLevelCompanyPackages();
    $this->composerRequireTopLevelCompanyPackages();
    $this->verifySut();
  }

  /**
   * Adds Composer path repositories for company packages.
   */
  private function addPathRepositories(): void {
    if (!$this->options->hasSut() && !$this->options->symlinkAll()) {
      return;
    }

    foreach ($this->getLocalPackages() as $package) {
      // Only create repositories for packages that are present locally.
      if ($package !== $this->options->getSut() && !$this->shouldSymlinkNonSut($package)) {
        continue;
      }

      $this->composerJsonHelper->addRepository(
        $package->getPackageName(),
        'path',
        $this->fixture->getPath($package->getRepositoryUrlRaw())
      );
    }
  }

  /**
   * Gets all local packages.
   *
   * @return \Acquia\Orca\Domain\Package\Package[]
   *   An associative array of local package objects keyed by their package
   *   names.
   */
  private function getLocalPackages(): array {
    $packages = [];
    foreach ($this->packageManager->getAll() as $package_name => $package) {
      $is_sut = $package === $this->options->getSut();
      if (!$is_sut && !$this->options->symlinkAll()) {
        continue;
      }

      $packages[$package_name] = $package;
    }
    return $packages;
  }

  /**
   * Configures Composer to install company packages from source.
   */
  private function configureComposerForTopLevelCompanyPackages(): void {
    $packages = $this->packageManager->getAll();
    $this->composerJsonHelper->setPreferInstallFromSource(array_keys($packages));
  }

  /**
   * Requires the top-level company packages via Composer.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function composerRequireTopLevelCompanyPackages(): void {
    $packages = $this->getCompanyPackageDependencies();
    $prefer_source = $this->options->preferSource();
    $this->composer->requirePackages($packages, $prefer_source);
  }

  /**
   * Verifies that the SUT was correctly placed.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function verifySut(): void {
    if (!$this->options->hasSut()) {
      return;
    }

    /* @var \Acquia\Orca\Domain\Package\Package $sut */
    $sut = $this->options->getSut();

    $sut_install_path = $sut->getInstallPathAbsolute();
    if (!file_exists($sut_install_path)) {
      throw new OrcaException('Failed to place SUT at correct path.');
    }

    if (!$sut->shouldGetComposerRequired()) {
      return;
    }

    if (!is_link($sut_install_path)) {
      throw new OrcaException('Failed to symlink SUT via local path repository.');
    }
  }

  /**
   * Gets the list of Composer dependency strings for company packages.
   *
   * @return string[]
   *   The list of Composer dependency strings for company packages.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function getCompanyPackageDependencies(): array {
    $sut = $this->options->getSut();
    $dependencies = $this->getCompanyPackagesToRequire();

    if ($this->options->isSutOnly()) {
      $dependencies = [$sut];
    }

    $core = $this->options->getCoreResolved();

    foreach ($dependencies as $package_name => &$package) {
      // Omit packages that cannot be Composer required.
      if (!$package->shouldGetComposerRequired()) {
        unset($dependencies[$package_name]);
        continue;
      }

      // Omit packages without a version compatible with the version of Drupal
      // core being installed.
      $has_version = (bool) $package->getVersionRecommended($core);
      if ($this->options->isDev()) {
        $has_version = (bool) $package->getVersionDev($core);
      }
      if (!$has_version) {
        unset($dependencies[$package_name]);
      }

      // Always symlink a Composer requirable SUT.
      if ($package === $sut) {
        $package = $this->getLocalPackageString($package);
        continue;
      }

      // If configured to symlink all and package exists locally, symlink it.
      if ($this->shouldSymlinkNonSut($package)) {
        $package = $this->getLocalPackageString($package);
        continue;
      }

      // Otherwise use the latest installable version according to Composer.
      $version = $this->findLatestVersion($package);
      $package = "{$package->getPackageName()}:{$version}";
    }

    return array_values($dependencies);
  }

  /**
   * Gets the company packages to require with Composer.
   *
   * @return \Acquia\Orca\Domain\Package\Package[]|string[]
   *   The packages to require.
   */
  private function getCompanyPackagesToRequire(): array {
    if ($this->options->symlinkAll()) {
      return $this->getLocalPackages();
    }
    return $this->packageManager->getAll();
  }

  /**
   * Determines whether or not the given non-SUT package should be symlinked.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package in question.
   *
   * @return bool
   *   TRUE if the given package should be symlinked or FALSE if not.
   */
  private function shouldSymlinkNonSut(Package $package): bool {
    if (!$this->options->symlinkAll()) {
      return FALSE;
    }

    return $package->repositoryExists();
  }

  /**
   * Gets the target version for the given package.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package to get the target version for.
   *
   * @return string|null
   *   The target version if available or NULL if not.
   */
  private function getTargetVersion(Package $package): ?string {
    return ($this->options->isDev())
      ? $package->getVersionDev($this->options->getCore())
      : $package->getVersionRecommended($this->options->getCore());
  }

  /**
   * Finds the latest available version for a given package.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The package to get the latest version for.
   *
   * @return string
   *   The package for the latest version.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   In case no version can be found.
   */
  private function findLatestVersion(Package $package): string {
    $constraint = $this->getTargetVersion($package);
    if ($constraint === '*') {
      $constraint = NULL;
    }
    return $this->versionFinder
      ->findLatestVersion($package->getPackageName(), $constraint, $this->options->isDev());
  }

  /**
   * Gets the package string for a given local package..
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The local package.
   *
   * @return string
   *   The package string for the given package, e.g., "drupal/example:*".
   */
  private function getLocalPackageString(Package $package): string {
    return $package->getPackageName() . ':' . $this->getLocalPackageVersion($package);
  }

  /**
   * Gets the version of a given local package.
   *
   * @param \Acquia\Orca\Domain\Package\Package $package
   *   The local package.
   *
   * @return string
   *   The versions of the given package, e.g., "@dev" or "dev-8.x-1.x".
   */
  private function getLocalPackageVersion(Package $package): string {
    $path = $package->getRepositoryUrlAbsolute();
    return $this->versionGuesser->guessVersion($path);
  }

  /**
   * Adds company subextensions to the fixture.
   */
  private function addCompanySubextensions(): void {
    $this->configureComposerForLocalSubextensions();
    $this->composerRequireSubextensions();
  }

  /**
   * Configures Composer to find and place subextensions of local packages.
   */
  private function configureComposerForLocalSubextensions(): void {
    $this->addLocalSubextensionRepositories();
    $this->addInstallerPathsForLocalSubextensions();
  }

  /**
   * Adds Composer repositories for subextensions of local packages.
   */
  private function addLocalSubextensionRepositories(): void {
    foreach ($this->getLocalPackages() as $package) {
      foreach ($this->subextensionManager->getByParent($package) as $subextension) {
        $this->composerJsonHelper->addRepository(
          $subextension->getPackageName(),
          'path',
          $subextension->getRepositoryUrlRaw()
        );
      }
    }
  }

  /**
   * Adds installer-paths for subextensions of local packages.
   */
  private function addInstallerPathsForLocalSubextensions(): void {
    $package_names = $this->getLocalSubextensionPackageNames();
    $this->composerJsonHelper
      ->addInstallerPath('files-private/{$name}', $package_names);
  }

  /**
   * Gets all local subextension package names.
   *
   * @return string[]
   *   An indexed array of package names.
   */
  private function getLocalSubextensionPackageNames(): array {
    $package_names = [];
    foreach ($this->getLocalPackages() as $package) {
      $subextension_names = array_keys($this->subextensionManager->getByParent($package));
      $package_names = array_merge($package_names, $subextension_names);
    }
    return $package_names;
  }

  /**
   * Requires the company subextensions via Composer.
   */
  private function composerRequireSubextensions(): void {
    $subextensions = [];
    foreach ($this->packageManager->getAll() as $package) {
      // The Drupal.org Composer Facade only supports subextensions in modules
      // and themes.
      if (!in_array($package->getType(), ['drupal-module', 'drupal-theme'])) {
        continue;
      }

      $version = $this->fixtureInspector->getInstalledPackageVersionPretty($package->getPackageName());
      foreach (array_keys($this->subextensionManager->getByParent($package)) as $package_name) {
        $subextensions[] = "{$package_name}:{$version}";
      }
    }

    if (!$subextensions) {
      return;
    }

    asort($subextensions);
    $this->composer->requirePackages($subextensions);
  }

  /**
   * Installs Acquia Cloud Hooks.
   *
   * @see https://github.com/acquia/cloud-hooks#installing-cloud-hooks
   */
  private function installCloudHooks(): void {
    $this->output->section('Installing Cloud Hooks');
    $this->cloudHooksInstaller->install();
    $this->git->commitCodeChanges('Installed Cloud Hooks.');
  }

  /**
   * Ensures that Drupal is correctly configured.
   */
  protected function ensureDrupalSettings(): void {
    $this->output->section('Ensuring Drupal settings');
    $this->drupalSettingsHelper->ensureSettings($this->options);
    $this->git->commitCodeChanges('Ensured Drupal settings');
  }

  /**
   * Installs the site.
   *
   * Installs Drupal and enables company extensions.
   *
   * @throws \Exception
   */
  private function installSite(): void {
    if (!$this->options->installSite()) {
      return;
    }

    $this->output->section('Installing site');
    $this->siteInstaller->install($this->options->getProfile());
    $this->git->commitCodeChanges('Installed site.');
  }

  /**
   * Sets up the files directories.
   *
   * Ensures the existence of the uploaded files directories and sets
   * permissions on them.
   *
   * @see https://www.drupal.org/docs/7/install/setting-up-the-files-directory
   */
  private function setUpFilesDirectories(): void {
    $this->output->section('Setting up files directories');
    $directories = [
      $this->fixture->getPath('docroot/sites/all/files'),
      $this->fixture->getPath('docroot/sites/default/files'),
      $this->fixture->getPath('files-private'),
    ];
    $this->processRunner->runExecutable('mkdir', array_merge([
      '-p',
    ], $directories));
    $this->processRunner->runExecutable('chmod', array_merge([
      '-R',
      '0770',
    ], $directories));
  }

  /**
   * Creates and checks out a backup tag for the current state of the fixture.
   */
  private function createAndCheckoutBackupTag(): void {
    $this->output->section('Creating backup tag');
    $this->git->backupFixtureRepo();
  }

  /**
   * Displays the fixture status.
   */
  private function displayStatus(): void {
    $this->output->section('Fixture created:');
    (new StatusTable($this->output))
      ->setRows($this->fixtureInspector->getOverview())
      ->render();
  }

}
