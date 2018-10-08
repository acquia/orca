<?php

namespace Acquia\Orca\Robo\Plugin\Commands;

use Acquia\Orca\Exception\UserCancelException;
use Acquia\Orca\ProductModuleDataManager;
use Symfony\Component\Console\Input\InputOption;

/**
 * Provides the "fixture:create" command.
 */
class FixtureCreateCommand extends CommandBase {

  /**
   * The fixture's Composer configuration (composer.json).
   *
   * @var \stdClass
   */
  private $composerConfig;

  /**
   * Whether or not the --force command option was specified.
   *
   * @var bool
   */
  private $force = FALSE;

  /**
   * The SUT package name, e.g., drupal/example.
   *
   * @var string|false
   */
  private $sut = FALSE;

  /**
   * The SUT destination directory base name.
   *
   * @var string
   */
  private $sutDestBaseName = '';

  /**
   * The SUT source directory base name.
   *
   * @var string
   */
  private $sutSourceBaseName = '';

  /**
   * The SUT destination absolute path.
   *
   * @var string
   */
  private $sutDestPath = '';

  /**
   * Whether or not the build is SUT-only.
   *
   * @var bool
   */
  private $sutOnly = FALSE;

  /**
   * Creates the base test fixture.
   *
   * Creates a BLT-based Drupal site build, includes the system under test using Composer, optionally includes all other Acquia product modules, installs Drupal, and commits any code changes.
   *
   * @command fixture:create
   * @option sut-only Add only the system under test (SUT). Omit all other
   *   non-required Acquia product modules.
   * @option sut The system under test (SUT) in the form of its package name,
   *   e.g., drupal/example.
   * @option force Destroy a pre-existing fixture first, if present. See
   *   fixture:destroy.
   * @aliases build
   * @usage fixture:create --sut=drupal/example
   *   Create the fixture for testing drupal/example in the presence of all other
   *   Acquia product modules.
   * @usage fixture:create --sut=drupal/example --sut-only
   *   Create the fixture for testing drupal/example in the absence of any other
   *   non-required Acquia product modules.
   *
   * @return \Robo\Collection\CollectionBuilder|int
   */
  public function execute(array $options = [
    'sut|s' => InputOption::VALUE_REQUIRED,
    'sut-only|i' => FALSE,
    // @todo A one-character option shortcut of -f for --force seems obvious,
    //   but for some reason, robo doesn't respect it. Other characters work.
    //   It's probably a bug, but it didn't seem worth reporting at the time.
    'force' => FALSE,
  ]) {
    $this->force = $options['force'];
    $this->sut = $options['sut'];
    if ($this->sut) {
      $this->sutDestBaseName = ProductModuleDataManager::moduleName($this->sut);
      $this->sutSourceBaseName = ProductModuleDataManager::dir($this->sut);
      $this->sutDestPath = $this->buildPath(self::ACQUIA_PRODUCT_MODULES_DIR . "/{$this->sutDestBaseName}");
    }
    $this->sutOnly = $options['sut-only'];

    if ($this->sutOnly && empty($this->sut)) {
      throw new \InvalidArgumentException('An sut option value must be provided for an SUT-only build.');
    }

    $valid_values = ProductModuleDataManager::packageNames();
    if ($this->sut && !in_array($this->sut, $valid_values)) {
      throw new \RuntimeException(sprintf("Invalid value for sut option: \"%s\". Acceptable values are\n  - %s", $this->sut, implode("\n  - ", $valid_values)));
    }

    try {
      return $this->collectionBuilder()
        ->addTaskList([
          $this->createBltProject(),
          $this->createCodeBranch('initial'),
          $this->removeLightningProfile(),
          $this->addAcquiaProductModules(),
          $this->commitCodeChanges('Added Acquia product modules.'),
          $this->installDrupal(),
          $this->commitCodeChanges('Installed Drupal.', self::BASE_FIXTURE_BRANCH),
          $this->installAcquiaProductModules(),
        ])
        ->addCode($this->selfCheck());
    }
    catch (UserCancelException $e) {
      return $e->getCode();
    }
  }

  /**
   * Creates a BLT project.
   *
   * @return \Robo\Task\Composer\CreateProject
   */
  private function createBltProject() {
    $collection = $this->collectionBuilder();

    if (file_exists($this->buildPath())) {
      if ($this->force) {
        $collection->addTask($this->destroyFixture());
      }
      else {
        $this->io()
          ->error('The fixture already exists. Use the --force option to destroy it and proceed anyway.');
        throw new UserCancelException();
      }
    }

    return $collection->addTask($this->taskComposerCreateProject()
      // @todo Remove the dev branch when composer-merge-plugin removal work has
      //   been merged into from BLT.
      ->source('acquia/blt-project:dev-remove-merge-plugin')
      ->target($this->buildPath())
      ->interactive(FALSE));
  }

  /**
   * Removes the Lightning profile Composer requirement (acquia/lightning).
   *
   * The profile requirement conflicts with individual Lightning submodule
   * requirements--namely, it prevents them from being symlinked via a local
   * "path" repository.
   *
   * @return \Robo\Task\Composer\Remove
   */
  private function removeLightningProfile() {
    return $this->taskComposerRemove()
      ->dir($this->buildPath())
      ->arg('acquia/lightning');
  }

