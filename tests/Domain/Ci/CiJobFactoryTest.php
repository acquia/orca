<?php

namespace Acquia\Orca\Tests\Domain\Ci;

use Acquia\Orca\Domain\Ci\CiJobFactory;
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
use Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestFromPreviousMinorCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeTestToNextMajorBetaOrLaterCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeTestToNextMajorDevCiJob;
use Acquia\Orca\Domain\Ci\Job\LooseDeprecatedCodeScanCiJob;
use Acquia\Orca\Domain\Ci\Job\StaticCodeAnalysisCiJob;
use Acquia\Orca\Domain\Ci\Job\StrictDeprecatedCodeScanCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Domain\Ci\Job\DeprecatedCodeScanWContribCiJob|\Prophecy\Prophecy\ObjectProphecy $deprecatedCodeScanWContribCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnCurrentCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentDevCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnCurrentDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnLatestLtsCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnLatestLtsCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMajorLatestMinorBetaOrLaterCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnNextMajorLatestMinorBetaOrLaterCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMajorLatestMinorDevCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnNextMajorLatestMinorDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMinorCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnNextMinorCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMinorDevCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnNextMinorDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnOldestSupportedCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnOldestSupportedCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnPreviousMinorCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedTestOnPreviousMinorCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestFromPreviousMinorCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedUpgradeTestFromPreviousMinorCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedUpgradeTestToNextMinorCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorDevCiJob|\Prophecy\Prophecy\ObjectProphecy $integratedUpgradeTestToNextMinorDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedTestOnCurrentCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentDevCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedTestOnCurrentDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorDevCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedTestOnNextMajorLatestMinorDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedTestOnNextMinorCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMinorDevCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedTestOnNextMinorDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeTestToNextMajorBetaOrLaterCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedUpgradeToNextMajorBetaOrLaterCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeTestToNextMajorDevCiJob|\Prophecy\Prophecy\ObjectProphecy $isolatedUpgradeToNextMajorDevCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\LooseDeprecatedCodeScanCiJob|\Prophecy\Prophecy\ObjectProphecy $looseDeprecatedCodeScanCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\StaticCodeAnalysisCiJob|\Prophecy\Prophecy\ObjectProphecy $staticCodeAnalysisCiJob
 * @property \Acquia\Orca\Domain\Ci\Job\StrictDeprecatedCodeScanCiJob|\Prophecy\Prophecy\ObjectProphecy $strictDeprecatedCodeScanCiJob
 */
class CiJobFactoryTest extends TestCase {

  use CiEnumsTestTrait;

  protected function setUp(): void {
    $this->deprecatedCodeScanWContribCiJob = $this->prophesize(DeprecatedCodeScanWContribCiJob::class);
    $this->integratedTestOnCurrentCiJob = $this->prophesize(IntegratedTestOnCurrentCiJob::class);
    $this->integratedTestOnCurrentDevCiJob = $this->prophesize(IntegratedTestOnCurrentDevCiJob::class);
    $this->integratedTestOnLatestLtsCiJob = $this->prophesize(IntegratedTestOnLatestLtsCiJob::class);
    $this->integratedTestOnNextMajorLatestMinorBetaOrLaterCiJob = $this->prophesize(IntegratedTestOnNextMajorLatestMinorBetaOrLaterCiJob::class);
    $this->integratedTestOnNextMajorLatestMinorDevCiJob = $this->prophesize(IntegratedTestOnNextMajorLatestMinorDevCiJob::class);
    $this->integratedTestOnNextMinorCiJob = $this->prophesize(IntegratedTestOnNextMinorCiJob::class);
    $this->integratedTestOnNextMinorDevCiJob = $this->prophesize(IntegratedTestOnNextMinorDevCiJob::class);
    $this->integratedTestOnOldestSupportedCiJob = $this->prophesize(IntegratedTestOnOldestSupportedCiJob::class);
    $this->integratedTestOnPreviousMinorCiJob = $this->prophesize(IntegratedTestOnPreviousMinorCiJob::class);
    $this->integratedUpgradeTestFromPreviousMinorCiJob = $this->prophesize(IntegratedUpgradeTestFromPreviousMinorCiJob::class);
    $this->integratedUpgradeTestToNextMinorCiJob = $this->prophesize(IntegratedUpgradeTestToNextMinorCiJob::class);
    $this->integratedUpgradeTestToNextMinorDevCiJob = $this->prophesize(IntegratedUpgradeTestToNextMinorDevCiJob::class);
    $this->isolatedTestOnCurrentCiJob = $this->prophesize(IsolatedTestOnCurrentCiJob::class);
    $this->isolatedTestOnCurrentDevCiJob = $this->prophesize(IsolatedTestOnCurrentDevCiJob::class);
    $this->isolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob = $this->prophesize(IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob::class);
    $this->isolatedTestOnNextMajorLatestMinorDevCiJob = $this->prophesize(IsolatedTestOnNextMajorLatestMinorDevCiJob::class);
    $this->isolatedTestOnNextMinorCiJob = $this->prophesize(IsolatedTestOnNextMinorCiJob::class);
    $this->isolatedTestOnNextMinorDevCiJob = $this->prophesize(IsolatedTestOnNextMinorDevCiJob::class);
    $this->isolatedUpgradeToNextMajorBetaOrLaterCiJob = $this->prophesize(IsolatedUpgradeTestToNextMajorBetaOrLaterCiJob::class);
    $this->isolatedUpgradeToNextMajorDevCiJob = $this->prophesize(IsolatedUpgradeTestToNextMajorDevCiJob::class);
    $this->looseDeprecatedCodeScanCiJob = $this->prophesize(LooseDeprecatedCodeScanCiJob::class);
    $this->staticCodeAnalysisCiJob = $this->prophesize(StaticCodeAnalysisCiJob::class);
    $this->strictDeprecatedCodeScanCiJob = $this->prophesize(StrictDeprecatedCodeScanCiJob::class);
  }

