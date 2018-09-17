<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Symfony\Component\Finder\Finder;

/**
 * Provides the "fixture:create" command.
 */
class FixtureCreateCommand extends CommandBase {

  /**
   * Creates the base test fixture.
   *
   * Creates a BLT-based Drupal site build, includes the module under test
   * using Composer, installs Drupal, and commits any code changes.
   *
   * @command fixture:create
   * @aliases build
   *
   * @return \Robo\ResultData
   *
   * @throws \RuntimeException
   */
  public function execute() {
    if (file_exists($this->buildPath())) {
      throw new \RuntimeException('The build directory already exists. Run `orca fixture:destroy` to remove it.');
    }

    return $this->collectionBuilder()
      ->addTaskList([
        $this->createBltProject(),
        $this->addAcquiaProductModules(),
        $this->commitCodeChanges('Added Acquia product modules.'),
        $this->installDrupal(),
        $this->commitCodeChanges('Installed Drupal.', self::BASE_FIXTURE_BRANCH),
        $this->installAcquiaProductModules(),
      ])
      ->run();
  }

  /**
   * Creates a BLT project.
   *
   * @return \Robo\Task\Composer\CreateProject
   */
  private function createBltProject() {
    return $this->taskComposerCreateProject()
      ->source('acquia/blt-project')
      ->target($this->buildPath())
      ->interactive(FALSE);
  }

  /**
   * Adds Acquia product modules to the codebase.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  private function addAcquiaProductModules() {
    return $this->collectionBuilder()
      ->addCode($this->configureComposer())
      ->addTask($this->taskComposerRequire()
        ->dependency($this->getAcquiaProductModulePackageStrings())
        ->dir($this->buildPath()))
      ->addTask($this->removeDuplicateModules());
  }

  /**
   * Configures Composer to place Acquia modules in a special directory.
   *
   * @return \Closure
   */
  private function configureComposer() {
    return function () {
      $composer_file = $this->buildPath('composer.json');
      $config = json_decode(file_get_contents($composer_file));
      // Installer paths seem to be applied in the order specified, so our
      // overrides need to be added to the beginning in order to take effect.
      // Drush commands, which we don't WANT to override, need to come yet
      // earlier.
      $config->extra->{'installer-paths'} = (object) array_merge(
        ['drush/Commands/{$name}' => (array) $config->extra->{'installer-paths'}->{'drush/Commands/{$name}'}],
        ['docroot/modules/contrib/acquia/{$name}' => $this->getAcquiaProductModulePackageNames()],
        (array) $config->extra->{'installer-paths'}
      );
      file_put_contents($composer_file, json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    };
  }

  /**
   * Gets the list of Acquia product module Composer package names.
   *
   * @return string[]
   *   An indexed array of package strings, excluding constraints, e.g.,
   *   "drupal/example:^1.0".
   */
  private function getAcquiaProductModulePackageNames() {
    $names = [];
    foreach ($this->getAcquiaProductModulePackageStrings() as $package_string) {
      $names[] = explode(':', $package_string)[0];
    }
    return $names;
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
    foreach ($this->getAcquiaProductModuleNames() as $name) {
      $dirs[] = $this->buildPath("docroot/modules/contrib/{$name}");
    }
    return $this->taskDeleteDir($dirs);
  }

  /**
   * Gets the list of Acquia product module machine names.
   *
   * @return string[]
   */
  private function getAcquiaProductModuleNames() {
    $names = [];
    foreach ($this->getAcquiaProductModulePackageNames() as $package_name) {
      $names[] = explode('/', $package_name)[1];
    }
    return $names;
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
   * Installs all Acquia product modules.
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  private function installAcquiaProductModules() {
    return $this->collectionBuilder()
      ->addCode(function () {
        $module_list = $this->getAcquiaProductModuleList();
        return $this->taskDrushExec("pm-enable -y {$module_list}")
          ->run();
      });
  }

  /**
   * Gets a space-separated list of Acquia product module machine names.
   *
   * Excludes test modules.
   *
   * @return string
   */
  private function getAcquiaProductModuleList() {
    $files = Finder::create()
      ->files()
      ->in($this->buildPath('docroot/modules/contrib/acquia'))
      ->notPath('@/tests/@')
      ->name('/.*.info.yml$/')
      ->notContains('/package:\\s*Testing/i')
      ->notContains('/hidden:\\s*TRUE/i');
    $modules = [];
    /** @var \SplFileObject $file */
    foreach ($files as $file) {
      $modules[] = basename($file->getFilename(), '.info.yml');
    }
    return implode(' ', $modules);
  }

}
