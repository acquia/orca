<?php

namespace Acquia\Orca\Drupal;

use Composer\DependencyResolver\Pool;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\RepositoryFactory;
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
   * Gets the Drupal core version for a given constant.
   *
   * @param \Acquia\Orca\Drupal\DrupalCoreVersion $version
   *   The Drupal core version constant.
   *
   * @return string
   *   The corresponding version string.
   *
   * @throws \RuntimeException
   *   If no corresponding version is found.
   */
  public function get(DrupalCoreVersion $version): string {
    switch ($version->getValue()) {
      case DrupalCoreVersion::PREVIOUS_RELEASE:
        return $this->getPreviousMinorRelease();

      case DrupalCoreVersion::PREVIOUS_DEV:
        return $this->getPreviousDevVersion();

      case DrupalCoreVersion::CURRENT_RECOMMENDED:
        return $this->getCurrentRecommendedRelease();

      case DrupalCoreVersion::CURRENT_DEV:
        return $this->getCurrentDevVersion();

      case DrupalCoreVersion::NEXT_RELEASE:
        return $this->getNextRelease();

      case DrupalCoreVersion::NEXT_DEV:
        return $this->getNextDevVersion();

      case DrupalCoreVersion::D9_READINESS:
        return $this->getD9DevVersion();

      default:
        throw new LogicException(sprintf('Unknown version. Update %s:%s.', __CLASS__, __FUNCTION__));
    }
  }

  /**
   * Gets a formatted form of the Drupal core version for a given constant.
   *
   * @param \Acquia\Orca\Drupal\DrupalCoreVersion $version
   *   The Drupal core version constant.
   *
   * @return string
   *   The corresponding version string if found or a tilde (~) if not.
   */
  public function getPretty(DrupalCoreVersion $version): string {
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
   * @see \Acquia\Orca\Drupal\DrupalCoreVersion::PREVIOUS_RELEASE
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
   * @see \Acquia\Orca\Drupal\DrupalCoreVersion::PREVIOUS_DEV
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
   * @see \Acquia\Orca\Drupal\DrupalCoreVersion::CURRENT_RECOMMENDED
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
   * @see \Acquia\Orca\Drupal\DrupalCoreVersion::CURRENT_DEV
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
   * @see \Acquia\Orca\Drupal\DrupalCoreVersion::NEXT_RELEASE
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
   * @see \Acquia\Orca\Drupal\DrupalCoreVersion::NEXT_DEV
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
   * @see \Acquia\Orca\Drupal\DrupalCoreVersion::D9_READINESS
   */
  private function getD9DevVersion(): string {
    return $this->find('~9.0.0', 'dev', 'dev');
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
    return (string) (float) $this->getCurrentRecommendedRelease();
  }

}
