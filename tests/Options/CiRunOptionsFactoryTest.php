<?php

namespace Acquia\Orca\Tests\Options;

use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Options\CiRunOptionsFactory;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use Acquia\Orca\Tests\TestCase;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 *
 * @coversDefaultClass \Acquia\Orca\Options\FixtureOptionsFactory
 */
class CiRunOptionsFactoryTest extends TestCase {

  use CiEnumsTestTrait;

  protected function setUp(): void {
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists($this->validSutName())
      ->willReturn(TRUE);
  }

  private function createFixtureOptionsFactory(): CiRunOptionsFactory {
    $package_manager = $this->packageManager->reveal();
    return new CiRunOptionsFactory($package_manager);
  }

  private function validSutName(): string {
    return 'drupal/example';
  }

  public function testFactory(): void {
    $factory = $this->createFixtureOptionsFactory();

    $options = $factory->create([
      'job' => $this->validJobName(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ]);

    self::assertEquals($this->validJobName(), $options->getJob());
    self::assertEquals($this->validPhaseName(), $options->getPhase());
  }

}
