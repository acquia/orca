<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Acquia\Orca\Exception\OrcaVersionNotFoundException;

/**
 * Provides Composer remote version finding.
 */
class VersionFinder {

  /**
   * The version selector factory.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory
   */
  private $versionSelectorFactory;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory $version_selector_factory
   *   The version selector factory.
   */
  public function __construct(VersionSelectorFactory $version_selector_factory) {
    $this->versionSelectorFactory = $version_selector_factory;
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
    $version_selector = $this->versionSelectorFactory->create(TRUE, $dev);

    $stability = 'alpha';
    if ($dev) {
      $stability = 'dev';
    }

    /* @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal */
    $candidate = $version_selector->findBestCandidate($package_name, $constraint, $stability);

    if (!$candidate) {
      $constraint = $constraint ?? "*";
      throw new OrcaVersionNotFoundException(sprintf('No available version could be found for "%s:%s@%s".', $package_name, $constraint, $stability));
    }

    return $candidate->getPrettyVersion();
  }

}
