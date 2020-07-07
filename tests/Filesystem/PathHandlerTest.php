<?php

namespace Acquia\Orca\Tests\Filesystem;

use Acquia\Orca\Filesystem\AbstractPathHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property string $basePath
 * @covers \Acquia\Orca\Filesystem\AbstractPathHandler
 */
class PathHandlerTest extends TestCase {

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
  }

  /**
   * @dataProvider providerExists
   */
  public function testExists($base_path, $exists) {
    $this->basePath = $base_path;
    $this->filesystem
      ->exists($base_path)
      ->willReturn($exists);
    $path_handler = $this->createPathHander();

    $return = $path_handler->exists();

    $this->filesystem
      ->exists($base_path)
      ->shouldHaveBeenCalledTimes(1);
    $this->assertEquals($exists, $return, 'Returned correct value.');
  }

  public function providerExists() {
    return [
      ['/path-exists', TRUE],
      ['/no-path-there', FALSE],
    ];
  }

  /**
   * @dataProvider providerGetPath
   * @covers \Acquia\Orca\Filesystem\AbstractPathHandler::getPath
   */
  public function testGetPath($base_path, $sub_path, $expected) {
    $this->basePath = $base_path;

    $path_handler = $this->createPathHander();

    $this->assertEquals($expected, $path_handler->getPath($sub_path), 'Resolved path.');
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

  protected function createPathHander() {
    $filesystem = $this->filesystem->reveal();
    return new class($filesystem, $this->basePath) extends AbstractPathHandler {};
  }

}
