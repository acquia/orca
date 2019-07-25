<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\StatusTable;
use Acquia\Orca\Utility\SutSettingsTrait;
use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Composer\Package\Version\VersionGuesser;
use Noodlehaus\Config;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a fixture.
 */
class FixtureCreator {

  use SutSettingsTrait;

  const DEFAULT_PROFILE = 'minimal';

  /**
   * The Acquia extension enabler.
   *
   * @var \Acquia\Orca\Fixture\AcquiaExtensionEnabler
   */
  private $acquiaExtensionEnabler;

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
   * The fixture configurer.
   *
   * @var \Acquia\Orca\Fixture\FixtureConfigurer
   */
  private $fixtureConfigurer;

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
   * The subextension manager.
   *
   * @var \Acquia\Orca\Fixture\SubextensionManager
   */
  private $subextensionManager;

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
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\AcquiaExtensionEnabler $acquia_extension_enabler
   *   The Acquia extension enabler.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureConfigurer $fixture_configurer
   *   The fixture configurer.
   * @param \Acquia\Orca\Fixture\FixtureInspector $fixture_inspector
   *   The fixture inspector.
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
  public function __construct(AcquiaExtensionEnabler $acquia_extension_enabler, Fixture $fixture, FixtureConfigurer $fixture_configurer, FixtureInspector $fixture_inspector, SymfonyStyle $output, ProcessRunner $process_runner, PackageManager $package_manager, SubextensionManager $subextension_manager, VersionGuesser $version_guesser) {
    $this->acquiaExtensionEnabler = $acquia_extension_enabler;
    $this->fixture = $fixture;
    $this->fixtureConfigurer = $fixture_configurer;
    $this->fixtureInspector = $fixture_inspector;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->packageManager = $package_manager;
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
    $this->fixtureConfigurer->ensureGitConfig();
    $this->configureBltProject();
    $this->fixDefaultDependencies();
    $this->addAcquiaPackages();
    $this->addComposerExtraData();
    $this->installCloudHooks();
    $this->ensureDrupalSettings();
    $this->installDrupal();
    $this->setUpFilesDirectories();
    $this->enableAcquiaExtensions();
    $this->createAndCheckoutBackupTag();
    $this->fixtureConfigurer->removeTemporaryLocalGitConfig();
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
   *   The installation profile machine name, e.g., "minimal" or "lightning".
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
    $additions[] = 'drupal/console:~1.0';

    // Install a specific version of Drupal core.
    if ($this->drupalCoreVersion) {
      $additions[] = "drupal/core:{$this->drupalCoreVersion}";
    }

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
    $packages = $this->packageManager->getMultiple();
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
    $this->addSutRepository();
    $this->composerRequireTopLevelAcquiaPackages();
    $this->verifySut();
  }

