<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Creator;
use Acquia\Orca\Fixture\Destroyer;
use Acquia\Orca\Fixture\Facade;
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

  public function testCreator() {
    $fixture = $this->createFacade();

    $creator = $fixture->getCreator();

    $this->assertTrue($creator instanceof Creator, 'Instantiated class.');
  }

  public function testDestroyer() {
    $fixture = $this->createFacade();

    $destroyer = $fixture->getDestroyer();

    $this->assertTrue($destroyer instanceof Destroyer, 'Instantiated class.');
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
  public function testPathResolution($root_path, $docroot_path) {
    $this->rootPath = $root_path;
    $facade = $this->createFacade();

    $this->assertEquals($root_path, $facade->rootPath(), 'Resolved root path.');
    $this->assertEquals($docroot_path, $facade->docrootPath(), 'Resolved docroot path.');
  }

  public function providerPathResolution() {
    return [
      ['/var/www/orca-build', '/var/www/orca-build/docroot'],
      ['/tmp/test', '/tmp/test/docroot'],
    ];
  }

  protected function createFacade(): Facade {
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    return new Facade($filesystem, $this->rootPath);
  }

}
