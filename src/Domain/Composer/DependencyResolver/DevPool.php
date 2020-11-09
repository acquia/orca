<?php

namespace Acquia\Orca\Domain\Composer\DependencyResolver;

use Composer\DependencyResolver\Pool;

/**
 * Provides a Composer package pool with a dev minimum stability.
 */
class DevPool extends Pool {}
