<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Acquia\Orca\Exception\VersionNotFoundException;
use Composer\Package\Version\VersionSelector;

/**
 * Provides a facade for encapsulating Composer remote version finding.
 */
class VersionFinder {

  /**
   * The Composer version selector.
   *
   * @var \Composer\Package\Version\VersionSelector
   */
  private $versionSelector;

  /**
   * Constructs an instance.
   *
   * @param \Composer\Package\Version\VersionSelector $version_selector
   *   The Composer version selector.
   */
  public function __construct(VersionSelector $version_selector) {
    $this->versionSelector = $version_selector;
  }

  /**
   * Finds the latest available version for a given package.
   *
   * @param string $package_name
   *   The package name.
   * @param string|null $constraint
   *   The version constraint if there is one or NULL if not.
   * @param bool $dev
   *   TRUE to allow dev version results or FALSE not to.
   *
   * @return string
   *   The found version.
   *
   * @throws \Acquia\Orca\Exception\VersionNotFoundException
   */
  public function findLatestVersion(string $package_name, ?string $constraint, bool $dev): string {
    $stability = 'alpha';
    if ($dev) {
      $stability = 'dev';
    }

    $candidate = $this->versionSelector
      ->findBestCandidate($package_name, $constraint, NULL, $stability);

    if (!$candidate) {
      throw new VersionNotFoundException(sprintf('No available version could be found for "%s:%s".', $package_name, $constraint));
    }

    return $candidate->getPrettyVersion();
  }

}
