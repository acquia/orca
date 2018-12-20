<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Utility\ProcessRunner;
use Acquia\Orca\Utility\SutSettingsTrait;
use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Creates a fixture.
 */
class FixtureCreator {

  use SutSettingsTrait;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The finder.
   *
   * @var \Symfony\Component\Finder\Finder
   */
  private $finder;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

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
   * The process runner.
   *
   * @var \Acquia\Orca\Utility\ProcessRunner
   */
  private $processRunner;

  /**
   * The project manager.
   *
   * @var \Acquia\Orca\Fixture\ProjectManager
   */
  private $projectManager;

  /**
   * The submodule manager.
   *
   * @var \Acquia\Orca\Fixture\SubmoduleManager
   */
  private $submoduleManager;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Symfony\Component\Finder\Finder $finder
   *   The finder.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\ProjectManager $project_manager
   *   The project manager.
   * @param \Acquia\Orca\Fixture\SubmoduleManager $submodule_manager
   *   The submodule manager.
   */
  public function __construct(Filesystem $filesystem, Finder $finder, Fixture $fixture, SymfonyStyle $output, ProcessRunner $process_runner, ProjectManager $project_manager, SubmoduleManager $submodule_manager) {
    $this->filesystem = $filesystem;
    $this->finder = $finder;
    $this->fixture = $fixture;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->projectManager = $project_manager;
    $this->submoduleManager = $submodule_manager;
  }

  /**
   * Creates the fixture.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   */
  public function create(): void {
    $this->ensurePreconditions();
    $this->createBltProject();
    $this->removeUnneededProjects();
    $this->addAcquiaProjects();
    $this->installDrupal();
    $this->installAcquiaModules();
    $this->createBackupBranch();
    $this->output->success('Fixture created');
  }

  /**
   * Ensures that the preconditions for creating the fixture are satisfied.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the preconditions are not met.
   */
  private function ensurePreconditions() {
    $this->output->section('Checking preconditions');

    if ($this->sut) {
      $sut_repo = $this->fixture->getPath($this->sut->getRepositoryUrl());

      if (!is_dir($sut_repo)) {
        $this->output->error(sprintf('SUT is absent from expected location: %s', $sut_repo));
        throw new OrcaException();
      }
      $this->output->comment(sprintf('SUT is present at expected location: %s', $sut_repo));

      $composer_json = new JsonFile("{$sut_repo}/composer.json");
      if (!$composer_json->exists()) {
        $this->output->error(sprintf('SUT is missing root composer.json', $sut_repo));
        throw new OrcaException();
      }
      $this->output->comment('SUT contains root composer.json');

      $data = $composer_json->read();

      $actual_name = isset($data['name']) ? $data['name'] : NULL;
      $expected_name = $this->sut->getPackageName();
      if ($actual_name !== $expected_name) {
        $this->output->error(sprintf("SUT composer.json's 'name' value %s does not match expected %s", var_export($actual_name, TRUE), var_export($expected_name, TRUE)));
        throw new OrcaException();
      }
      $this->output->comment(sprintf("SUT composer.json's 'name' value matches expected %s", var_export($expected_name, TRUE)));
    }
  }

