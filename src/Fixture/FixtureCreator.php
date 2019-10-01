<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\StatusTable;
use Acquia\Orca\Utility\SutSettingsTrait;
use Composer\Config\JsonConfigSource;
use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionGuesser;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\RepositoryFactory;
use Noodlehaus\Config;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates a fixture.
 */
class FixtureCreator {

  use SutSettingsTrait;

  const DEFAULT_PROFILE = 'orca';

  /**
   * The Composer exit on patch failure flag.
   *
   * @var bool
   */
  private $composerExitOnPatchFailure = TRUE;

  /**
   * The Drupal core version override.
   *
   * @var string|null
   */
  private $drupalCoreVersion;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The fixture configurator.
   *
   * @var \Acquia\Orca\Fixture\FixtureConfigurator
   */
  private $fixtureConfigurator;

  /**
   * The fixture inspector.
   *
   * @var \Acquia\Orca\Fixture\FixtureInspector
   */
  private $fixtureInspector;

  /**
   * The install site flag.
   *
   * @var bool
   */
  private $installSite = TRUE;

  /**
   * The bare flag.
   *
   * @var bool
   */
  private $isBare = FALSE;

  /**
   * The dev flag.
   *
   * @var bool
   */
  private $isDev = FALSE;

  /**
   * The Composer API for the fixture's composer.json.
   *
   * @var \Composer\Config\JsonConfigSource|null
   */
  private $jsonConfigSource;

  /**
   * A backup of the fixture's composer.json data before making changes.
   *
   * @var array
   */
  private $jsonConfigDataBackup = [];

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private $output;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * The prefer source flag.
   *
   * @var bool
   */
  private $preferSource = FALSE;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The installation profile.
   *
   * @var string
   */
  private $profile = self::DEFAULT_PROFILE;

  /**
   * The site installer.
   *
   * @var \Acquia\Orca\Fixture\SiteInstaller
   */
  private $siteInstaller;

  /**
   * The subextension manager.
   *
   * @var \Acquia\Orca\Fixture\SubextensionManager
   */
  private $subextensionManager;

  /**
   * The symlink all flag.
   *
   * @var bool
   */
  private $symlinkAll = FALSE;

  /**
   * The SQLite flag.
   *
   * @var bool
   */
  private $useSqlite = TRUE;