  /**
   * Adds Acquia product modules to the codebase.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  private function addAcquiaProductModules() {
    $collection = $this->collectionBuilder()
      ->addCode($this->configureComposer())
      ->addTaskList([
        $this->taskComposerRequire()
          ->dependency($this->getDependencies())
          ->dir($this->buildPath()),
        $this->removeDuplicateModules(),
      ]);
    if ($this->sut) {
      $collection->addTask($this->forceSutSymlinkInstall());
    }
    return $collection;
  }

  /**
   * Configures Composer to place Acquia modules in a special directory.
   *
   * @return \Closure
   */
  private function configureComposer() {
    return function () {
      $this->loadComposerJson();
      $this->addInstallerPaths();
      if ($this->sut) {
        $this->addSutRepository();
      }
      $this->saveComposerJson();
    };
  }

  /**
   * Loads the fixture's composer.json data.
   */
  private function loadComposerJson() {
    $json = file_get_contents($this->composerJson());
    $this->composerConfig = json_decode($json);
  }

  /**
   * Returns the path to the fixture's composer.json file.
   *
   * @return string
   */
  private function composerJson() {
    return $this->buildPath('composer.json');
  }

  /**
   * Adds installer-paths configuration to group product modules together.
   */
  private function addInstallerPaths() {
    // Installer paths seem to be applied in the order specified, so our
    // overrides need to be added to the beginning in order to take effect.
    // Drush commands, which we don't WANT to override, need to come yet
    // earlier.
    $this->composerConfig->extra->{'installer-paths'} = (object) array_merge(
      ['drush/Commands/{$name}' => (array) $this->composerConfig->extra->{'installer-paths'}->{'drush/Commands/{$name}'}],
      ['docroot/modules/contrib/acquia/{$name}' => ProductModuleDataManager::packageNames()],
      (array) $this->composerConfig->extra->{'installer-paths'}
    );
  }

  /**
   * Adds a Composer repository for the system under test.
   */
  private function addSutRepository() {
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
   * Saves the fixture's composer.json data.
   */
  private function saveComposerJson() {
    $data = json_encode($this->composerConfig, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    file_put_contents($this->composerJson(), $data);
  }

  /**
   * Gets the list of Composer dependency strings.
   *
   * @return string[]
   */
  private function getDependencies() {
    $sut_package_string = "{$this->sut}:@dev";

    if ($this->sutOnly) {
      return [$sut_package_string];
    }

    $dependencies = ProductModuleDataManager::packageStringPlural();

    // Replace the version constraint on the SUT to allow for symlinking.
    if ($this->sut) {
      $dependencies[$this->sut] = $sut_package_string;
    }

    return array_values($dependencies);
  }

  /**
   * Forces Composer to install the SUT from the local path repository.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  private function forceSutSymlinkInstall() {
    return $this->collectionBuilder()
      ->addTaskList([
        $this->taskFilesystemStack()
          ->remove([
            $this->buildPath('composer.lock'),
            $this->sutDestPath,
          ]),
        $this->taskComposerInstall()
          ->noInteraction()
          ->dir($this->buildPath()),
      ]);
  }

  /**
   * Removes duplicate modules left in docroot/modules/contrib.
   *
   * Modules placed in docroot/modules/contrib by the original
   * `composer create-project` process are not removed when new versions are
   * saved to docroot/modules/acquia.
   *
   * @return \Robo\Task\Filesystem\DeleteDir
   */
  private function removeDuplicateModules() {
    $dirs = [];
    foreach (ProductModuleDataManager::projectNamePlural($this->sut) as $name) {
      $path = $this->buildPath("docroot/modules/contrib/{$name}");
      if (file_exists($path)) {
        $dirs[] = $path;
      }
    }
    return $this->taskDeleteDir($dirs);
  }

  /**
   * Commits code changes made to the build directory.
   *
   * @param string $message
   *   The commit message to use.
   * @param string|false $backup_branch
   *   The name of a branch to point at the commit for backup or FALSE to skip
   *   creating one.
   *
   * @return \Robo\Task\Vcs\GitStack
   */
  private function commitCodeChanges($message, $backup_branch = FALSE) {
    $task = $this->taskGitStack()
      ->dir($this->buildPath())
      ->silent(TRUE)
      ->add('.')
      ->commit($message, '--allow-empty');
    if ($backup_branch) {
      $task->exec("branch -f {$backup_branch}");
    }
    return $task;
  }

  /**
   * Creates a code branch.
   *
   * @param string $name
   *   A name for the branch.
   *
   * @return \Robo\Task\Vcs\GitStack
   */
  private function createCodeBranch($name) {
    return $this->taskGitStack()
      ->dir($this->buildPath())
      ->exec("branch -f {$name}");
  }

  /**
   * Installs all Acquia product modules.
   *
   * @return \Acquia\Orca\Task\NullTask|\Robo\Collection\CollectionBuilder
   */
  private function installAcquiaProductModules() {
    $package = ($this->sutOnly) ? $this->sut : FALSE;
    $module_list = implode(' ', ProductModuleDataManager::moduleNamePlural($package));

    if (!$module_list) {
      return $this->taskNull();
    }

    return $this->taskDrushExec("pm-enable -y {$module_list}");
  }

  /**
   * Verifies the results of the command.
   */
  private function selfCheck() {
    return function () {
      if ($this->sut) {
        $this->assert(file_exists($this->sutDestPath), 'Internal error: Failed to place SUT at the correct path.');
        $this->assert(is_link($this->sutDestPath), 'Internal error: Failed to symlink SUT via local path repository.');
      }
      $this->io()->note('Command fixture:create complete.');
    };
  }

}