  /**
   * Adds a Composer repository for the system under test.
   *
   * Repositories take precedence in the order specified (i.e., first match
   * found wins), so our override needs to be added to the beginning in order
   * to take effect.
   */
  private function addSutRepository(): void {
    if (!$this->sut) {
      return;
    }

    $this->loadComposerJson();

    // Remove original repositories.
    $this->jsonConfigSource->removeProperty('repositories');

    // Add new repository.
    $this->jsonConfigSource->addRepository($this->sut->getPackageName(), [
      'type' => 'path',
      'url' => $this->fixture->getPath($this->sut->getRepositoryUrl()),
    ]);

    // Append original repositories.
    foreach ($this->jsonConfigDataBackup['repositories'] as $key => $value) {
      $this->jsonConfigSource->addRepository($key, $value);
    }
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
   * Requires the top-level Acquia packages via Composer.
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
    $this->processRunner->runExecutable([
      'stat',
      $this->sut->getInstallPathAbsolute(),
    ]);

    $fixture_path = $this->fixture->getPath();

    // See if Composer knows why it wasn't symlinked.
    $this->processRunner->runOrcaVendorBin([
      'composer',
      'why-not',
      $this->getSutPackageString(),
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
    $this->processRunner->runExecutable([
      'cat',
      $this->fixture->getPath('composer.json'),
    ]);

    // Display the SUT's composer.json.
    $this->processRunner->runExecutable([
      'cat',
      $this->fixture->getPath("{$this->sut->getRepositoryUrl()}/composer.json"),
    ]);
  }

  /**
   * Gets the list of Composer dependency strings for Acquia packages.
   *
   * @return string[]
   *   The list of Composer dependency strings for Acquia packages.
   */
  private function getAcquiaPackageDependencies(): array {
    $dependencies = $this->packageManager->getMultiple();
    foreach ($dependencies as &$dependency) {
      $dependency = ($this->isDev) ? $dependency->getPackageStringDev() : $dependency->getPackageStringRecommended();
    }

    if (!$this->sut) {
      return array_values($dependencies);
    }

    $sut_package_string = $this->getSutPackageString();

    if ($this->isSutOnly) {
      return [$sut_package_string];
    }

    // Replace the version constraint on the SUT to allow for symlinking.
    $dependencies[$this->sut->getPackageName()] = $sut_package_string;

    return array_values($dependencies);
  }

  /**
   * Gets the package string for the SUT.
   *
   * @return string
   *   The package string for the SUT, e.g., "drupal/example:*".
   */
  private function getSutPackageString(): string {
    return "{$this->sut->getPackageName()}:{$this->getSutVersion()}";
  }

  /**
   * Gets the version of the SUT.
   *
   * @return string
   *   The versions of the SUT, e.g., "@dev" or "dev-8.x-1.x".
   */
  private function getSutVersion(): string {
    $path = $this->fixture->getPath($this->sut->getRepositoryUrl());
    $package_config = (array) new Config("{$path}/composer.json");
    $guess = $this->versionGuesser->guessVersion($package_config, $path);
    return (empty($guess['version'])) ? '@dev' : $guess['version'];
  }

  /**
   * Adds Acquia subextensions to the fixture.
   */
  private function addAcquiaSubextensions(): void {
    $this->configureComposerForSutSubextensions();
    $this->composerRequireSubextensions();
  }

  /**
   * Configures Composer to find and place subextensions of the SUT.
   */
  private function configureComposerForSutSubextensions(): void {
    if (!$this->sut || !$this->subextensionManager->getAll()) {
      return;
    }
    $this->loadComposerJson();
    $this->addSutSubextensionRepositories();
    $this->addInstallerPathsForSutSubextensions();
  }

  /**
   * Adds Composer repositories for subextensions of the SUT.
   *
   * Repositories take precedence in the order specified (i.e., first match
   * found wins), so our override needs to be added to the beginning in order
   * to take effect.
   */
  private function addSutSubextensionRepositories(): void {
    // Remove original repositories.
    $this->jsonConfigSource->removeProperty('repositories');

    // Add new repositories.
    foreach ($this->subextensionManager->getByParent($this->sut) as $package) {
      $this->jsonConfigSource->addRepository($package->getPackageName(), [
        'type' => 'path',
        'url' => $package->getRepositoryUrl(),
      ]);
    }

    // Append original repositories.
    foreach ($this->jsonConfigDataBackup['repositories'] as $key => $value) {
      $this->jsonConfigSource->addRepository($key, $value);
    }
  }

  /**
   * Adds installer-paths for subextensions of the SUT.
   */
  private function addInstallerPathsForSutSubextensions(): void {
    $package_names = array_keys($this->subextensionManager->getByParent($this->sut));

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
   * Requires the Acquia subextensions via Composer.
   */
  private function composerRequireSubextensions(): void {
    $subextensions = [];
    foreach ($this->packageManager->getMultiple() as $package) {
      // The Drupal.org Composer Facade only supports subextensions in modules
      // and themes.
      if (!in_array($package->getType(), ['drupal-module', 'drupal-theme'])) {
        continue;
      }

      $version = $package->getVersionRecommended();
      if ($this->isDev) {
        $version = $package->getVersionDev();
      }
      if ($this->sut && $package->getPackageName() === $this->sut->getPackageName()) {
        $version = $this->getSutVersion();
      }
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
    $this->processRunner->git(['add', '--all'], $this->fixture->getPath());
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
    $this->processRunner->runExecutable([
      'curl',
      '-L',
      '-o',
      $tarball,
      'https://github.com/acquia/cloud-hooks/tarball/master',
    ], $cwd);
    $this->processRunner->runExecutable([
      'tar',
      'xzf',
      $tarball,
    ], $cwd);
    $this->processRunner->runExecutable([
      'rm',
      $tarball,
    ], $cwd);

    $directory = glob($this->fixture->getPath('acquia-cloud-hooks-*'))[0];
    $this->processRunner->runExecutable([
      'mv',
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
PHP;
    file_put_contents($filename, $data, FILE_APPEND);
    $this->commitCodeChanges('Ensured Drupal settings');
  }

  /**
   * Installs Drupal.
   */
  private function installDrupal(): void {
    if (!$this->installSite) {
      return;
    }

    $this->output->section('Installing Drupal');
    $this->processRunner->runFixtureVendorBin([
      'drush',
      'site:install',
      $this->profile,
      "install_configure_form.update_status_module='[FALSE,FALSE]'",
      'install_configure_form.enable_update_status_module=NULL',
      '--site-name=ORCA',
      '--account-name=admin',
      '--account-pass=admin',
      '--no-interaction',
      '--verbose',
      '--ansi',
    ]);
    $this->commitCodeChanges('Installed Drupal.');
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
    $this->processRunner->runExecutable(array_merge([
      'mkdir',
      '-p',
    ], $directories));
    $this->processRunner->runExecutable(array_merge([
      'chmod',
      '-R',
      '0770',
    ], $directories));
  }

  /**
   * Enables the Acquia Drupal extensions.
   *
   * @throws \Exception
   */
  private function enableAcquiaExtensions(): void {
    if (!$this->installSite) {
      return;
    }

    if ($this->isBare) {
      return;
    }

    if ($this->isSutOnly && !$this->sut->isDrupalExtension()) {
      // No extensions to enable because the fixture is SUT-only and the SUT is
      // not a Drupal extension.
      return;
    }

    $this->acquiaExtensionEnabler->enable();
    $this->commitCodeChanges('Enabled Acquia extensions.');
  }

  /**
   * Creates and checks out a backup tag for the current state of the fixture.
   */
  private function createAndCheckoutBackupTag(): void {
    $this->output->section('Creating backup tag');
    $fixture_path = $this->fixture->getPath();
    $this->processRunner->git([
      'tag',
      Fixture::FRESH_FIXTURE_GIT_TAG,
    ], $fixture_path);
    $this->processRunner->git([
      'checkout',
      Fixture::FRESH_FIXTURE_GIT_TAG,
    ], $fixture_path);
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
