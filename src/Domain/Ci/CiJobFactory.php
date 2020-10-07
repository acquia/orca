<?php

namespace Acquia\Orca\Domain\Ci;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Domain\Ci\Job\DeprecatedCodeScanWContribCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnLatestLtsCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMajorLatestMinorBetaOrLaterCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMajorLatestMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMinorCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnOldestSupportedCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnPreviousMinorCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeToNextMajorBetaOrLaterCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeToNextMajorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\LooseDeprecatedCodeScanCiJob;
use Acquia\Orca\Domain\Ci\Job\StaticCodeAnalysisCiJob;
use Acquia\Orca\Domain\Ci\Job\StrictDeprecatedCodeScanCiJob;
use Acquia\Orca\Enum\CiJobEnum;

/**
 * Provides a factory for CI jobs.
 */
class CiJobFactory {

  /**
   * The CI jobs.
   *
   * @var \Acquia\Orca\Domain\Ci\Job\AbstractCiJob[]
   */
  private $jobs;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Ci\Job\DeprecatedCodeScanWContribCiJob $deprecated_code_scan_w_contrib_ci_job
   *   Static code analysis.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentCiJob $integrated_test_on_current_ci_job
   *   Integrated test on oldest supported Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentDevCiJob $integrated_test_on_current_dev_ci_job
   *   Integrated test on previous minor Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMajorLatestMinorBetaOrLaterCiJob $integrated_test_on_next_major_latest_minor_beta_or_later_ci_job
   *   Isolated test on current Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMajorLatestMinorDevCiJob $integrated_test_on_next_major_latest_minor_dev_ci_job
   *   Integrated test on current Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMinorCiJob $integrated_test_on_next_minor_ci_job
   *   Integrated upgrade test to next minor Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMinorDevCiJob $integrated_test_on_next_minor_dev_ci_job
   *   Integrated upgrade test to next minor dev Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnOldestSupportedCiJob $integrated_test_on_oldest_supported_ci_job
   *   Isolated test on current dev Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnPreviousMinorCiJob $integrated_test_on_previous_minor_ci_job
   *   Integrated test on current dev Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorCiJob $integrated_upgrade_test_to_next_minor_ci_job
   *   Loose deprecated code scan.
   * @param \Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorDevCiJob $integrated_upgrade_test_to_next_minor_dev_ci_job
   *   Strict deprecated code scan.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentCiJob $isolated_test_on_current_ci_job
   *   Deprecated code scan w/ contrib.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentDevCiJob $isolated_test_on_current_dev_ci_job
   *   Isolated test on next minor Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob $isolated_test_on_next_major_latest_minor_beta_or_later_ci_job
   *   Integrated test on next minor Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorDevCiJob $isolated_test_on_next_major_latest_minor_dev_ci_job
   *   Isolated test on next minor dev Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorCiJob $isolated_test_on_next_minor_ci_job
   *   Integrated test on next minor dev Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorDevCiJob $isolated_test_on_next_minor_dev_ci_job
   *   Isolated test on next major, latest minor beta-or-later Drupal core
   *   version.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeToNextMajorBetaOrLaterCiJob $isolated_upgrade_to_next_major_beta_or_later_ci_job
   *   Integrated test on next major, latest minor beta-or-later Drupal core
   *   version.
   * @param \Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeToNextMajorDevCiJob $isolated_upgrade_to_next_major_dev_ci_job
   *   Isolated test on next major, latest minor dev Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\LooseDeprecatedCodeScanCiJob $loose_deprecated_code_scan_ci_job
   *   Integrated test on next major, latest minor dev Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\StaticCodeAnalysisCiJob $static_code_analysis_ci_job
   *   Isolated upgrade to next major beta-or-later Drupal core version.
   * @param \Acquia\Orca\Domain\Ci\Job\StrictDeprecatedCodeScanCiJob $strict_deprecated_code_scan_ci_job
   *   Isolated upgrade to next major dev Drupal core version.
   */
  public function __construct(
    DeprecatedCodeScanWContribCiJob $deprecated_code_scan_w_contrib_ci_job,
    IntegratedTestOnCurrentCiJob $integrated_test_on_current_ci_job,
    IntegratedTestOnCurrentDevCiJob $integrated_test_on_current_dev_ci_job,
    IntegratedTestOnNextMajorLatestMinorBetaOrLaterCiJob $integrated_test_on_next_major_latest_minor_beta_or_later_ci_job,
    IntegratedTestOnNextMajorLatestMinorDevCiJob $integrated_test_on_next_major_latest_minor_dev_ci_job,
    IntegratedTestOnNextMinorCiJob $integrated_test_on_next_minor_ci_job,
    IntegratedTestOnNextMinorDevCiJob $integrated_test_on_next_minor_dev_ci_job,
    IntegratedTestOnOldestSupportedCiJob $integrated_test_on_oldest_supported_ci_job,
    IntegratedTestOnPreviousMinorCiJob $integrated_test_on_previous_minor_ci_job,
    IntegratedUpgradeTestToNextMinorCiJob $integrated_upgrade_test_to_next_minor_ci_job,
    IntegratedUpgradeTestToNextMinorDevCiJob $integrated_upgrade_test_to_next_minor_dev_ci_job,
    IsolatedTestOnCurrentCiJob $isolated_test_on_current_ci_job,
    IsolatedTestOnCurrentDevCiJob $isolated_test_on_current_dev_ci_job,
    IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob $isolated_test_on_next_major_latest_minor_beta_or_later_ci_job,
    IsolatedTestOnNextMajorLatestMinorDevCiJob $isolated_test_on_next_major_latest_minor_dev_ci_job,
    IsolatedTestOnNextMinorCiJob $isolated_test_on_next_minor_ci_job,
    IsolatedTestOnNextMinorDevCiJob $isolated_test_on_next_minor_dev_ci_job,
    IsolatedUpgradeToNextMajorBetaOrLaterCiJob $isolated_upgrade_to_next_major_beta_or_later_ci_job,
    IsolatedUpgradeToNextMajorDevCiJob $isolated_upgrade_to_next_major_dev_ci_job,
    LooseDeprecatedCodeScanCiJob $loose_deprecated_code_scan_ci_job,
    StaticCodeAnalysisCiJob $static_code_analysis_ci_job,
    StrictDeprecatedCodeScanCiJob $strict_deprecated_code_scan_ci_job
  ) {
    foreach (func_get_args() as $job) {
      $this->jobs[$job->getJobName()] = $job;
    }
  }

  /**
   * Creates a CI job.
   *
   * @param \Acquia\Orca\Enum\CiJobEnum $job_name
   *   The job name.
   *
   * @return \Acquia\Orca\Domain\Ci\Job\AbstractCiJob
   *   The CI job.
   */
  public function create(CiJobEnum $job_name): AbstractCiJob {
    return $this->jobs[$job_name->getKey()];
  }

}
