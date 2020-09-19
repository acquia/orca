<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Console\Helper\StatusTable;
use Acquia\Orca\Domain\Composer\Composer;
use Acquia\Orca\Domain\Composer\Version\VersionFinder;
use Acquia\Orca\Domain\Composer\Version\VersionGuesser;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Git\Git;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\FixtureOptions;
use Composer\Semver\Comparator;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a fixture.
 */
class FixtureCreator {

  public const DEFAULT_PROFILE = 'orca';

  /**
   * The codebase creator.
   *
   * @var \Acquia\Orca\Domain\Fixture\CodebaseCreator
   */
  private $codebaseCreator;

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Domain\Composer\Composer
   */
  private $composer;

  /**
   * The fixture composer.json helper.
   *
   * @var \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper
   */
  private $composerJsonHelper;

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
   * @param \Acquia\Orca\Domain\Fixture\CodebaseCreator $codebase_creator
   *   The codebase creator.
   * @param \Acquia\Orca\Domain\Composer\Composer $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper $composer_json_helper
   *   The fixture composer.json helper.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Fixture\FixtureInspector $fixture_inspector
   *   The fixture inspector.
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
  public function __construct(CodebaseCreator $codebase_creator, Composer $composer, ComposerJsonHelper $composer_json_helper, FixturePathHandler $fixture_path_handler, FixtureInspector $fixture_inspector, SiteInstaller $site_installer, SymfonyStyle $output, ProcessRunner $process_runner, PackageManager $package_manager, SubextensionManager $subextension_manager, VersionFinder $version_finder, VersionGuesser $version_guesser) {
    $this->codebaseCreator = $codebase_creator;
    $this->composer = $composer;
    $this->composerJsonHelper = $composer_json_helper;
    $this->fixture = $fixture_path_handler;
    $this->fixtureInspector = $fixture_inspector;
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
    $fixture_path = $this->fixture->getPath();

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

    // Install requirements for deprecation checking.
    $additions[] = 'mglaman/phpstan-drupal-deprecations';
    $additions[] = 'nette/di:^3.0';

    // Require additional packages.
    $command = [
      'composer',
      'require',
    ];
    if ($this->options->preferSource()) {
      $command[] = '--prefer-source';
    }
    if (!$this->options->isBare()) {
      $command[] = '--no-update';
    }
    $command = array_merge($command, $additions);
    $this->processRunner->runOrcaVendorBin($command, $fixture_path);
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
    $this->commitCodeChanges('Added company packages.');
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
    $command = [
      'composer',
      'require',
      '--no-interaction',
    ];
    if ($this->options->preferSource()) {
      $command[] = '--prefer-source';
    }
    $command = array_merge($command, $this->getCompanyPackageDependencies());
    $this->processRunner->runOrcaVendorBin($command, $this->fixture->getPath());
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
      $this->displayFailedSymlinkDebuggingInfo();
      throw new OrcaException('Failed to symlink SUT via local path repository.');
    }
  }

  /**
   * Displays debugging info about a failure to symlink the SUT.
   */
  private function displayFailedSymlinkDebuggingInfo(): void {
    $this->output->section('Debugging info');
    /* @var Package $sut */
    $sut = $this->options->getSut();

    $this->output->comment('Display some info about the SUT install path.');
    $this->processRunner->runExecutable('stat', [
      $sut->getInstallPathAbsolute(),
    ]);

    $fixture_path = $this->fixture->getPath();

    $this->output->comment("See if Composer knows why it wasn't symlinked.");
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'why-not',
      $this->getLocalPackageString($sut),
    ], $fixture_path);

    $this->output->comment('See why Composer installed what it did.');
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'why',
      $sut->getPackageName(),
    ], $fixture_path);

    $this->output->comment('Display the Git branches in the path repo.');
    $this->processRunner->git([
      'branch',
    ], $sut->getRepositoryUrlAbsolute());

    $this->output->comment("Display the fixture's composer.json.");
    $this->processRunner->runExecutable('cat', [
      $this->fixture->getPath('composer.json'),
    ]);

    $this->output->comment("Display the SUT's composer.json.");
    $this->processRunner->runExecutable('cat', [
      "{$sut->getRepositoryUrlAbsolute()}/composer.json",
    ]);
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
    $dependencies = ($this->options->symlinkAll()) ? $this->getLocalPackages() : $this->packageManager->getAll();
    $sut = $this->options->getSut();
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
   * Ensures that Drupal is correctly configured.
   */
  protected function ensureDrupalSettings(): void {
    $this->output->section('Ensuring Drupal settings');
    $this->ensureCiSettingsFile();
    $this->ensureLocalSettingsFile();
    $this->commitCodeChanges('Ensured Drupal settings');
  }

  /**
   * Ensures that the CI settings file is correctly configured.
   */
  private function ensureCiSettingsFile(): void {
    $path = $this->fixture->getPath('docroot/sites/default/settings/ci.settings.php');

    $data = '<?php' . PHP_EOL . PHP_EOL;
    $data .= $this->getSettings();

    file_put_contents($path, $data, FILE_APPEND);
  }

  /**
   * Ensures that the local settings file is correctly configured.
   */
  private function ensureLocalSettingsFile(): void {
    $path = $this->fixture->getPath('docroot/sites/default/settings/local.settings.php');

    $id = '# ORCA settings.';

    // Return early if the settings are already present.
    if (strpos(file_get_contents($path), $id)) {
      return;
    }

    // Add the settings.
    $data = PHP_EOL . $id . PHP_EOL;
    $data .= $this->getSettings();
    file_put_contents($path, $data, FILE_APPEND);
  }

  /**
   * Gets the PHP code to add to the Drupal settings files.
   *
   * @return string
   *   A string of PHP code.
   */
  private function getSettings(): string {
    $data = '';

    if ($this->options->useSqlite()) {
      $data .= <<<'PHP'
$databases['default']['default']['database'] = dirname(DRUPAL_ROOT) . '/db.sqlite';
$databases['default']['default']['driver'] = 'sqlite';
unset($databases['default']['default']['namespace']);
PHP;
    }

    $data .= PHP_EOL . PHP_EOL . <<<'PHP'
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
    return $data . PHP_EOL;
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
    $this->processRunner->git([
      'tag',
      Git::FRESH_FIXTURE_TAG,
    ]);
    $this->processRunner->git([
      'checkout',
      Git::FRESH_FIXTURE_TAG,
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
