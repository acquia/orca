<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\ConfigLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Acquia\Orca\Fixture\Fixture
 *
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Utility\ConfigLoader $configLoader
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property string $rootPath
 */
class FixtureTest extends TestCase {

  protected function setUp() {
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->rootPath = '/var/www/orca-build';
  }

  public function testConstruction() {
    $fixture = $this->createFixture();

    $this->assertTrue($fixture instanceof Fixture, 'Instantiated class.');
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
   * @dataProvider providerGetPath
   */
  public function testGetPath($root_path, $sub_path, $expected) {
    $this->rootPath = $root_path;
    $fixture = $this->createFixture();

    $this->assertEquals($expected, $fixture->getPath($sub_path), 'Resolved path.');
  }

  public function providerGetPath() {
    return [
      ['/var/www/orca-build', NULL, '/var/www/orca-build'],
      ['/var/www/orca-build', 'lorem/ipsum/dolor/sit/amet', '/var/www/orca-build/lorem/ipsum/dolor/sit/amet'],
      ['/tmp/test', 'lorem-ipsum', '/tmp/test/lorem-ipsum'],
      ['/1/2/3/4/5', '../../../3/4/../4/5/6', '/1/2/3/4/5/6'],
      ['/1/2/../2/3', NULL, '/1/2/3'],
      ['/lorem/ipsum', 'dolor/sit/amet/', '/lorem/ipsum/dolor/sit/amet'],
      ['///lorem///ipsum///', NULL, '/lorem/ipsum'],
    ];
  }

  protected function createFixture(): Fixture {
    /** @var \Acquia\Orca\Utility\ConfigLoader $config_loader */
    $config_loader = $this->configLoader->reveal();
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    return new Fixture($config_loader, $filesystem, $this->rootPath);
  }

}
