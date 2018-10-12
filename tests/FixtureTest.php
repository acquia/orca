<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Fixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy filesystem
 * @property string $rootPath
 */
class FixtureTest extends TestCase {

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->rootPath = '/var/www/orca/orca-build';
  }

  public function testConstruction() {
    $fixture = $this->createFixture();

    $this->assertTrue($fixture instanceof Fixture, 'Instantiated class.');
  }

  /**
   * @dataProvider providerDestroy
   */
  public function testDestroy($root_path) {
    $this->rootPath = $root_path;
    $this->filesystem
      ->remove($root_path);
    $fixture = $this->createFixture();

    $fixture->destroy();

    $this->filesystem
      ->remove($root_path)
      ->shouldHaveBeenCalledTimes(1);
  }

  public function providerDestroy() {
    return [
      ['/var/www/orca-build', TRUE],
      ['/tmp/test', FALSE],
    ];
  }

  /**
   * @dataProvider providerExists
   */
  public function testExists($root_path, $exists) {
    $this->rootPath = $root_path;
    $this->filesystem
      ->exists($root_path)
      ->willReturn($exists);
    $fixture = $this->createFixture();

    $return = $fixture->exists();

    $this->filesystem
      ->exists($root_path)
      ->shouldHaveBeenCalledTimes(1);
    $this->assertEquals($exists, $return, 'Returned correct value.');
  }

  public function providerExists() {
    return [
      ['/fixture-exists', TRUE],
      ['/no-fixture-there', FALSE],
    ];
  }

  /**
   * @dataProvider providerPathResolution
   */
  public function testPathResolution($root_path, $docroot_path) {
    $this->rootPath = $root_path;
    $fixture = $this->createFixture();

    $this->assertEquals($root_path, $fixture->rootPath(), 'Resolved root path.');
    $this->assertEquals($docroot_path, $fixture->docrootPath(), 'Resolved docroot path.');
  }

  public function providerPathResolution() {
    return [
      ['/var/www/orca-build', '/var/www/orca-build/docroot'],
      ['/tmp/test', '/tmp/test/docroot'],
    ];
  }

  protected function createFixture(): Fixture {
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    return new Fixture($filesystem, $this->rootPath);
  }

}
