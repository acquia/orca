<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Domain\Fixture\FixtureOptions;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionFinder
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 *
 * @coversDefaultClass \Acquia\Orca\Domain\Fixture\FixtureOptions
 */
class FixtureOptionsTest extends TestCase {

  public function setUp(): void {
    $this->drupalCoreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists(Argument::any())
      ->willReturn(TRUE);
  }

  private function createFixtureOptions(array $options): FixtureOptions {
    $drupal_core_version_finder = $this->drupalCoreVersionFinder->reveal();
    $package_manager = $this->packageManager->reveal();
    return new FixtureOptions($drupal_core_version_finder, $package_manager, $options);
  }

  /**
   * @covers ::__construct
   * @covers ::force
   * @covers ::getCore
   * @covers ::getProfile
   * @covers ::getProjectTemplate
   * @covers ::getSut
   * @covers ::hasSut
   * @covers ::ignorePatchFailure
   * @covers ::installSite
   * @covers ::isBare
   * @covers ::isDev
   * @covers ::isSutOnly
   * @covers ::isValidSutValue
   * @covers ::preferSource
   * @covers ::resolve
   * @covers ::symlinkAll
   * @covers ::useSqlite
   * @covers ::validate
   */
  public function testDefaults(): void {
    $core = '9.0.0';
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersionEnum(DrupalCoreVersionEnum::CURRENT_RECOMMENDED))
      ->willReturn($core);

    $options = $this->createFixtureOptions([]);

