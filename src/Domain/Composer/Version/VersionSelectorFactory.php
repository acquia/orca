<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\RepositoryFactory;

/**
 * Creates a Composer version selector.
 */
class VersionSelectorFactory {

  /**
   * Creates a Composer version selector for Packagist and Drupal.org.
   *
   * @return \Composer\Package\Version\VersionSelector
   *   The version selector.
   */
  public static function create(): VersionSelector {
    $io = new NullIO();
    $packagist = RepositoryFactory::defaultRepos($io)['packagist.org'];
    $drupal_org = RepositoryFactory::createRepo($io, Factory::createConfig($io), [
      'type' => 'composer',
      'url' => 'https://packages.drupal.org/8',
    ]);

    $pool = new Pool('dev');
    $pool->addRepository($packagist);
    $pool->addRepository($drupal_org);

    return new VersionSelector($pool);
  }

}
