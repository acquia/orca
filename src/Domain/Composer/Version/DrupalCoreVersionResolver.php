<?php

namespace Acquia\Orca\Domain\Composer\Version;

use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Composer\Package\PackageInterface;

/**
 * Finds a range of Drupal core versions.
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
   * The latest LTS Drupal core version.
   *
   * @var string|null
   */
  private $latestLts;

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
   * The next minor dev version of Drupal core.
   *
   * @var string|null
   */
  private $nextMinorDev;

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
   * The version selector factory.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory
   */
  private $versionSelectorFactory;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalDotOrgApiClient $drupal_dot_org_api_client
   *   The Drupal.org API client.
   * @param \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory $version_selector_factory
   *   The version selector factory.
   */
  public function __construct(DrupalDotOrgApiClient $drupal_dot_org_api_client, VersionSelectorFactory $version_selector_factory) {
    $this->drupalDotOrgApiClient = $drupal_dot_org_api_client;
    $this->versionSelectorFactory = $version_selector_factory;
  }

  /**
   * Determines if a given version keyword resolves to a version that exists.
   *
   * @param \Acquia\Orca\Enum\DrupalCoreVersionEnum $version
   *   The Drupal core version constant.
   *
   * @return bool
   *   TRUE if the version exists or FALSE if not.
   */
  public function existsPredefined(DrupalCoreVersionEnum $version): bool {
    try {
      $this->resolvePredefined($version);
    }
    catch (OrcaVersionNotFoundException $e) {
      return FALSE;
    }
    return TRUE;
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
  public function resolvePredefined(DrupalCoreVersionEnum $version): string {
    switch ($version->getValue()) {
      case DrupalCoreVersionEnum::OLDEST_SUPPORTED():
        return $this->findOldestSupported();

      case DrupalCoreVersionEnum::LATEST_LTS():
        return $this->findLatestLts();

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
   * Finds the Drupal core version matching the given arbitrary criteria.
   *
   * @param string $version
   *   The core version constraint.
   * @param string $preferred_stability
   *   The stability, both minimum and preferred. Available options (in order of
   *   stability) are dev, alpha, beta, RC, and stable.
   * @param bool $dev
   *   TRUE to allow dev stability results or FALSE not to.
   *
   * @return string
   *   The version string.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function resolveArbitrary(string $version, string $preferred_stability = 'stable', bool $dev = TRUE): string {
    $selector = $this->versionSelectorFactory->create(TRUE, $dev);
    /* @phan-suppress-next-line PhanTypeMismatchArgumentProbablyReal */
    $package = $selector->findBestCandidate('drupal/core', $version, $preferred_stability);
    if ($package instanceof PackageInterface) {
      return $package->getPrettyVersion();
    }
    $message = sprintf(
      'No Drupal core version satisfies the given constraints: version=%s, stability=%s',
      $version,
      $preferred_stability
    );
    throw new OrcaVersionNotFoundException($message);
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
    $this->oldestSupported = $this->resolveArbitrary($branch, 'stable');

    return $this->oldestSupported;
  }

  /**
   * Finds the latest LTS version of Drupal core.
   *
   * @return string
   *   The semver version string, e.g., 8.9.7.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findLatestLts(): string {
    if ($this->latestLts) {
      return $this->latestLts;
    }

    $parts = explode('.', $this->findCurrent());
    $current_major = array_shift($parts);

    // Gets the oldest supported version of Drupal Core.
    $oldestSupported = $this->findOldestSupported();
    // If oldest supported Drupal Core version is less than current major.
    if ((int) $oldestSupported < (int) $current_major) {
      $this->latestLts = $oldestSupported;
      return $this->latestLts;
    }

    $message = "No Drupal core version satisfies the given constraints: oldest supported ($oldestSupported) less than current major ($current_major)";
    throw new OrcaVersionNotFoundException($message);
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
      ->resolveArbitrary("<{$current_minor}", 'stable');
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
      $candidate = $this->resolveArbitrary('*', 'stable');
    }
    catch (OrcaVersionNotFoundException $e) {
      throw new \LogicException('Could not find current version of Drupal core.');
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
      ->resolveArbitrary("~{$this->findNextMinorUnresolved()}", 'alpha', FALSE);

    return $this->nextMinor;
  }

  /**
   * Finds the next minor version unresolved, e.g., 9.2.0 for 9.1.7.
   *
   * @return string
   *   The semver string.
   */
  private function findNextMinorUnresolved(): string {
    $current_minor = (float) ($this->findCurrent());
    $current_minor = (string) ($current_minor + 0.1);
    return "{$current_minor}.0";
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
    if ($this->nextMinorDev) {
      return $this->nextMinorDev;
    }

    $next_minor = $this->findNextMinorUnresolved();
    $this->nextMinorDev = $this->convertToDev($next_minor);

    return $this->nextMinorDev;
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
      ->resolveArbitrary("^{$major}", 'beta');

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
      ->resolveArbitrary("^{$major}", 'dev');

    return $this->nextMajorLatestMinorDev;
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
