<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job\Helper;

use Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Tests\TestCase;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionResolver
 * @coversDefaultClass \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker
 */
class RedundantJobCheckerTest extends TestCase {

  protected function setUp(): void {
    $this->drupalCoreVersionResolver = $this->prophesize(DrupalCoreVersionResolver::class);
  }

  private function createRedundantJobChecker(): RedundantJobChecker {
    $drupal_core_version_resolver = $this->drupalCoreVersionResolver->reveal();
    return new RedundantJobChecker($drupal_core_version_resolver);
  }

  /**
   * @dataProvider providerIsRedundant
   */
  public function testIsRedundant($ci_job, $oldest_supported, $latest_lts, $previous_minor, $is_redundant): void {
    $this->drupalCoreVersionResolver
      ->resolvePredefined(DrupalCoreVersionEnum::OLDEST_SUPPORTED())
      ->shouldBeCalledOnce()
      ->willReturn($oldest_supported);
    $this->drupalCoreVersionResolver
      ->resolvePredefined(DrupalCoreVersionEnum::LATEST_LTS())
      ->shouldBeCalledOnce()
      ->willReturn($latest_lts);
    $this->drupalCoreVersionResolver
      ->resolvePredefined(DrupalCoreVersionEnum::PREVIOUS_MINOR())
      ->shouldBeCalledOnce()
      ->willReturn($previous_minor);
    $checker = $this->createRedundantJobChecker();

    $actual_first = $checker->isRedundant($ci_job);
    // Call again to test value caching.
    $actual_second = $checker->isRedundant($ci_job);

    self::assertSame($is_redundant, $actual_first, 'Correctly determined duplicate status.');
    self::assertSame($is_redundant, $actual_second, 'Correctly cached return value.');
  }

  public function providerIsRedundant(): array {
    return [
      'No duplicates' => [
        'ci_job' => CiJobEnum::INTEGRATED_TEST_ON_OLDEST_SUPPORTED(),
        'oldest_supported' => '8.0.0',
        'latest_lts' => '9.0.0',
        'previous_minor' => '10.0.0',
        'is_redundant' => FALSE,
      ],
      'Oldest supported duplicates latest LTS' => [
        'ci_job' => CiJobEnum::INTEGRATED_TEST_ON_OLDEST_SUPPORTED(),
        'oldest_supported' => '8.0.0',
        'latest_lts' => '8.0.0',
        'previous_minor' => '9.0.0',
        'is_redundant' => TRUE,
      ],
      'Oldest supported duplicates previous minor' => [
        'ci_job' => CiJobEnum::INTEGRATED_TEST_ON_OLDEST_SUPPORTED(),
        'oldest_supported' => '8.0.0',
        'latest_lts' => '9.0.0',
        'previous_minor' => '8.0.0',
        'is_redundant' => TRUE,
      ],
      'Latest LTS duplicates previous minor' => [
        'ci_job' => CiJobEnum::INTEGRATED_TEST_ON_LATEST_LTS(),
        'oldest_supported' => '8.0.0',
        'latest_lts' => '9.0.0',
        'previous_minor' => '9.0.0',
        'is_redundant' => TRUE,
      ],
      'Previous minor duplicates latest LTS' => [
        'ci_job' => CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR(),
        'oldest_supported' => '8.0.0',
        'latest_lts' => '9.0.0',
        'previous_minor' => '9.0.0',
        'is_redundant' => TRUE,
      ],
      'All three are duplicates' => [
        'ci_job' => CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR(),
        'oldest_supported' => '8.0.0',
        'latest_lts' => '8.0.0',
        'previous_minor' => '8.0.0',
        'is_redundant' => TRUE,
      ],
      'Inapplicable job' => [
        'ci_job' => CiJobEnum::INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER(),
        'oldest_supported' => '8.0.0',
        'latest_lts' => '8.0.0',
        'previous_minor' => '8.0.0',
        'is_redundant' => FALSE,
      ],
    ];
  }

}