    self::assertFalse($options->force(), 'Set/got default "force" option.');
    self::assertFalse($options->hasSut(), 'Detected absence of "sut" option.');
    self::assertFalse($options->ignorePatchFailure(), 'Set/got default "ignore-patch-failure" option.');
    self::assertFalse($options->isBare(), 'Set/got default "bare" option.');
    self::assertFalse($options->isDev(), 'Set/got default "dev" option.');
    self::assertFalse($options->isSutOnly(), 'Set/got default "sut-only" option.');
    self::assertFalse($options->preferSource(), 'Set/got default "prefer-source" option.');
    self::assertFalse($options->symlinkAll(), 'Set/got default "symlink-all" option.');
    self::assertEquals($core, $options->getCore(), 'Set/got default "core" option.');
    self::assertNull($options->getProfile(), 'Set/got default "profile" option.');
    self::assertEquals('acquia/drupal-recommended-project', $options->getProjectTemplate(), 'Set/got default "project-template" option.');
    self::assertNull($options->getSut(), 'Set/got default "sut" option.');
    self::assertTrue($options->installSite(), 'Set/got default "no-site-install" option.');
    self::assertTrue($options->useSqlite(), 'Set/got default "no-sqlite" option.');
  }

  /**
   * @covers ::__construct
   * @covers ::force
   * @covers ::getCore
   * @covers ::getProfile
   * @covers ::getProjectTemplate
   * @covers ::getSut
   * @covers ::hasSut
   * @covers ::ignorePatchFailure
   * @covers ::installSite
   * @covers ::isBare
   * @covers ::isDev
   * @covers ::isSutOnly
   * @covers ::preferSource
   * @covers ::resolve
   * @covers ::symlinkAll
   * @covers ::useSqlite
   * @covers ::validate
   */
  public function testValidOptions(): void {
    $profile = 'example';
    $project_template = 'lorem/ipsum';
    $sut_name = 'dolor/sit';
    $sut_object = $this->prophesize(Package::class);
    $sut_object
      ->getPackageName()
      ->willReturn($sut_name);
    $sut_object = $sut_object->reveal();
    $this->packageManager
      ->get($sut_name)
      ->willReturn($sut_object);
    $core = '10.0.0';

    $options1 = $this->createFixtureOptions([
      'core' => $core,
      'dev' => TRUE,
      'force' => TRUE,
      'ignore-patch-failure' => TRUE,
      'no-site-install' => TRUE,
      'no-sqlite' => TRUE,
      'prefer-source' => TRUE,
      'profile' => $profile,
      'project-template' => $project_template,
      'sut' => $sut_name,
      'sut-only' => TRUE,
      'symlink-all' => TRUE,
    ]);
    $options2 = $this->createFixtureOptions([
      'bare' => TRUE,
    ]);

    self::assertEquals($profile, $options1->getProfile(), 'Set/got "profile" option.');
    self::assertEquals($project_template, $options1->getProjectTemplate(), 'Set/got "project-template" option.');
    self::assertEquals($sut_object, $options1->getSut(), 'Set/got "sut" option.');
    self::assertEquals($core, $options1->getCore(), 'Set/got "core" option.');
    self::assertFalse($options1->installSite(), 'Set/got "no-site-install" option.');
    self::assertFalse($options1->useSqlite(), 'Set/got "no-sqlite" option.');
    self::assertTrue($options1->force(), 'Set/got "force" option.');
    self::assertTrue($options1->hasSut(), 'Detected presence of "sut" option.');
    self::assertTrue($options1->ignorePatchFailure(), 'Set/got "ignore-patch-failure" option.');
    self::assertTrue($options1->isDev(), 'Set/got "dev" option.');
    self::assertTrue($options1->isSutOnly(), 'Set/got "sut-only" option.');
    self::assertTrue($options1->preferSource(), 'Set/got "prefer-source" option.');
    self::assertTrue($options1->symlinkAll(), 'Set/got "symlink-all" option.');
    self::assertTrue($options2->isBare(), 'Set/got "bare" option.');
  }

  /**
   * @covers ::resolve
   */
  public function testUndefinedOptions(): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    $this->createFixtureOptions(['undefined' => 'option']);
  }

  /**
   * @dataProvider providerInvalidOptions
   *
   * @covers ::resolve
   */
  public function testInvalidOptions($option): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    $this->createFixtureOptions([$option => 12345]);
  }

  public function providerInvalidOptions(): array {
    return [
      ['bare'],
      ['core'],
      ['dev'],
      ['force'],
      ['ignore-patch-failure'],
      ['prefer-source'],
      ['profile'],
      ['project-template'],
      ['no-site-install'],
      ['no-sqlite'],
      ['sut'],
      ['sut-only'],
      ['symlink-all'],
    ];
  }

  /**
   * @dataProvider providerInvalidCombinations
   *
   * @covers ::validate
   */
  public function testInvalidCombinations($options, $message): void {
    $sut_object = $this->prophesize(Package::class);
    $sut_object = $sut_object->reveal();
    $this->packageManager
      ->get(Argument::any())
      ->willReturn($sut_object);
    $this->expectExceptionObject(new OrcaInvalidArgumentException($message));

    $this->createFixtureOptions($options);
  }

  public function providerInvalidCombinations(): array {
    return [
      [['bare' => TRUE, 'sut' => 'test/example'], 'Cannot create a bare fixture with a SUT.'],
      [['bare' => TRUE, 'symlink-all' => TRUE], 'Cannot symlink all in a bare fixture.'],
      [['sut-only' => TRUE], 'Cannot create a SUT-only fixture without a SUT.'],
    ];
  }

  /**
   * @dataProvider providerCoreRawConstraintsValid
   *
   * @covers ::findCoreVersion
   * @covers ::getCore
   * @covers ::isValidCoreValue
   * @covers ::resolve
   */
  public function testCoreRawConstraintsValid($version): void {
    $options = $this->createFixtureOptions(['core' => $version]);

    self::assertEquals($version, $options->getCore(), 'Accepted valid "core" option.');
  }

  public function providerCoreRawConstraintsValid(): array {
    return [
      ['9.0.0'],
      ['1.2.3'],
    ];
  }

  /**
   * @covers ::findCoreVersion
   * @covers ::getCore
   * @covers ::isValidCoreValue
   * @covers ::resolve
   */
  public function testCoreRawConstraintInvalid(): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    $this->createFixtureOptions(['core' => 'invalid']);
  }

  /**
   * @dataProvider providerCoreConstantValid
   *
   * @covers ::findCoreVersion
   * @covers ::getCore
   * @covers ::isValidCoreValue
   * @covers ::resolve
   */
  public function testCoreConstantValid($version): void {
    $expected = '10.0.0';
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersionEnum($version))
      ->shouldBeCalledOnce()
      ->willReturn($expected);

    $options = $this->createFixtureOptions(['core' => $version]);
    // Call once to test essential functionality.
    $core = $options->getCore();
    // Call again to test value caching.
    $options->getCore();

    self::assertEquals($expected, $core, 'Accepted valid "core" constant option.');
  }

  public function providerCoreConstantValid(): array {
    // Return all Drupal core version constants.
    $array = [];
    foreach (DrupalCoreVersionEnum::keys() as $value) {
      $array[] = [$value];
    }
    return $array;
  }

  /**
   * @dataProvider providerCoreResolvedRange
   *
   * @covers ::getCoreResolved
   * @covers ::resolve
   */
  public function testCoreResolvedRange($core, $dev, $stability): void {
    $this->drupalCoreVersionFinder
      ->find($core, $stability, $stability)
      ->shouldBeCalledOnce()
      ->willReturn('string');

    $options = $this->createFixtureOptions([
      'core' => $core,
      'dev' => $dev,
    ]);
    // Call once to test essential functionality.
    $options->getCoreResolved();
    // Call again to test value caching.
    $options->getCoreResolved();
  }

  public function providerCoreResolvedRange(): array {
    return [
      ['~8', FALSE, 'stable'],
      ['8.0.0@dev', FALSE, 'stable'],
      ['8.0.x-dev', FALSE, 'stable'],
      ['>8 <9', FALSE, 'stable'],
      ['~9', FALSE, 'stable'],
      ['~9', TRUE, 'dev'],
    ];
  }

  /**
   * @covers ::findCoreVersion
   * @covers ::getCore
   * @covers ::isValidCoreValue
   * @covers ::resolve
   */
  public function testCoreDefaultDev(): void {
    $core = '10.0.0';
    $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersionEnum(DrupalCoreVersionEnum::CURRENT_DEV))
      ->shouldBeCalledOnce()
      ->willReturn($core);

    $options = $this->createFixtureOptions(['dev' => TRUE]);

    self::assertEquals($core, $options->getCore(), 'Got correct default dev core version.');
  }

  /**
   * @dataProvider providerProfileValid
   *
   * @covers ::isValidProfileValue
   * @covers ::resolve
   */
  public function testProfileValid($name): void {
    $options = $this->createFixtureOptions(['profile' => $name]);

    self::assertEquals($name, $options->getProfile(), 'Accepted valid "profile" option.');
  }

  public function providerProfileValid(): array {
    return [
      [NULL],
      ['abc'],
      ['test_example'],
      ['test123'],
      ['lorem_ipsum_dolor_sit_amet_consectetur_adipiscing_elit'],
    ];
  }

  /**
   * @dataProvider providerProfileInvalid
   *
   * @covers ::isValidProfileValue
   * @covers ::resolve
   */
  public function testProfileInvalid($name): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    $options = $this->createFixtureOptions(['profile' => $name]);

    self::assertEquals($name, $options->getProfile(), 'Accepted valid "profile" option.');
  }

  public function providerProfileInvalid(): array {
    return [
      [''],
      ['ab'],
      [' test '],
      ['_test'],
      ['test example'],
      ['test.example'],
      ['123test'],
      ['Test'],
    ];
  }

  /**
   * @covers ::isValidProjectTemplateValue
   * @covers ::resolve
   */
  public function testProjectTemplateValid(): void {
    $name = 'test/example';
    $options = $this->createFixtureOptions(['project-template' => $name]);

    self::assertEquals($name, $options->getProjectTemplate(), 'Accepted valid "project-template" option.');
  }

  /**
   * @covers ::isValidProjectTemplateValue
   * @covers ::resolve
   */
  public function testProjectTemplateInvalid(): void {
    $this->expectException(OrcaInvalidArgumentException::class);

    $this->createFixtureOptions(['project-template' => 'invalid']);
  }

  /**
   * @dataProvider providerProjectTemplateSelectionByCoreVersion
   *
   * @covers ::getCoreResolved
   * @covers ::getProjectTemplate
   * @covers ::resolve
   */
  public function testProjectTemplateSelectionByCoreVersion($core, $expected): void {
    $this->drupalCoreVersionFinder
      ->find($core, Argument::any(), Argument::any())
      ->willReturnArgument();

    $options = $this->createFixtureOptions(['core' => $core]);

    self::assertEquals($expected, $options->getProjectTemplate(), 'Selected correct project template for core version.');
  }

  public function providerProjectTemplateSelectionByCoreVersion(): array {
    return [
      ['8.0.0@dev', 'acquia/blt-project'],
      ['8.0.x-dev', 'acquia/blt-project'],
      ['8.0.0', 'acquia/blt-project'],
      ['8.0.x-dev', 'acquia/blt-project'],
      ['9.0.0', 'acquia/drupal-recommended-project'],
      ['9.0.0@dev', 'acquia/drupal-recommended-project'],
    ];
  }

  /**
   * @covers ::getSut
   * @covers ::hasSut
   * @covers ::isValidSutValue
   * @covers ::resolve
   */
  public function testHasSut(): void {
    $name = 'test/example';
    $package = $this->prophesize(Package::class)->reveal();
    $this->packageManager
      ->get($name)
      ->shouldBeCalled()
      ->willReturn($package);

    $options = $this->createFixtureOptions(['sut' => $name]);

    self::assertEquals($package, $options->getSut());
    self::assertEquals(TRUE, $options->hasSut());
  }

  /**
   * @covers ::isValidSutValue
   * @covers ::resolve
   */
  public function testUnknownSut(): void {
    $name = 'test/example';
    $this->packageManager
      ->exists($name)
      ->shouldBeCalled()
      ->willReturn(FALSE);
    $this->expectException(OrcaInvalidArgumentException::class);

    $this->createFixtureOptions(['sut' => $name]);
  }

}
