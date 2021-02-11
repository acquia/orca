<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Acquia\Orca\Exception\OrcaVersionNotFoundException;

/**
 * Provides Composer remote version finding.
 */
class VersionFinder {

  /**
   * The version selector.
   *
   * @var \Composer\Package\Version\VersionSelector
   */
  private $versionSelector;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory $version_selector_factory
   *   The version selector factory.
   */
  public function __construct(VersionSelectorFactory $version_selector_factory) {
    $this->versionSelector = $version_selector_factory->create(TRUE, FALSE);
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
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function findLatestVersion(string $package_name, ?string $constraint, bool $dev): string {
    $stability = 'alpha';
    if ($dev) {
      $stability = 'dev';
    }

    /* @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal */
    $candidate = $this->versionSelector->findBestCandidate($package_name, $constraint, $stability);

    if (!$candidate) {
      throw new OrcaVersionNotFoundException(sprintf('No available version could be found for "%s:%s".', $package_name, $constraint));
    }

    return $candidate->getPrettyVersion();
  }

}
