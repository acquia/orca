<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory;
use Composer\Package\Version\VersionSelector;

/**
 * Creates a Composer version selector.
 */
class VersionSelectorFactory {

  /**
   * The Composer pool factory.
   *
   * @var \Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory
   */
  private $poolFactory;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory $pool_factory
   *   The Composer pool factory.
   */
  public function __construct(PoolFactory $pool_factory) {
    $this->poolFactory = $pool_factory;
  }

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
    $pool = $this->poolFactory->create($include_drupal_dot_org, $dev);
    return new VersionSelector($pool);
  }

}