  private function createFactory(): CiJobFactory {
    $deprecated_code_scan_w_contrib_ci_job = $this->deprecatedCodeScanWContribCiJob->reveal();
    $integrated_test_on_current_ci_job = $this->integratedTestOnCurrentCiJob->reveal();
    $integrated_test_on_current_dev_ci_job = $this->integratedTestOnCurrentDevCiJob->reveal();
    $integrated_test_on_latest_lts = $this->integratedTestOnLatestLtsCiJob->reveal();
    $integrated_test_on_next_major_latest_minor_beta_or_later_ci_job = $this->integratedTestOnNextMajorLatestMinorBetaOrLaterCiJob->reveal();
    $integrated_test_on_next_major_latest_minor_dev_ci_job = $this->integratedTestOnNextMajorLatestMinorDevCiJob->reveal();
    $integrated_test_on_next_minor_ci_job = $this->integratedTestOnNextMinorCiJob->reveal();
    $integrated_test_on_next_minor_dev_ci_job = $this->integratedTestOnNextMinorDevCiJob->reveal();
    $integrated_test_on_oldest_supported_ci_job = $this->integratedTestOnOldestSupportedCiJob->reveal();
    $integrated_test_on_previous_minor_ci_job = $this->integratedTestOnPreviousMinorCiJob->reveal();
    $integrated_upgrade_test_from_previous_minor = $this->integratedUpgradeTestFromPreviousMinorCiJob->reveal();
    $integrated_upgrade_test_to_next_minor_ci_job = $this->integratedUpgradeTestToNextMinorCiJob->reveal();
    $integrated_upgrade_test_to_next_minor_dev_ci_job = $this->integratedUpgradeTestToNextMinorDevCiJob->reveal();
    $isolated_test_on_current_ci_job = $this->isolatedTestOnCurrentCiJob->reveal();
    $isolated_test_on_current_dev_ci_job = $this->isolatedTestOnCurrentDevCiJob->reveal();
    $isolated_test_on_next_major_latest_minor_beta_or_later_ci_job = $this->isolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob->reveal();
    $isolated_test_on_next_major_latest_minor_dev_ci_job = $this->isolatedTestOnNextMajorLatestMinorDevCiJob->reveal();
    $isolated_test_on_next_minor_ci_job = $this->isolatedTestOnNextMinorCiJob->reveal();
    $isolated_test_on_next_minor_dev_ci_job = $this->isolatedTestOnNextMinorDevCiJob->reveal();
    $isolated_upgrade_test_to_next_major_beta_or_later_ci_job = $this->isolatedUpgradeToNextMajorBetaOrLaterCiJob->reveal();
    $isolated_upgrade_test_to_next_major_dev_ci_job = $this->isolatedUpgradeToNextMajorDevCiJob->reveal();
    $loose_deprecated_code_scan_ci_job = $this->looseDeprecatedCodeScanCiJob->reveal();
    $static_code_analysis_ci_job = $this->staticCodeAnalysisCiJob->reveal();
    $strict_deprecated_code_scan_ci_job = $this->strictDeprecatedCodeScanCiJob->reveal();
    return new CiJobFactory(
      $deprecated_code_scan_w_contrib_ci_job,
      $integrated_test_on_current_ci_job,
      $integrated_test_on_current_dev_ci_job,
      $integrated_test_on_latest_lts,
      $integrated_test_on_next_major_latest_minor_beta_or_later_ci_job,
      $integrated_test_on_next_major_latest_minor_dev_ci_job,
      $integrated_test_on_next_minor_ci_job,
      $integrated_test_on_next_minor_dev_ci_job,
      $integrated_test_on_oldest_supported_ci_job,
      $integrated_test_on_previous_minor_ci_job,
      $integrated_upgrade_test_from_previous_minor,
      $integrated_upgrade_test_to_next_minor_ci_job,
      $integrated_upgrade_test_to_next_minor_dev_ci_job,
      $isolated_test_on_current_ci_job,
      $isolated_test_on_current_dev_ci_job,
      $isolated_test_on_next_major_latest_minor_beta_or_later_ci_job,
      $isolated_test_on_next_major_latest_minor_dev_ci_job,
      $isolated_test_on_next_minor_ci_job,
      $isolated_test_on_next_minor_dev_ci_job,
      $isolated_upgrade_test_to_next_major_beta_or_later_ci_job,
      $isolated_upgrade_test_to_next_major_dev_ci_job,
      $loose_deprecated_code_scan_ci_job,
      $static_code_analysis_ci_job,
      $strict_deprecated_code_scan_ci_job
    );
  }

  /**
   * @dataProvider providerJobs
   */
  public function testCreate(CiJobEnum $expected): void {
    $factory = $this->createFactory();

    $job = $factory->create($expected);

    self::assertEquals($expected, $job->getJobName());
  }

}
