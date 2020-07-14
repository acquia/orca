<?php

namespace Acquia\Orca\Filesystem;

use Symfony\Component\Finder\Finder;

/**
 * Provides a factory for Symfony Finder objects for dependency injection.
 */
class FinderFactory {

  /**
   * Creates a Finder instance.
   */
  public function create(): Finder {
    return Finder::create();
  }

}
