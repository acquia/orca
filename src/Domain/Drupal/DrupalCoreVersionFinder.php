<?php

namespace Acquia\Orca\Domain\Drupal;

use Acquia\Orca\Domain\Composer\DependencyResolver\PoolFactory;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Composer\Package\Version\VersionSelector;
use LogicException;
use RuntimeException;

/**
 * Finds a range of Drupal core versions.
 */
class DrupalCoreVersionFinder {

  /**
   * The previous minor release.
   *
   * @var string
   */
  private $previousMinorRelease = '';

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
   * Gets the Drupal core version for a given constant.
   *
   * @param \Acquia\Orca\Enum\DrupalCoreVersionEnum $version
   *   The Drupal core version constant.
   *
   * @return string
   *   The corresponding version string.
   *
   * @throws \RuntimeException
   *   If no corresponding version is found.
   */
  public function get(DrupalCoreVersionEnum $version): string {
    switch ($version->getValue()) {
      case DrupalCoreVersionEnum::PREVIOUS_RELEASE:
        return $this->getPreviousMinorRelease();

      case DrupalCoreVersionEnum::PREVIOUS_DEV:
        return $this->getPreviousDevVersion();

      case DrupalCoreVersionEnum::CURRENT_RECOMMENDED:
        return $this->getCurrentRecommendedRelease();

      case DrupalCoreVersionEnum::CURRENT_DEV:
        return $this->getCurrentDevVersion();

      case DrupalCoreVersionEnum::NEXT_RELEASE:
        return $this->getNextRelease();

      case DrupalCoreVersionEnum::NEXT_DEV:
        return $this->getNextDevVersion();

      case DrupalCoreVersionEnum::D9_READINESS:
        return $this->getD9DevVersion();

      default:
        throw new LogicException(sprintf('Unknown version. Update %s:%s.', __CLASS__, __FUNCTION__));
    }
  }

  /**
   * Gets a formatted form of the Drupal core version for a given constant.
   *
   * @param \Acquia\Orca\Enum\DrupalCoreVersionEnum $version
   *   The Drupal core version constant.
   *
   * @return string
   *   The corresponding version string if found or a tilde (~) if not.
   */
  public function getPretty(DrupalCoreVersionEnum $version): string {
    try {
      return $this->get($version);
    }
    catch (RuntimeException $e) {
      return '~';
    }
  }

  /**
   * Finds the Drupal core version matching the given criteria.
   *
   * @param string|null $target_core_version
   *   The target core version.
   * @param string $minimum_stability
   *   The minimum stability. Available options (in order of stability) are
   *   dev, alpha, beta, RC, and stable.
   * @param string $preferred_stability
   *   The preferred stability. Available options (in order of stability) are
   *   dev, alpha, beta, RC, and stable.
   *
   * @return string
   *   The version string.
   */
  public function find(string $target_core_version = NULL, string $minimum_stability = 'stable', string $preferred_stability = 'stable'): string {
    $best_candidate = $this->getVersionSelector($minimum_stability)
      ->findBestCandidate('drupal/core', $target_core_version, NULL, $preferred_stability);
    if (!$best_candidate) {
      throw new RuntimeException(sprintf('No Drupal core version satisfies the given constraints: version=%s, minimum stability=%s', $target_core_version, $minimum_stability));
    }
    return $best_candidate->getPrettyVersion();
  }

  /**
   * Gets the latest release from the previous minor version.
   *
   * @return string
   *   The version string, e.g., "8.5.14.0".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersionEnum::PREVIOUS_RELEASE
   */
  private function getPreviousMinorRelease(): string {
    if ($this->previousMinorRelease) {
      return $this->previousMinorRelease;
    }
    $this->previousMinorRelease = $this->find("<{$this->getCurrentMinorVersion()}");
    return $this->previousMinorRelease;
  }

  /**
   * Gets the previous minor dev version.
   *
   * @return string
   *   The version string, e.g., "8.5.x-dev".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersionEnum::PREVIOUS_DEV
   */
  private function getPreviousDevVersion(): string {
    $previous_minor_version = (float) $this->getCurrentMinorVersion() - 0.1;
    return "{$previous_minor_version}.x-dev";
  }

  /**
   * Gets the current recommended release.
   *
   * @return string
   *   The version string, e.g., "8.6.14.0".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersionEnum::CURRENT_RECOMMENDED
   */
  private function getCurrentRecommendedRelease(): string {
    // @todo This is hardcoded in order to prevent 8.8.x being dropped from the
    //   version set when Drupal 9 is released, even though it continues to be
    //   a supported version. This quirk of major version rollovers needs to be
    //   solved before Drupal 10 comes out.
    return '8.9.0';
  }

  /**
   * Gets the current dev version.
   *
   * @return string
   *   The version string, e.g., "8.6.x-dev".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersionEnum::CURRENT_DEV
   */
  private function getCurrentDevVersion(): string {
    return "{$this->getCurrentMinorVersion()}.x-dev";
  }

  /**
   * Gets the latest pre-release version.
   *
   * @return string
   *   The version string, e.g., "8.7.0.0-beta2".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersionEnum::NEXT_RELEASE
   */
  private function getNextRelease(): string {
    // @todo This is hardcoded in order to prevent 9.0.x from becoming the "next the
    //   version" in the version set when Drupal 9 is released. This quirk of
    //   major version rollovers needs to be solved before Drupal 10 comes out.
    return '8.9.0';
  }

  /**
   * Gets the next minor dev version.
   *
   * @return string
   *   The version string, e.g., "8.7.x-dev".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersionEnum::NEXT_DEV
   */
  private function getNextDevVersion(): string {
    // @todo This is hardcoded in order to prevent 9.x-dev from becoming the
    //   "next the dev version" in the version set when Drupal 9 is released.
    //   This quirk of major version rollovers needs to be solved before Drupal
    //   10 comes out.
    return '8.9.x-dev';
  }

  /**
   * Gets the next D9 dev version.
   *
   * @return string
   *   The version string, e.g., "9.0.x-dev".
   *
   * @see \Acquia\Orca\Enum\DrupalCoreVersionEnum::D9_READINESS
   */
  private function getD9DevVersion(): string {
    return $this->find('~9.0.0', 'dev', 'dev');
  }

  /**
   * Gets a Composer version selector.
   *
   * @return \Composer\Package\Version\VersionSelector
   *   A Composer version selector.
   */
  private function getVersionSelector(): VersionSelector {
    $pool = $this->factory->createWithPackagistOnly();
    return new VersionSelector($pool);
  }

  /**
   * Gets the current minor version.
   *
   * @return string
   *   The version string, e.g., "8.6".
   */
  private function getCurrentMinorVersion(): string {
    return (string) (float) $this->getCurrentRecommendedRelease();
  }

}
