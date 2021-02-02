<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositorySet;

/**
 * Creates a Composer version selector.
 */
class VersionSelectorFactory {

  /**
   * Creates Composer version selector.
   *
   * @param bool $include_drupal_dot_org
   *   TRUE to include drupal.org or FALSE to include on Packagist.
   * @param bool $dev
   *   TRUE for a minimum stability of dev or FALSE for alpha.
   *
   * @return \Composer\Package\Version\VersionSelector
   *   The version selector.
   */
  public function create($include_drupal_dot_org, bool $dev): VersionSelector {
    $repository_set = $this->createDefaultRepositorySet($dev);

    if ($include_drupal_dot_org) {
      $this->addDrupalDotOrgRepository($repository_set);
    }

    return new VersionSelector($repository_set);
  }

  /**
   * Creates the default repository set.
   *
   * @param bool $dev
   *   TRUE for a minimum stability of dev or FALSE for alpha.
   *
   * @return \Composer\Repository\RepositorySet
   *   The repository set.
   */
  protected function createDefaultRepositorySet(bool $dev): RepositorySet {
    $stability = $dev ? 'dev' : 'alpha';
    return new RepositorySet($stability, []);
  }

  /**
   * Adds the Drupal.org Composer Facade to the repository set.
   *
   * @param \Composer\Repository\RepositorySet $repository_set
   *   The repository set.
   */
  protected function addDrupalDotOrgRepository(RepositorySet $repository_set): void {
    $io = new NullIO();

    // An empty config array must be passed or Composer will look for a
    // composer.json on the filesystem, resulting in a dependence on the current
    // working directory and hard-to-debug failures if the ORCA CLI is invoked
    // from anywhere other than its root directory.
    $composer = $composer = Factory::create($io, [], TRUE);

    $repository_manager = $composer->getRepositoryManager();
    $repositories = $repository_manager->getRepositories();
    $composite_repository = new CompositeRepository($repositories);
    $config = Factory::createConfig($io, '/dev/null');
    $drupal_org = RepositoryFactory::createRepo($io, $config, [
      'type' => 'composer',
      'url' => 'https://packages.drupal.org/8',
    ]);
    $composite_repository->addRepository($drupal_org);
    $repository_set->addRepository($composite_repository);
  }

}
