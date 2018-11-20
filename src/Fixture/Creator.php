<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\ProcessRunner;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates a fixture.
 *
 * @property \stdClass $composerConfig
 * @property \Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Symfony\Component\Console\Style\SymfonyStyle $output
 * @property \Acquia\Orca\ProcessRunner $processRunner
 * @property \Acquia\Orca\Fixture\ProductData $productData
 * @property string $sutDestBaseName
 * @property string $sutDestPath
 * @property string $sutSourceBaseName
 */
class Creator {

  /**
   * Whether or not the fixture is SUT-only.
   *
   * @var bool
   */
  private $isSutOnly = FALSE;

  /**
   * The SUT package name, e.g., drupal/example.
   *
   * @var string|null
   */
  private $sut;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $output
   *   The output decorator.
   * @param \Acquia\Orca\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Fixture\ProductData $product_data
   *   The product data.
   */
  public function __construct(Filesystem $filesystem, Fixture $fixture, SymfonyStyle $output, ProcessRunner $process_runner, ProductData $product_data) {
    $this->fixture = $fixture;
    $this->filesystem = $filesystem;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->productData = $product_data;
  }

  /**
   * Creates the fixture.
   */
  public function create(): void {
    $this->createBltProject();
    $this->removeUnneededProjects();
    $this->addAcquiaProductModules();
    $this->installDrupal();
    $this->installAcquiaProductModules();
    $this->createBackupBranch();
    $this->selfCheck();
  }

  /**
   * Sets the system under test (SUT).
   *
   * @param string|null $sut
   *   (Optional) The system under test (SUT) in the form of its package name,
   *   e.g., "drupal/example", or NULL to unset the SUT.
   */
  public function setSut(?string $sut = NULL): void {
    $this->sut = $sut;
    $this->sutDestBaseName = $this->productData->moduleName($this->sut);
    $this->sutSourceBaseName = $this->productData->dir($this->sut);
    $this->sutDestPath = $this->fixture->docrootPath("/modules/contrib/acquia/{$this->sutDestBaseName}");
  }

  /**
   * Sets the fixture to SUT-only or not.
   *
   * @param bool $is_sut_only
   *   (Optional) Whether or not to set the fixture to SUT-only. Defaults to
   *   TRUE.
   *
   * phpcs:disable Drupal.Commenting.FunctionComment.IncorrectTypeHint
   */
  public function setSutOnly(?bool $is_sut_only = NULL): void {
    $this->isSutOnly = (bool) $is_sut_only;
  }

  /**
   * Creates a BLT project.
   */
  private function createBltProject(): void {
    $this->output->section('Creating BLT project');
    $this->processRunner->runExecutableProcess([
      'composer',
      'create-project',
      // @todo Remove the dev branch when composer-merge-plugin removal work
      //   has been merged into BLT.
      'acquia/blt-project:dev-remove-merge-plugin',
      $this->fixture->rootPath(),
      '--no-interaction',
      '--no-install',
      '--no-scripts',
    ]);
  }

  /**
   * Removes unneeded projects.
   */
  private function removeUnneededProjects(): void {
    $this->output->section('Removing unneeded projects');
    $this->processRunner->runExecutableProcess([
      'composer',
      'remove',
      // The Lightning profile requirement conflicts with individual Lightning
      // submodule requirements--namely, it prevents them from being symlinked
      // via a local "path" repository.
      'acquia/lightning',
      // Other Acquia projects are only conditionally required later and should
      // in no case be included up-front.
      'drupal/acquia_connector',
      'drupal/acquia_purge',
      '--no-update',
    ], $this->fixture->rootPath());
  }

  /**
   * Adds Acquia product modules to the codebase.
   */
  private function addAcquiaProductModules(): void {
    $this->output->section('Adding Acquia product modules');
    $this->configureComposer();
    $this->requireDependencies();
    if ($this->sut) {
      $this->forceSutSymlinkInstall();
    }
    $this->commitCodeChanges('Added Acquia product modules.');
  }

  /**
   * Configures Composer to place Acquia modules in a special directory.
   */
  private function configureComposer(): void {
    $this->loadComposerJson();
    $this->addInstallerPaths();
    if ($this->sut) {
      $this->addSutRepository();
    }
    $this->addExtraData();
    $this->saveComposerJson();
  }

  /**
   * Loads the fixture's composer.json data.
   */
  private function loadComposerJson(): void {
    $json = file_get_contents($this->fixture->rootPath('composer.json'));
    $this->composerConfig = json_decode($json);
  }

  /**
   * Adds installer-paths configuration to group product modules together.
   */
  private function addInstallerPaths(): void {
    // Installer paths seem to be applied in the order specified, so our
    // overrides need to be added to the beginning in order to take effect.
    // Drush commands, which we don't WANT to override, need to come yet
    // earlier.
    $this->composerConfig->extra->{'installer-paths'} = (object) array_merge(
      ['drush/Commands/{$name}' => (array) $this->composerConfig->extra->{'installer-paths'}->{'drush/Commands/{$name}'}],
      [Fixture::PRODUCT_MODULE_INSTALL_PATH . '/{$name}' => $this->productData->packageNames()],
      (array) $this->composerConfig->extra->{'installer-paths'}
    );
  }

