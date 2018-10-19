<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Creator;
use Acquia\Orca\Fixture\Destroyer;
use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\Tests\Tester;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Acquia\Orca\Fixture\Facade
 *
 * @property \Prophecy\Prophecy\ObjectProphecy filesystem
 * @property string $rootPath
 */
class FacadeTest extends TestCase {

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->rootPath = '/var/www/orca/orca-build';
  }

  public function testConstruction() {
    $fixture = $this->createFacade();

    $this->assertTrue($fixture instanceof Facade, 'Instantiated class.');
  }

  /**
   * @dataProvider providerCollaboratorGetters
   */
  public function testCollaboratorGetters($getter, $class) {
    $fixture = $this->createFacade();

    $object = $fixture->{$getter}();

    $this->assertTrue($object instanceof $class, 'Instantiated class.');
  }

  public function providerCollaboratorGetters() {
    return [
      ['getCreator', Creator::class],
      ['getDestroyer', Destroyer::class],
      ['getTester', Tester::class],
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
    $facade = $this->createFacade();

    $return = $facade->exists();

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
  public function testPathResolution($root_path, $docroot_path, $product_module_path) {
    $this->rootPath = $root_path;
    $facade = $this->createFacade();
    $sub_path = '/some/sub-path';

    $this->assertEquals($root_path, $facade->rootPath(), 'Resolved root path.');
    $this->assertEquals("{$root_path}/{$sub_path}", $facade->rootPath($sub_path), 'Resolved root path with sub-path.');
    $this->assertEquals($docroot_path, $facade->docrootPath(), 'Resolved docroot path.');
    $this->assertEquals("{$docroot_path}/{$sub_path}", $facade->docrootPath($sub_path), 'Resolved docroot path with sub-path.');
    $this->assertEquals($product_module_path, $facade->productModuleInstallPath(), 'Resolved product module path.');
  }

  public function providerPathResolution() {
    return [
      ['/var/www/orca-build', '/var/www/orca-build/docroot', '/var/www/orca-build/docroot/modules/contrib/acquia'],
      ['/tmp/test', '/tmp/test/docroot', '/tmp/test/docroot/modules/contrib/acquia'],
    ];
  }

  protected function createFacade(): Facade {
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    return new Facade($filesystem, $this->rootPath);
  }

}
