<?php

namespace Acquia\Orca\Domain\Composer\DependencyResolver;

use Composer\DependencyResolver\Pool;

/**
 * Provides a Composer package pool with an alpha minimum stability.
 */
class ReleasePool extends Pool {}
