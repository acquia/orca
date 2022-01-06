<?php

namespace Acquia\Orca\Domain\Ci\Job\Helper;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;

/**
 * Determines whether essentially redundant jobs exist.
 */
class RedundantJobChecker {

  /**
   * The Drupal core version resolver.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $drupalCoreVersionResolver;

  /**
   * The cached return value.
   *
   * @var bool
   */
  private $isRedundant;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_resolver
   *   The Drupal core version resolver.
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_resolver) {
    $this->drupalCoreVersionResolver = $drupal_core_version_resolver;
  }

  /**
   * Determines whether or not the given CI job is redundant.
   *
   * @param \Acquia\Orca\Enum\CiJobEnum $ci_job
   *   The CI job in question.
   *
   * @return bool
   *   TRUE if the given job is redundant or FALSE if not.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function isRedundant(CiJobEnum $ci_job): bool {
    if (isset($this->isRedundant)) {
      return $this->isRedundant;
    }

    $resolved_versions = $this->getResolvedVersions();

    // Ignore jobs that aren't subject to duplication.
    if (!array_key_exists($ci_job->getKey(), $resolved_versions)) {
      return $this->cacheAndReturn(FALSE);
    }

    $given_version = $resolved_versions[$ci_job->getKey()];
    unset($resolved_versions[$ci_job->getKey()]);
    if (in_array($given_version, $resolved_versions, TRUE)) {
      return $this->cacheAndReturn(TRUE);
    }

    return $this->cacheAndReturn(FALSE);
  }

  /**
   * Gets the resolved versions of the potential duplicates.
   *
   * @return string[]
   *   The resolved version strings keyed by CI job key.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function getResolvedVersions(): array {
    /** @var \Acquia\Orca\Enum\DrupalCoreVersionEnum[] $ci_jobs */
    $ci_jobs = [
      CiJobEnum::INTEGRATED_TEST_ON_OLDEST_SUPPORTED => CiJobEnum::INTEGRATED_TEST_ON_OLDEST_SUPPORTED()
        ->getDrupalCoreVersion(),
      CiJobEnum::INTEGRATED_TEST_ON_LATEST_LTS => CiJobEnum::INTEGRATED_TEST_ON_LATEST_LTS()
        ->getDrupalCoreVersion(),
      CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR => CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR()
        ->getDrupalCoreVersion(),
    ];
    foreach ($ci_jobs as $key => $ci_job) {
      try {
        /* @phan-suppress-next-line PhanTypeMismatchArgumentNullable */
        $job = $this->drupalCoreVersionResolver->resolvePredefined($ci_job);
      }
      catch (OrcaVersionNotFoundException $e) {
        // Some versions may not exist, such as LTS. Ignore these.
        continue;
      }
      $ci_jobs[$key] = $job;
    }
    return $ci_jobs;
  }

  /**
   * Caches a given value and returns it.
   *
   * @param bool $value
   *   The value.
   *
   * @return bool
   *   The value.
   */
  private function cacheAndReturn(bool $value): bool {
    $this->isRedundant = $value;
    return $this->isRedundant;
  }

}
