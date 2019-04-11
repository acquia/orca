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
   * The current recommended version.
   *
   * @var string
   */
  private $currentRecommendedVersion = '';

  /**
   * The latest pre-release version.
   *
   * @var string
   */
  private $latestPreReleaseVersion = '';

  /**
   * The previous minor version.
   *
   * @var string
   */
  private $previousMinorVersion = '';

  /**
   * Gets the previous minor version.
   *
   * @return string
   *   The version string, e.g., "8.5.14.0".
   */
  public function getPreviousMinorVersion(): string {
    if ($this->previousMinorVersion) {
      return $this->previousMinorVersion;
    }
    $this->previousMinorVersion = $this->getVersionSelector()
      ->findBestCandidate('drupal/core', "<{$this->getCurrentMinorVersion()}")
      ->getVersion();
    return $this->previousMinorVersion;
  }

  /**
   * Gets the current recommended version.
   *
   * @return string
   *   The version string, e.g., "8.6.14.0".
   */
  public function getCurrentRecommendedVersion(): string {
    if ($this->currentRecommendedVersion) {
      return $this->currentRecommendedVersion;
    }
    $this->currentRecommendedVersion = $this->getVersionSelector()
      ->findBestCandidate('drupal/core')
      ->getVersion();
    return $this->currentRecommendedVersion;
  }

  /**
   * Gets the current dev version.
   *
   * @return string
   *   The version string, e.g., "8.6.x-dev".
   */
  public function getCurrentDevVersion(): string {
    return "{$this->getCurrentMinorVersion()}.x-dev";
  }

  /**
   * Gets the latest pre-release version.
   *
   * @return string
   *   The version string, e.g., "8.7.0.0-beta2".
   */
  public function getLatestPreReleaseVersion(): string {
    if ($this->latestPreReleaseVersion) {
      return $this->latestPreReleaseVersion;
    }
    $this->latestPreReleaseVersion = $this->getVersionSelector('alpha')
      ->findBestCandidate('drupal/core', ">{$this->getCurrentRecommendedVersion()}")
      ->getVersion();
    return $this->latestPreReleaseVersion;
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
    return (string) floatval($this->getCurrentRecommendedVersion());
  }

}
