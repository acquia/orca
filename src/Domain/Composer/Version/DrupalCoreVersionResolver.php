<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Composer\Package\PackageInterface;
use LogicException;

/**
 * Resolves Drupal core version constants to concrete version strings.
 */
class DrupalCoreVersionResolver {

  /**
   * The current Drupal core version.
   *
   * @var string|null
   */
  private $current;

  /**
   * The Drupal.org API client.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient
   */
  private $drupalDotOrgApiClient;

  /**
   * The next major latest minor Drupal core version beta or later.
   *
   * @var string|null
   */
  private $nextMajorLatestMinorBetaOrLater;

  /**
   * The next major latest minor dev version of Drupal core.
   *
   * @var string|null
   */
  private $nextMajorLatestMinorDev;

  /**
   * The next minor version of Drupal core.
   *
   * @var string|null
   */
  private $nextMinor;

  /**
   * The oldest supported version of Drupal core.
   *
   * @var string|null
   */
  private $oldestSupported;

  /**
   * The previous minor Drupal core version.
   *
   * @var string|null
   */
  private $previousMinor;

  /**
   * The version selector.
   *
   * @var \Composer\Package\Version\VersionSelector
   */
  private $selector;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient $drupal_to_org_api_client
   *   The Drupal.org API client.
   * @param \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory $version_selector_factory
   *   The version selector factory.
   */
  public function __construct(DrupalDotOrgApiClient $drupal_to_org_api_client, VersionSelectorFactory $version_selector_factory) {
    $this->drupalDotOrgApiClient = $drupal_to_org_api_client;
    $this->selector = $version_selector_factory->createWithPackagistOnly();
  }

  /**
   * Resolves a given version keyword to a concrete version string.
   *
   * @param \Acquia\Orca\Enum\DrupalCoreVersionEnum $version
   *   The Drupal core version constant.
   *
   * @return string
   *   The resolved version string.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function resolve(DrupalCoreVersionEnum $version): ?string {
    switch ($version->getValue()) {
      case DrupalCoreVersionEnum::OLDEST_SUPPORTED():
        return $this->findOldestSupported();

      case DrupalCoreVersionEnum::PREVIOUS_MINOR():
        return $this->findPreviousMinor();

      case DrupalCoreVersionEnum::CURRENT():
      default:
        return $this->findCurrent();

      case DrupalCoreVersionEnum::CURRENT_DEV():
        return $this->findCurrentDev();

      case DrupalCoreVersionEnum::NEXT_MINOR():
        return $this->findNextMinor();

      case DrupalCoreVersionEnum::NEXT_MINOR_DEV():
        return $this->findNextMinorDev();

      case DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER():
        return $this->findNextMajorLatestMinorBetaOrLater();

      case DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_DEV():
        return $this->findNextMajorLatestMinorDev();

    }
  }

  /**
   * Finds the oldest supported version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 10.0.0-dev.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findOldestSupported(): string {
    if ($this->oldestSupported) {
      return $this->oldestSupported;
    }

    $branch = $this->drupalDotOrgApiClient->getOldestSupportedDrupalCoreBranch();
    $this->oldestSupported = $this->findBestCandidate($branch, 'stable');

    return $this->oldestSupported;
  }

  /**
   * Finds the previous minor version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 9.1.x-dev.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findPreviousMinor(): string {
    if ($this->previousMinor) {
      return $this->previousMinor;
    }

    $parts = explode('.', $this->findCurrent());
    array_pop($parts);
    $current_minor = implode('.', $parts);
    $this->previousMinor = $this
      ->findBestCandidate("<{$current_minor}", 'stable');
    return $this->previousMinor;
  }

  /**
   * Finds the current version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 9.1.0.
   */
  private function findCurrent(): string {
    if ($this->current) {
      return $this->current;
    }

    try {
      $candidate = $this->findBestCandidate('*', 'stable');
    }
    catch (OrcaVersionNotFoundException $e) {
      throw new LogicException('Could not find current version of Drupal core.');
    }
    $this->current = $candidate;

    return $this->current;
  }

  /**
   * Finds the current dev version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 9.1.x-dev.
   */
  private function findCurrentDev(): string {
    return $this->convertToDev($this->findCurrent());
  }

  /**
   * Finds the next minor version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 9.1.x-alpha.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findNextMinor(): string {
    if ($this->nextMinor) {
      return $this->nextMinor;
    }

    $this->nextMinor = $this
      ->findBestCandidate(">{$this->findCurrent()}", 'alpha');

    return $this->nextMinor;
  }

  /**
   * Finds the next minor dev version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 9.1.x-alpha1.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findNextMinorDev(): string {
    return $this->convertToDev($this->findNextMinor());
  }

  /**
   * Finds the next major, latest minor version of Drupal core beta-or-later.
   *
   * @return string
   *   The semver version string, e.g., 10.0.0-beta1.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findNextMajorLatestMinorBetaOrLater(): string {
    if ($this->nextMajorLatestMinorBetaOrLater) {
      return $this->nextMajorLatestMinorBetaOrLater;
    }

    $parts = explode('.', $this->findCurrent());
    $major = $parts[0];
    $major++;

    $this->nextMajorLatestMinorBetaOrLater = $this
      ->findBestCandidate("^{$major}", 'beta');

    return $this->nextMajorLatestMinorBetaOrLater;
  }

  /**
   * Finds the next major, latest minor dev version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 10.0.0-dev.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findNextMajorLatestMinorDev(): string {
    if ($this->nextMajorLatestMinorDev) {
      return $this->nextMajorLatestMinorDev;
    }

    $parts = explode('.', $this->findCurrent());
    $major = $parts[0];
    $major++;

    $this->nextMajorLatestMinorDev = $this
      ->findBestCandidate("^{$major}", 'dev');

    return $this->nextMajorLatestMinorDev;
  }

  /**
   * Finds the best candidate for a given Drupal core version constraint.
   *
   * @param string $constraint
   *   A Composer version constraint.
   * @param string $stability
   *   The minimum stability. Available options (in order of stability) are
   *   dev, alpha, beta, RC, and stable.
   *
   * @return string|null
   *   The semver version string if available, e.g., 9.1.x-dev, or NULL is not.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findBestCandidate(string $constraint, string $stability): ?string {
    $package = $this->selector
      ->findBestCandidate('drupal/core', $constraint, NULL, $stability);
    if ($package instanceof PackageInterface) {
      return $package->getPrettyVersion();
    }
    throw new OrcaVersionNotFoundException('There is no available version matching the given constraints.');
  }

  /**
   * Converts a given version to its corresponding dev version.
   *
   * @param string $version
   *   The version to convert.
   *
   * @return string
   *   The converted version, e.g., 9.1.x-dev.
   */
  private function convertToDev(string $version): string {
    $parts = explode('.', $version);
    array_pop($parts);
    $parts[] = 'x-dev';
    return implode('.', $parts);
  }

}