  /**
   * Creates a BLT project.
   */
  private function createBltProject(): void {
    $this->output->section('Creating BLT project');
    $process = $this->processRunner->createOrcaVendorBinProcess([
      'composer',
      'create-project',
      '--stability=dev',
      '--no-interaction',
      '--no-install',
      '--no-scripts',
      'acquia/blt-project',
      $this->fixture->getPath(),
    ]);
    $this->processRunner->run($process);

    // Prevent errors later because "Source directory docroot/core has
    // uncommitted changes" after "Removing package drupal/core so that it can
    // be re-installed and re-patched".
    // @see https://drupal.stackexchange.com/questions/273859
    $this->loadComposerJson();
    $this->jsonConfigSource->addConfigSetting('discard-changes', TRUE);
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
   * Removes unneeded projects.
   */
  private function removeUnneededProjects(): void {
    $this->output->section('Removing unneeded projects');
    $process = $this->processRunner->createOrcaVendorBinProcess([
      'composer',
      'remove',
      '--no-update',
      // The Lightning profile requirement conflicts with individual Lightning
      // submodule requirements--namely, it prevents them from being symlinked
      // via a local "path" repository.
      'acquia/lightning',
      // Other Acquia projects are only conditionally required later and should
      // in no case be included up-front.
      'drupal/acquia_connector',
      'drupal/acquia_purge',
    ]);
    $this->processRunner->run($process, $this->fixture->getPath());
  }

  /**
   * Adds Acquia projects to the codebase.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   */
  private function addAcquiaProjects(): void {
    $this->output->section('Adding Acquia projects');
    $this->addTopLevelAcquiaPackages();
    $this->addSutSubmodules();
    $this->addComposerExtraData();
    $this->commitCodeChanges('Added Acquia projects.');
  }

  /**
   * Adds the top-level Acquia packages to composer.json.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   *   If the SUT isn't properly installed.
   */
  private function addTopLevelAcquiaPackages(): void {
    $this->configureComposerForTopLevelAcquiaPackages();
    $this->composerRequireTopLevelAcquiaPackages();
    if ($this->sut) {
      $this->forceSutSymlinkInstall();
      $this->verifySut();
    }
  }

  /**
   * Configures Composer to find and place Acquia projects.
   */
  private function configureComposerForTopLevelAcquiaPackages(): void {
    $this->loadComposerJson();
    if ($this->sut) {
      $this->addSutRepository();
    }
  }

  /**
   * Adds a Composer repository for the system under test.
   *
   * Repositories take precedence in the order specified (i.e., first match
   * found wins), so our override needs to be added to the beginning in order
   * to take effect.
   */
  private function addSutRepository(): void {
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
      'sut-only' => $this->isSutOnly,
    ]);
  }

  /**
   * Requires the top-level Acquia packages via Composer.
   */
  private function composerRequireTopLevelAcquiaPackages(): void {
    $process = $this->processRunner->createOrcaVendorBinProcess(array_merge(
      ['composer', 'require', '--no-interaction'],
      $this->getAcquiaProductModuleDependencies()
    ));
    $this->processRunner->run($process, $this->fixture->getPath());
  }

  /**
   * Forces Composer to install the SUT from the local path repository.
   */
  private function forceSutSymlinkInstall(): void {
    $this->filesystem->remove([
      $this->fixture->getPath('composer.lock'),
      $this->sut->getInstallPathAbsolute(),
    ]);
    $process = $this->processRunner->createOrcaVendorBinProcess([
      'composer',
      'install',
      '--no-interaction',
    ]);
    $this->processRunner->run($process, $this->fixture->getPath());
  }

  /**
   * Verifies that the SUT was correctly placed.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function verifySut(): void {
    $sut_install_path = $this->sut->getInstallPathAbsolute();
    if (!file_exists($sut_install_path)) {
      $this->output->error('Failed to place SUT at correct path.');
      throw new OrcaException();
    }
    elseif (!is_link($sut_install_path)) {
      $this->output->error('Failed to symlink SUT via local path repository.');

      // Display debugging information.
      $process = $this->processRunner->createOrcaVendorBinProcess([
        'composer',
        'why-not',
        "{$this->sut->getPackageName()}:@dev",
      ]);
      $this->processRunner->run($process, $this->fixture->getPath());
      $process = $this->processRunner->createExecutableProcess([
        'cat',
        $this->fixture->getPath('composer.json'),
      ]);
      $this->processRunner->run($process);
      $process = $this->processRunner->createExecutableProcess([
        'cat',
        $this->fixture->getPath("{$this->sut->getRepositoryUrl()}/composer.json"),
      ]);
      $this->processRunner->run($process);

      throw new OrcaException();
    }
  }

  /**
   * Gets the list of Composer dependency strings for Acquia product modules.
   *
   * @return string[]
   */
  private function getAcquiaProductModuleDependencies(): array {
    $dependencies = $this->projectManager->getMultiple(NULL, 'getPackageString');

    if (!$this->sut) {
      return array_values($dependencies);
    }

    $sut_package_string = "{$this->sut->getPackageName()}:@dev";

    if ($this->isSutOnly) {
      return [$sut_package_string];
    }

    // Replace the version constraint on the SUT to allow for symlinking.
    $dependencies[$this->sut->getPackageName()] = $sut_package_string;

    return array_values($dependencies);
  }

  /**
   * Adds submodules of the SUT to composer.json.
   */
  private function addSutSubmodules(): void {
    if (!$this->sut || !$this->submoduleManager->getAll()) {
      return;
    }
    $this->configureComposerForSutSubmodules();
    $this->composerRequireSutSubmodules();
  }

  /**
   * Configures Composer to find and place submodules of the SUT.
   */
  private function configureComposerForSutSubmodules(): void {
    $this->loadComposerJson();
    $this->addSutSubmoduleRepositories();
    $this->addInstallerPathsForSutSubmodules();
  }

  /**
   * Adds Composer repositories for submodules of the SUT.
   *
   * Repositories take precedence in the order specified (i.e., first match
   * found wins), so our override needs to be added to the beginning in order
   * to take effect.
   */
  private function addSutSubmoduleRepositories(): void {
    // Remove original repositories.
    $this->jsonConfigSource->removeProperty('repositories');

    // Add new repositories.
    foreach ($this->submoduleManager->getAll() as $project) {
      $this->jsonConfigSource->addRepository($project->getPackageName(), [
        'type' => 'path',
        'url' => $project->getRepositoryUrl(),
      ]);
    }

    // Append original repositories.
    foreach ($this->jsonConfigDataBackup['repositories'] as $key => $value) {
      $this->jsonConfigSource->addRepository($key, $value);
    }
  }

  /**
   * Adds installer-paths for submodules of the SUT.
   */
  private function addInstallerPathsForSutSubmodules(): void {
    // Installer paths seem to be applied in the order specified, so overrides
    // need to be added to the beginning in order to take effect. Begin by
    // removing the original installer paths.
    $this->jsonConfigSource->removeProperty('extra.installer-paths');

    // Add new installer paths.
    $package_names = array_keys($this->submoduleManager->getByParent($this->sut));
    // Submodules are implicitly installed with their parent modules, and
    // Composer won't allow them to be placed in the same location via their
    // separate packages to be placed in the same location. Neither will it
    // allow them to be "installed" outside the repository, in the system temp
    // directory or /dev/null, for example. In the absence of a better option,
    // the private files directory provides a convenient destination that Git is
    // already configured to ignore.
    $path = 'extra.installer-paths.files-private/{$name}';
    $this->jsonConfigSource->addProperty($path, $package_names);

    // Append original installer paths.
    foreach ($this->jsonConfigDataBackup['extra']['installer-paths'] as $key => $value) {
      $this->jsonConfigSource->addProperty("extra.installer-paths.{$key}", $value);
    }
  }

  /**
   * Requires the Acquia submodules via Composer.
   */
  private function composerRequireSutSubmodules(): void {
    $packages = [];
    foreach (array_keys($this->submoduleManager->getByParent($this->sut)) as $package_name) {
      $packages[] = "{$package_name}:@dev";
    }
    $process = $this->processRunner->createOrcaVendorBinProcess(array_merge(
      ['composer', 'require', '--no-interaction'],
      $packages
    ));
    $this->processRunner->run($process, $this->fixture->getPath());
  }

  /**
   * Commits code changes made to the build directory.
   *
   * @param string $message
   *   The commit message to use.
   */
  private function commitCodeChanges($message): void {
    $cwd = $this->fixture->getPath();
    $process = $this->processRunner->createExecutableProcess([
      'git',
      'add',
      '-A',
    ]);
    $this->processRunner->run($process, $cwd);
    $process = $this->processRunner->createExecutableProcess([
      'git',
      'commit',
      '-m',
      $message,
      '--author="ORCA <no-reply@acquia.com>"',
      '--allow-empty',
    ]);
    $this->processRunner->run($process, $cwd);
  }

  /**
   * Installs Drupal.
   */
  private function installDrupal(): void {
    $this->output->section('Installing Drupal');
    $this->ensureDrupalSettings();
    $process = $this->processRunner->createFixtureVendorBinProcess([
      'drush',
      'site-install',
      'minimal',
      "install_configure_form.update_status_module='[FALSE,FALSE]'",
      'install_configure_form.enable_update_status_module=NULL',
      '--site-name=ORCA',
      '--account-name=admin',
      '--account-pass=admin',
      '--no-interaction',
      '--verbose',
      '--ansi',
    ]);
    $this->processRunner->run($process, $this->fixture->getPath());
    $this->commitCodeChanges('Installed Drupal.');
  }

  /**
   * Ensure that Drupal is correctly configured.
   */
  protected function ensureDrupalSettings(): void {
    $filename = $this->fixture->getPath('docroot/sites/default/settings/local.settings.php');
    $id = '# ORCA settings.';

    // Return early if the settings are already present.
    if (strpos(file_get_contents($filename), $id)) {
      return;
    }

    // Add the settings.
    $data = "\n{$id}\n" . <<<'PHP'
$databases['default']['default']['database'] = dirname(DRUPAL_ROOT) . '/docroot/sites/default/files/.ht.sqlite';
$databases['default']['default']['driver'] = 'sqlite';
unset($databases['default']['default']['namespace']);

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
  }

  /**
   * Installs the Acquia modules.
   */
  private function installAcquiaModules(): void {
    if ($this->isSutOnly && ($this->sut->getType() !== 'drupal-module')) {
      return;
    }

    $this->output->section('Installing Acquia product modules');
    $module_list = $this->getAcquiaModuleList();
    $process = $this->processRunner->createFixtureVendorBinProcess(array_merge([
      'drush',
      'pm-enable',
      '--yes',
    ], $module_list));
    $this->processRunner->run($process, $this->fixture->getPath());
  }

  /**
   * Gets the list of Acquia modules to install.
   *
   * @return string[]
   */
  private function getAcquiaModuleList(): array {
    if ($this->isSutOnly) {
      $module_list = [$this->sut->getProjectName()];
      foreach ($this->submoduleManager->getByParent($this->sut) as $submodule) {
        $module_list[] = $submodule->getProjectName();
      }
      return $module_list;
    }

    $module_list = array_values($this->projectManager->getMultiple('drupal-module', 'getProjectName'));
    foreach ($this->submoduleManager->getAll() as $submodule) {
      $module_list[] = $submodule->getProjectName();
    }
    return $module_list;
  }

  /**
   * Creates a backup branch for the current state of the code.
   */
  private function createBackupBranch(): void {
    $this->output->section('Creating backup branch');
    $process = $this->processRunner->createExecutableProcess([
      'git',
      'branch',
      '--force',
      Fixture::BASE_FIXTURE_GIT_BRANCH,
    ]);
    $this->processRunner->run($process, $this->fixture->getPath());
  }

}