  /**
   * Adds a Composer repository for the system under test.
   */
  private function addSutRepository(): void {
    // Avoid PHP warnings by creating the "repositories" property if absent.
    if (!property_exists($this->composerConfig, 'repositories') || !is_object($this->composerConfig->repositories)) {
      $this->composerConfig->repositories = new \stdClass();
    }
    // Repositories take precedence in the order specified (i.e., first match
    // found wins), so our override needs to be added to the beginning in order
    // to take effect.
    $this->composerConfig->repositories = (object) array_merge(
      [
        $this->sut => [
          'type' => 'path',
          'url' => "../{$this->sutSourceBaseName}",
        ],
      ],
      (array) $this->composerConfig->repositories
    );
  }

  /**
   * Adds data about the fixture to the "extra" property.
   */
  private function addExtraData() {
    $this->composerConfig->extra->orca = (object) [
      'sut' => $this->sut,
      'sut-only' => $this->isSutOnly,
    ];
  }

  /**
   * Requires the dependencies via Composer.
   */
  private function requireDependencies(): void {
    $this->processRunner->runExecutableProcess(array_merge(
      ['composer', 'require'],
      $this->getDependencies()
    ), $this->fixture->rootPath());
  }

  /**
   * Forces Composer to install the SUT from the local path repository.
   */
  private function forceSutSymlinkInstall(): void {
    $this->filesystem->remove([
      $this->fixture->rootPath('composer.lock'),
      $this->sutDestPath,
    ]);
    $this->processRunner->runExecutableProcess([
      'composer',
      'install',
      '--no-interaction',
    ], $this->fixture->rootPath());
  }

  /**
   * Gets the list of Composer dependency strings.
   *
   * @return string[]
   */
  private function getDependencies(): array {
    $sut_package_string = "{$this->sut}:@dev";
    if ($this->isSutOnly) {
      return [$sut_package_string];
    }
    $dependencies = $this->productData->packageStringPlural();

    // Replace the version constraint on the SUT to allow for symlinking.
    if ($this->sut) {
      $dependencies[$this->sut] = $sut_package_string;
    }

    return array_values($dependencies);
  }

  /**
   * Saves the fixture's composer.json data.
   */
  private function saveComposerJson(): void {
    $data = json_encode($this->composerConfig, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    file_put_contents($this->fixture->rootPath('composer.json'), $data);
  }

  /**
   * Commits code changes made to the build directory.
   *
   * @param string $message
   *   The commit message to use.
   */
  private function commitCodeChanges($message): void {
    $cwd = $this->fixture->rootPath();
    $this->processRunner->runExecutableProcess(['git', 'add', '-A'], $cwd);
    $this->processRunner->runExecutableProcess([
      'git',
      'commit',
      '-m',
      $message,
      '--author',
      'ORCA <no-reply@acquia.com>',
      '--allow-empty',
    ], $cwd);
  }

  /**
   * Installs Drupal.
   */
  private function installDrupal(): void {
    $this->output->section('Installing Drupal');
    $this->ensureDrupalSettings();
    $this->processRunner->runProcess([
      'vendor/bin/drush',
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
    ], $this->fixture->rootPath());
    $this->commitCodeChanges('Installed Drupal.');
  }

  /**
   * Ensure that Drupal is correctly configured.
   */
  protected function ensureDrupalSettings() {
    $filename = $this->fixture->docrootPath('sites/default/settings/local.settings.php');
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
   * Installs the Acquia product modules.
   */
  private function installAcquiaProductModules(): void {
    $this->output->section('Installing Acquia product modules');

    $package = ($this->isSutOnly) ? $this->sut : NULL;
    $module_list = $this->productData->moduleNamePlural($package);

    if (!$module_list) {
      return;
    }

    $this->processRunner->runProcess(array_merge([
      'vendor/bin/drush',
      'pm-enable',
      '-y',
    ], $module_list), $this->fixture->rootPath());
  }

  /**
   * Creates a backup branch for the current state of the code.
   */
  private function createBackupBranch(): void {
    $this->output->section('Creating backup branch');
    $this->processRunner->runExecutableProcess([
      'git',
      'branch',
      '--force',
      Fixture::BASE_FIXTURE_GIT_BRANCH,
    ], $this->fixture->rootPath());
  }

  /**
   * Verifies the fixture.
   */
  private function selfCheck(): void {
    $this->output->section('Verifying the fixture');
    $errors = [];

    if ($this->sut) {
      if (!file_exists($this->sutDestPath)) {
        $errors[] = 'Failed to place SUT at the correct path.';
      }
      elseif (!is_link($this->sutDestPath)) {
        $errors[] = 'Failed to symlink SUT via local path repository.';
      }
    }

    if ($errors) {
      $this->output->error($errors);
      return;
    }

    $this->output->success('Fixture created');
  }

}
