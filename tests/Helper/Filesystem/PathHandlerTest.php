<?php

namespace Acquia\Orca\Tests\Helper\Filesystem;

use Acquia\Orca\Helper\Filesystem\AbstractPathHandler;
use Acquia\Orca\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @property string $basePath
 * @covers \Acquia\Orca\Helper\Filesystem\AbstractPathHandler
 */
class PathHandlerTest extends TestCase {

  protected function setUp(): void {
    $this->filesystem = $this->prophesize(Filesystem::class);
  }

  protected function createPathHandler(): AbstractPathHandler {
    $filesystem = $this->filesystem->reveal();
    return new class($filesystem, $this->basePath) extends AbstractPathHandler {};
  }

  /**
   * @dataProvider providerExists
   */
  public function testExists($base_path, $exists): void {
    $this->basePath = $base_path;
    $this->filesystem
      ->exists($base_path)
      ->shouldBeCalledOnce()
      ->willReturn($exists);
    $path_handler = $this->createPathHandler();

    $return = $path_handler->exists();

    self::assertEquals($exists, $return, 'Returned correct value.');
  }

  public function providerExists(): array {
    return [
      ['/path-exists', TRUE],
      ['/no-path-there', FALSE],
    ];
  }

  /**
   * @covers \Acquia\Orca\Helper\Filesystem\AbstractPathHandler::exists
   * @dataProvider providerExistsWithSubpath
   */
  public function testExistsWithSubpath($sub_path, $exists): void {
    $this->basePath = '/example';
    $full_path = "{$this->basePath}/{$sub_path}";
    $this->filesystem
      ->exists($full_path)
      ->shouldBeCalledOnce()
      ->willReturn($exists);

    $path_handler = $this->createPathHandler();
    $return = $path_handler->exists($sub_path);

    self::assertEquals($exists, $return, 'Returned correct value.');
  }

  public function providerExistsWithSubpath(): array {
    return [
      ['path-exists', TRUE],
      ['no-path-there', FALSE],
    ];
  }

  /**
   * @dataProvider providerGetPath
   * @covers \Acquia\Orca\Helper\Filesystem\AbstractPathHandler::getPath
   */
  public function testGetPath($base_path, $sub_path, $expected): void {
    $this->basePath = $base_path;

    $path_handler = $this->createPathHandler();

    self::assertEquals($expected, $path_handler->getPath($sub_path), 'Resolved path.');
  }

  public function providerGetPath(): array {
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

}
