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
  private $factory;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory $factory
   *   The Composer pool factory.
   */
  public function __construct(PoolFactory $factory) {
    $this->factory = $factory;
  }

  /**
   * Creates a Composer version selector for Packagist and Drupal.org.
   *
   * @return \Composer\Package\Version\VersionSelector
   *   The version selector.
   */
  public function create(): VersionSelector {
    $pool = $this->factory->createWithDrupalDotOrg();
    return new VersionSelector($pool);
  }

}