  /**
   * The Composer version guesser.
   *
   * @var \Composer\Package\Version\VersionGuesser
   */
  private $versionGuesser;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureConfigurator $fixture_configurator
   *   The fixture configurator.
   * @param \Acquia\Orca\Fixture\FixtureInspector $fixture_inspector
   *   The fixture inspector.
   * @param \Acquia\Orca\Fixture\SiteInstaller $site_installer
   *   The site installer.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   * @param \Acquia\Orca\Fixture\SubextensionManager $subextension_manager
   *   The subextension manager.
   * @param \Composer\Package\Version\VersionGuesser $version_guesser
   *   The Composer version guesser.
   */
  public function __construct(Filesystem $filesystem, Fixture $fixture, FixtureConfigurator $fixture_configurator, FixtureInspector $fixture_inspector, SiteInstaller $site_installer, SymfonyStyle $output, ProcessRunner $process_runner, PackageManager $package_manager, SubextensionManager $subextension_manager, VersionGuesser $version_guesser) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture;
    $this->fixtureConfigurator = $fixture_configurator;
    $this->fixtureInspector = $fixture_inspector;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->packageManager = $package_manager;
    $this->siteInstaller = $site_installer;
    $this->subextensionManager = $subextension_manager;
    $this->versionGuesser = $version_guesser;
  }

  /**
   * Creates the fixture.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   * @throws \Exception
   *   In case of errors.
   */
  public function create(): void {
    $this->createBltProject();
    $this->fixtureConfigurator->ensureGitConfig();
    $this->configureBltProject();
    $this->fixDefaultDependencies();
    $this->addAcquiaPackages();
    $this->addComposerExtraData();
    $this->installCloudHooks();
    $this->ensureDrupalSettings();
    $this->installSite();
    $this->setUpFilesDirectories();
    $this->createAndCheckoutBackupTag();
    $this->fixtureConfigurator->removeTemporaryLocalGitConfig();
    $this->displayStatus();
  }

  /**
   * Sets the bare flag.
   *
   * @param bool $is_bare
   *   TRUE for bare or FALSE otherwise.
   */
  public function setBare(bool $is_bare): void {
    $this->isBare = $is_bare;
  }

  /**
   * Sets the Composer exit on patch failure flag.
   *
   * @param bool $exit
   *   TRUE to exit on Composer patch failure or FALSE not to.
   */
  public function setComposerExitOnPatchFailure(bool $exit): void {
    $this->composerExitOnPatchFailure = $exit;
  }

  /**
   * Sets the Drupal core version to install.
   *
   * @param string $version
   *   The version string, e.g., "8.6.0".
   */
  public function setCoreVersion(string $version): void {
    $this->drupalCoreVersion = $version;
  }

  /**
   * Sets the dev flag.
   *
   * @param bool $is_dev
   *   TRUE for dev or FALSE for not.
   */
  public function setDev(bool $is_dev): void {
    $this->isDev = $is_dev;
  }

  /**
   * Sets the install site flag.
   *
   * @param bool $install_site
   *   TRUE to install the site or FALSE not to.
   */
  public function setInstallSite(bool $install_site): void {
    $this->installSite = $install_site;
  }

  /**
   * Sets the prefer source flag.
   *
   * @param bool $prefer_source
   *   TRUE to prefer source, or FALSE not to.
   */
  public function setPreferSource(bool $prefer_source): void {
    $this->preferSource = $prefer_source;
  }

  /**
   * Sets the installation profile.
   *
   * @param string $profile
   *   The installation profile machine name, e.g., "minimal".
   */
  public function setProfile(string $profile): void {
    $this->profile = $profile;
  }

  /**
   * Sets the SQLite flag.
   *
   * @param bool $use_sqlite
   *   TRUE to use SQLite or FALSE not to.
   */
  public function setSqlite(bool $use_sqlite): void {
    $this->useSqlite = $use_sqlite;
  }

  /**
   * Sets the symlink all flag.
   *
   * @param bool $symlink_all
   *   TRUE to symlink all Acquia packages or FALSE not to.
   */
  public function setSymlinkAll(bool $symlink_all): void {
    $this->symlinkAll = $symlink_all;
  }

  /**
   * Creates a BLT project.
   */
  private function createBltProject(): void {
    $this->output->section('Creating BLT project');
    $command = [
      'composer',
      'create-project',
      '--no-dev',
      '--no-scripts',
      '--no-install',
      '--no-interaction',
      'acquia/blt-project',
      $this->fixture->getPath(),
    ];
    if ($this->sut === 'acquia/blt' || $this->isDev) {
      $command[] = '--stability=dev';
    }
    $this->processRunner->runOrcaVendorBin($command);
  }

  /**
   * Configures the BLT project.
   */
  private function configureBltProject(): void {
    $this->loadComposerJson();

    // Prevent errors later because "Source directory docroot/core has
    // uncommitted changes" after "Removing package drupal/core so that it can
    // be re-installed and re-patched".
    // @see https://drupal.stackexchange.com/questions/273859
    $this->jsonConfigSource->addConfigSetting('discard-changes', TRUE);

    $this->jsonConfigSource->addProperty('extra.composer-exit-on-patch-failure', $this->composerExitOnPatchFailure);
  }

  /**
   * Loads the fixture's composer.json data.
   */
  private function loadComposerJson(): void {
    $json_file = new JsonFile($this->fixture->getPath('composer.json'));
    $this->jsonConfigDataBackup = $json_file->read();
    $this->jsonConfigSource = new JsonConfigSource($json_file);
  }

  /**
   * Fixes the default dependencies.
   */
  private function fixDefaultDependencies(): void {
    $this->output->section('Fixing default dependencies');
    $fixture_path = $this->fixture->getPath();

    // Remove unwanted packages.
    $this->processRunner->runOrcaVendorBin(array_merge(
      [
        'composer',
        'remove',
        '--no-update',
        // The Lightning profile requirement conflicts with individual Lightning
        // component requirements--namely, it prevents them from being symlinked
        // via a local "path" repository.
        'acquia/lightning',
      ],
      // Other Acquia packages are only conditionally required later and should
      // in no case be included up-front.
      $this->getUnwantedPackageList()
    ), $fixture_path);

    $additions = [];

    // Install the dev version of Drush.
    if ($this->isDev) {
      $additions[] = 'drush/drush:dev-master || 10.x-dev || 9.x-dev || 9.5.x-dev';
    }

    // Add Drupal Console as a soft dependency akin to Drush.
    $drupal_console_version = '~1.0';
    if ($this->isDev) {
      $drupal_console_version = 'dev-master';
    }
    $additions[] = "drupal/console:{$drupal_console_version}";

    // Install a specific version of Drupal core.
    if ($this->drupalCoreVersion) {
      $additions[] = "drupal/core:{$this->drupalCoreVersion}";
    }

    // Install requirements for deprecation checking.
    $additions[] = 'mglaman/phpstan-drupal-deprecations';
    $additions[] = 'nette/di:^3.0';

    // Require additional packages.
    $command = [
      'composer',
      'require',
    ];
    if ($this->preferSource) {
      $command[] = '--prefer-source';
    }
    if (!$this->isBare) {
      $command[] = '--no-update';
    }
    $command = array_merge($command, $additions);
    $this->processRunner->runOrcaVendorBin($command, $fixture_path);
  }

  /**
   * Gets the list of unwanted packages.
   *
   * @return array
   *   The list of unwanted packages.
   */
  private function getUnwantedPackageList(): array {
    $packages = $this->packageManager->getAll();
    if ($this->isBare || $this->isSutOnly) {
      // Don't remove BLT because it won't be replaced in a bare or SUT-only
      // fixture, and a fixture cannot be successfully built without it.
      unset($packages['acquia/blt']);
    }
    return array_keys($packages);
  }

  /**
   * Adds Acquia packages to the codebase.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   */
  private function addAcquiaPackages(): void {
    if ($this->isBare) {
      return;
    }

    $this->output->section('Adding Acquia packages');
    $this->addTopLevelAcquiaPackages();
    $this->addAcquiaSubextensions();
    $this->commitCodeChanges('Added Acquia packages.');
  }

  /**
   * Adds the top-level Acquia packages to composer.json.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   */
  private function addTopLevelAcquiaPackages(): void {
    $this->addPathRepositories();
    $this->configureComposerForTopLevelAcquiaPackages();
    $this->composerRequireTopLevelAcquiaPackages();
    $this->verifySut();
  }

  /**
   * Adds Composer path repositories for Acquia packages.
   *
   * Repositories take precedence in the order specified (i.e., first match
   * found wins), so our overrides need to be added to the beginning in order
   * to take effect.
   */
  private function addPathRepositories(): void {
    if (!$this->sut && !$this->symlinkAll) {
      return;
    }

    // Remove existing repositories.
    $this->loadComposerJson();
    $this->jsonConfigSource->removeProperty('repositories');

    foreach ($this->getLocalPackages() as $package) {
      $this->jsonConfigSource->addRepository($package->getPackageName(), [
        'type' => 'path',
        'url' => $this->fixture->getPath($package->getRepositoryUrl()),
      ]);
    }

    // Append original repositories.
    foreach ($this->jsonConfigDataBackup['repositories'] as $key => $value) {
      $this->jsonConfigSource->addRepository($key, $value);
    }
  }

  /**
   * Gets all local packages.
   *
   * @return \Acquia\Orca\Fixture\Package[]
   *   An associative array of local package objects keyed by their package
   *   names.
   */
  private function getLocalPackages(): array {
    $packages = [];
    foreach ($this->packageManager->getAll() as $package_name => $package) {
      $is_sut = $package == $this->sut;
      if (!$is_sut && !$this->symlinkAll) {
        continue;
      }

      $packages[$package_name] = $package;
    }
    return $packages;
  }

  /**
   * Adds data about the fixture to the "extra" property.
   */
  private function addComposerExtraData(): void {
    $this->jsonConfigSource->addProperty('extra.orca', [
      'sut' => ($this->sut) ? $this->sut->getPackageName() : NULL,
      'is-sut-only' => $this->isSutOnly,
      'is-bare' => $this->isBare,
      'is-dev' => $this->isDev,
    ]);
  }

  /**
   * Configures Composer to install Acquia packages from source.
   */
  private function configureComposerForTopLevelAcquiaPackages(): void {
    $packages = $this->packageManager->getAll();

    if (!$packages) {
      return;
    }

    // The preferred-install patterns are applied in the order specified, so
    // overrides need to be added to the beginning in order to take effect.
    // @see https://getcomposer.org/doc/06-config.md#preferred-install
    // Begin by removing the original installer paths.
    $this->jsonConfigSource->removeConfigSetting('preferred-install');

    $patterns = array_fill_keys(array_keys($packages), 'source');
    $this->jsonConfigSource->addConfigSetting('preferred-install', $patterns);

    if (empty($this->jsonConfigDataBackup['config']['preferred-install'])) {
      return;
    }

    // Append original patterns.
    foreach ($this->jsonConfigDataBackup['config']['preferred-install'] as $key => $value) {
      if (array_key_exists($key, $patterns)) {
        continue;
      }
      $this->jsonConfigSource->addConfigSetting("preferred-install.{$key}", $value);
    }
  }

  /**
   * Requires the top-level Acquia packages via Composer.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function composerRequireTopLevelAcquiaPackages(): void {
    $command = [
      'composer',
      'require',
      '--no-interaction',
    ];
    if ($this->preferSource) {
      $command[] = '--prefer-source';
    }
    $command = array_merge($command, $this->getAcquiaPackageDependencies());
    $this->processRunner->runOrcaVendorBin($command, $this->fixture->getPath());
  }

  /**
   * Verifies that the SUT was correctly placed.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function verifySut(): void {
    if (!$this->sut) {
      return;
    }

    $sut_install_path = $this->sut->getInstallPathAbsolute();
    if (!file_exists($sut_install_path)) {
      throw new OrcaException('Failed to place SUT at correct path.');
    }
    elseif (!is_link($sut_install_path)) {
      $this->displayFailedSymlinkDebuggingInfo();
      throw new OrcaException('Failed to symlink SUT via local path repository.');
    }
  }

  /**
   * Displays debugging info about a failure to symlink the SUT.
   */
  private function displayFailedSymlinkDebuggingInfo() {
    $this->output->section('Debugging info');

    // Display some info about the SUT install path.
    $this->processRunner->runExecutable('stat', [
      $this->sut->getInstallPathAbsolute(),
    ]);

    $fixture_path = $this->fixture->getPath();

    // See if Composer knows why it wasn't symlinked.
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'why-not',
      $this->getLocalPackageString($this->sut),
    ], $fixture_path);

    // See why Composer installed what it did.
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'why',
      $this->sut->getPackageName(),
    ], $fixture_path);

    // Display the Git branches in the path repo.
    $this->processRunner->git([
      'branch',
    ], $this->sut->getRepositoryUrl());

    // Display the fixture's composer.json.
    $this->processRunner->runExecutable('cat', [
      $this->fixture->getPath('composer.json'),
    ]);

    // Display the SUT's composer.json.
    $this->processRunner->runExecutable('cat', [
      $this->fixture->getPath("{$this->sut->getRepositoryUrl()}/composer.json"),
    ]);
  }

  /**
   * Gets the list of Composer dependency strings for Acquia packages.
   *
   * @return string[]
   *   The list of Composer dependency strings for Acquia packages.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function getAcquiaPackageDependencies(): array {
    $dependencies = ($this->symlinkAll) ? $this->getLocalPackages() : $this->packageManager->getAll();
    if ($this->isSutOnly) {
      $dependencies = [$this->sut];
    }
    foreach ($dependencies as $package_name => &$package) {
      // Always symlink the SUT.
      if ($package == $this->sut) {
        $package = $this->getLocalPackageString($package);
        continue;
      }

      // Omit packages that are non-installable per their specification.
      $package_is_installable = $this->getTargetVersion($package);
      if (!$package_is_installable) {
        unset($dependencies[$package_name]);
        continue;
      }

      // If configured to symlink all and package exists locally, symlink it.
      if ($this->shouldSymlinkNonSut($package)) {
        $package = $this->getLocalPackageString($package);
        continue;
      }

      // Otherwise use the latest installable version according to Composer.
      $version = $this->findLatestVersion($package)->getPrettyVersion();
      $package = "{$package->getPackageName()}:{$version}";
    }

    return array_values($dependencies);
  }

  /**
   * Determines whether or not the given non-SUT package should be symlinked.
   *
   * @param \Acquia\Orca\Fixture\Package $package
   *   The package in question.
   *
   * @return bool
   *   TRUE if the given package should be symlinked or FALSE if not.
   */
  private function shouldSymlinkNonSut(Package $package): bool {
    if (!$this->symlinkAll) {
      return FALSE;
    }

    $path = $this->fixture->getPath($package->getRepositoryUrl());
    return $this->filesystem->exists($path);
  }

  /**
   * Gets the target version for the given package.
   *
   * @param \Acquia\Orca\Fixture\Package $package
   *   The package to get the target version for.
   *
   * @return string|null
   *   The target version if available or NULL if not.
   */
  private function getTargetVersion(Package $package): ?string {
    return ($this->isDev)
      ? $package->getVersionDev($this->drupalCoreVersion)
      : $package->getVersionRecommended($this->drupalCoreVersion);
  }

  /**
   * Finds the latest available version for a given package.
   *
   * @param \Acquia\Orca\Fixture\Package $package
   *   The package to get the latest version for.
   *
   * @return \Composer\Package\PackageInterface
   *   The package for the latest version.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   In case no version can be found.
   */
  private function findLatestVersion(Package $package): PackageInterface {
    $io = new NullIO();
    $packagist = RepositoryFactory::defaultRepos($io)['packagist.org'];
    $drupal_org = RepositoryFactory::createRepo($io, Factory::createConfig($io), [
      'type' => 'composer',
      'url' => 'https://packages.drupal.org/8',
    ]);

    $stability = ($this->isDev) ? 'dev' : 'alpha';

    $pool = new Pool($stability);
    $pool->addRepository($packagist);
    $pool->addRepository($drupal_org);

    $target_version = $this->getTargetVersion($package);
    if ($target_version === '*') {
      $target_version = NULL;
    }
    $version = (new VersionSelector($pool))
      ->findBestCandidate($package->getPackageName(), $target_version, NULL, $stability);

    if (!$version) {
      throw new OrcaException(sprintf('No available version could be found for %s:"%s"', $package->getPackageName(), $target_version));
    }

    return $version;
  }

  /**
   * Gets the package string for a given local package..
   *
   * @param \Acquia\Orca\Fixture\Package $package
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
   * @param \Acquia\Orca\Fixture\Package $package
   *   The local package.
   *
   * @return string
   *   The versions of the given package, e.g., "@dev" or "dev-8.x-1.x".
   */
  private function getLocalPackageVersion(Package $package): string {
    $path = $this->fixture->getPath($package->getRepositoryUrl());
    $package_config = (array) new Config("{$path}/composer.json");
    $guess = $this->versionGuesser->guessVersion($package_config, $path);
    return (empty($guess['version'])) ? '@dev' : $guess['version'];
  }

  /**
   * Adds Acquia subextensions to the fixture.
   */
  private function addAcquiaSubextensions(): void {
    $this->configureComposerForLocalSubextensions();
    $this->composerRequireSubextensions();
  }

  /**
   * Configures Composer to find and place subextensions of local packages.
   */
  private function configureComposerForLocalSubextensions(): void {
    $this->loadComposerJson();
    $this->addLocalSubextensionRepositories();
    $this->addInstallerPathsForLocalSubextensions();
  }

  /**
   * Adds Composer repositories for subextensions of local packages.
   *
   * Repositories take precedence in the order specified (i.e., first match
   * found wins), so our override needs to be added to the beginning in order
   * to take effect.
   */
  private function addLocalSubextensionRepositories(): void {
    // Remove original repositories.
    $this->jsonConfigSource->removeProperty('repositories');

    // Add new repositories.
    foreach ($this->getLocalPackages() as $package) {
      foreach ($this->subextensionManager->getByParent($package) as $subextension) {
        $this->jsonConfigSource->addRepository($subextension->getPackageName(), [
          'type' => 'path',
          'url' => $subextension->getRepositoryUrl(),
        ]);
      }
    }

    // Append original repositories.
    foreach ($this->jsonConfigDataBackup['repositories'] as $key => $value) {
      $this->jsonConfigSource->addRepository($key, $value);
    }
  }

  /**
   * Adds installer-paths for subextensions of local packages.
   */
  private function addInstallerPathsForLocalSubextensions(): void {
    $package_names = $this->getLocalSubextensionPackageNames();

    if (!$package_names) {
      return;
    }

    // Installer paths seem to be applied in the order specified, so overrides
    // need to be added to the beginning in order to take effect. Begin by
    // removing the original installer paths.
    $this->jsonConfigSource->removeProperty('extra.installer-paths');

    // Add new installer paths.
    // Subextensions are implicitly installed with their parent modules, and
    // Composer won't allow them to be placed in the same location via their
    // separate packages. Neither will it allow them to be "installed" outside
    // the repository, in the system temp directory or /dev/null, for example.
    // In the absence of a better option, the private files directory provides a
    // convenient destination that Git is already configured to ignore.
    $path = 'extra.installer-paths.files-private/{$name}';
    $this->jsonConfigSource->addProperty($path, $package_names);

    // Append original installer paths.
    foreach ($this->jsonConfigDataBackup['extra']['installer-paths'] as $key => $value) {
      $this->jsonConfigSource->addProperty("extra.installer-paths.{$key}", $value);
    }
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
   * Requires the Acquia subextensions via Composer.
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
    asort($subextensions);
    $this->processRunner->runOrcaVendorBin(array_merge([
      'composer',
      'require',
      '--no-interaction',
    ], $subextensions), $this->fixture->getPath());
  }

  /**
   * Commits code changes made to the build directory.
   *
   * @param string $message
   *   The commit message to use.
   */
  private function commitCodeChanges($message): void {
    $this->processRunner->git(['add', '--all']);
    $this->processRunner->gitCommit($message);
  }

  /**
   * Installs Acquia Cloud Hooks.
   *
   * @see https://github.com/acquia/cloud-hooks#installing-cloud-hooks
   */
  private function installCloudHooks(): void {
    $this->output->section('Installing Cloud Hooks');
    $cwd = $this->fixture->getPath();

    $tarball = 'hooks.tar.gz';
    $this->processRunner->runExecutable('curl', [
      '-L',
      '-o',
      $tarball,
      'https://github.com/acquia/cloud-hooks/tarball/master',
    ], $cwd);
    $this->processRunner->runExecutable('tar', [
      'xzf',
      $tarball,
    ], $cwd);
    $this->processRunner->runExecutable('rm', [
      $tarball,
    ], $cwd);

    $directory = glob($this->fixture->getPath('acquia-cloud-hooks-*'))[0];
    $this->processRunner->runExecutable('mv', [
      $directory,
      'hooks',
    ], $cwd);

    $this->commitCodeChanges('Installed Cloud Hooks.');
  }

  /**
   * Ensure that Drupal is correctly configured.
   */
  protected function ensureDrupalSettings(): void {
    $this->output->section('Ensuring Drupal settings');
    $filename = $this->fixture->getPath('docroot/sites/default/settings/local.settings.php');
    $id = '# ORCA settings.';

    // Return early if the settings are already present.
    if (strpos(file_get_contents($filename), $id)) {
      return;
    }

    // Add the settings.
    $data = "\n{$id}\n";
    if ($this->useSqlite) {
      $data .= <<<'PHP'
$databases['default']['default']['database'] = dirname(DRUPAL_ROOT) . '/db.sqlite';
$databases['default']['default']['driver'] = 'sqlite';
unset($databases['default']['default']['namespace']);

PHP;
    }
    $data .= <<<'PHP'
// Override the definition of the service container used during Drupal's
// bootstrapping process. This is needed so that the core db-tools.php script
// can import database dumps properly. Without this, the destination database
// will get a cache_container table created in it before the import begins,
// which will cause the import to fail because it will think that Drupal is
// already installed.
// @see \Drupal\Core\DrupalKernel::$defaultBootstrapContainerDefinition
// @see https://www.drupal.org/project/drupal/issues/3006038
$settings['bootstrap_container_definition'] = [
  'parameters' => [],
  'services' => [
    'database' => [
      'class' => 'Drupal\Core\Database\Connection',
      'factory' => 'Drupal\Core\Database\Database::getConnection',
      'arguments' => ['default'],
    ],
    'cache.container' => [
      'class' => 'Drupal\Core\Cache\MemoryBackend',
    ],
    'cache_tags_provider.container' => [
      'class' => 'Drupal\Core\Cache\DatabaseCacheTagsChecksum',
      'arguments' => ['@database'],
    ],
  ],
];

// Change the config cache to use a memory backend to prevent SQLite "too many
// SQL variables" errors.
// @see https://www.drupal.org/project/drupal/issues/2031261
$settings['cache']['bins']['config'] = 'cache.backend.memory';
PHP;
    file_put_contents($filename, $data, FILE_APPEND);
    $this->commitCodeChanges('Ensured Drupal settings');
  }

  /**
   * Installs the site.
   *
   * Installs Drupal and enables Acquia extensions.
   *
   * @throws \Exception
   */
  private function installSite(): void {
    if (!$this->installSite) {
      return;
    }

    $this->output->section('Installing site');
    $this->siteInstaller->install($this->profile);
    $this->commitCodeChanges('Installed site.');
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
      $this->fixture->getPath('sites/all/files'),
      $this->fixture->getPath('sites/default/files'),
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
    $this->processRunner->git([
      'tag',
      Fixture::FRESH_FIXTURE_GIT_TAG,
    ]);
    $this->processRunner->git([
      'checkout',
      Fixture::FRESH_FIXTURE_GIT_TAG,
    ]);
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
