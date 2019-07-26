<?php

namespace Acquia\Orca\Utility;

use Composer\DependencyResolver\Pool;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\RepositoryFactory;

/**
 * Finds a range of Drupal core versions.
 */
class DrupalCoreVersionFinder {

  /**
   * The current recommended release.
   *
   * @var string
   */
  private $currentRecommendedRelease = '';

  /**
   * The next release.
   *
   * @var string
   */
  private $nextRelease = '';

  /**
   * The previous minor release.
   *
   * @var string
   */
  private $previousMinorRelease = '';

  /**
   * Gets the latest release from the previous minor version.
   *
   * @return string
   *   The version string, e.g., "8.5.14.0".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersion::PREVIOUS_RELEASE
   */
  public function getPreviousMinorRelease(): string {
    if ($this->previousMinorRelease) {
      return $this->previousMinorRelease;
    }
    $this->previousMinorRelease = $this->getCoreVersion("<{$this->getCurrentMinorVersion()}");
    return $this->previousMinorRelease;
  }

  /**
   * Gets the previous minor dev version.
   *
   * @return string
   *   The version string, e.g., "8.5.x-dev".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersion::PREVIOUS_DEV
   */
  public function getPreviousDevVersion(): string {
    $previous_minor_version = floatval($this->getCurrentMinorVersion()) - 0.1;
    return "{$previous_minor_version}.x-dev";
  }

  /**
   * Gets the current recommended release.
   *
   * @return string
   *   The version string, e.g., "8.6.14.0".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersion::CURRENT_RECOMMENDED
   */
  public function getCurrentRecommendedRelease(): string {
    if ($this->currentRecommendedRelease) {
      return $this->currentRecommendedRelease;
    }
    $this->currentRecommendedRelease = $this->getCoreVersion();
    return $this->currentRecommendedRelease;
  }

  /**
   * Gets the current dev version.
   *
   * @return string
   *   The version string, e.g., "8.6.x-dev".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersion::CURRENT_DEV
   */
  public function getCurrentDevVersion(): string {
    return "{$this->getCurrentMinorVersion()}.x-dev";
  }

  /**
   * Gets the latest pre-release version.
   *
   * @return string
   *   The version string, e.g., "8.7.0.0-beta2".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersion::NEXT_RELEASE
   */
  public function getNextRelease(): string {
    if ($this->nextRelease) {
      return $this->nextRelease;
    }
    $this->nextRelease = $this->getCoreVersion(">{$this->getCurrentRecommendedRelease()}", 'alpha');
    return $this->nextRelease;
  }

  /**
   * Gets the next minor dev version.
   *
   * @return string
   *   The version string, e.g., "8.7.x-dev".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersion::NEXT_DEV
   */
  public function getNextDevVersion(): string {
    $previous_minor_version = floatval($this->getCurrentMinorVersion()) + 0.1;
    return "{$previous_minor_version}.x-dev";
  }

  /**
   * Gets the Drupal core version matching the given criteria.
   *
   * @param string|null $target_package_version
   *   The target package version.
   * @param string $minimum_stability
   *   The minimum stability. Available options (in order of stability) are
   *   dev, alpha, beta, RC, and stable.
   *
   * @return string
   *   The version string.
   */
  private function getCoreVersion(string $target_package_version = NULL, string $minimum_stability = 'stable'): string {
    $best_candidate = $this->getVersionSelector($minimum_stability)
      ->findBestCandidate('drupal/core', $target_package_version);
    if (!$best_candidate) {
      throw new \RuntimeException(sprintf('No Drupal core version satisfies the given constraints: version=%s, minimum stability=%s', $target_package_version, $minimum_stability));
    }
    return $best_candidate->getVersion();
  }

  /**
   * Gets a Composer version selector.
   *
   * @param string $minimum_stability
   *   The minimum stability. Available options (in order of stability) are
   *   dev, alpha, beta, RC, and stable.
   *
   * @return \Composer\Package\Version\VersionSelector
   *   A Composer version selector.
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  private function getVersionSelector($minimum_stability = 'stable'): VersionSelector {
    $pool = new Pool($minimum_stability);
    $packagist = RepositoryFactory::defaultRepos(new NullIO())['packagist.org'];
    $pool->addRepository($packagist);
    return new VersionSelector($pool);
  }

  /**
   * Gets the current minor version.
   *
   * @return string
   *   The version string, e.g., "8.6".
   */
  private function getCurrentMinorVersion(): string {
    return (string) floatval($this->getCurrentRecommendedRelease());
  }

}
