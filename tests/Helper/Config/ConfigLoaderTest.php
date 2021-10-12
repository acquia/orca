<?php

namespace Acquia\Orca\Tests\Helper\Config;

use Acquia\Orca\Exception\OrcaDirectoryNotFoundException;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundException;
use Noodlehaus\Exception\ParseException as NoodlehausParseException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 * @coversDefaultClass \Acquia\Orca\Helper\Config\ConfigLoader
 */
class ConfigLoaderTest extends TestCase {

  private const CONFIG_DIR_PATH = 'var/www/example';

  private const CONFIG_FILE_PATH = 'var/www/example/composer.json';

  protected function setUp(): void {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->exists(Argument::any())
      ->shouldBeCalledOnce()
      ->willReturn(TRUE);
  }

  private function createConfigLoader(): ConfigLoader {
    $filesystem = $this->filesystem->reveal();

    return new class ($filesystem) extends ConfigLoader {

      public function loadConfig($path):  Config {
        return parent::loadConfig([]);
      }

    };

  }

  public function testLoadSuccess(): void {
    $this->filesystem
      ->exists(self::CONFIG_DIR_PATH)
      ->shouldBeCalledOnce()
      ->willReturn(TRUE);
    $loader = $this->createConfigLoader();

    $loader->load(self::CONFIG_FILE_PATH);

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(ConfigLoader::class, $loader, 'Instantiated class.');
  }

  public function testLoadDirectoryNotFound(): void {
    $this->filesystem
      ->exists(Argument::any())
      ->willReturn(FALSE);
    $loader = $this->createConfigLoader();
    $this->expectException(OrcaDirectoryNotFoundException::class);
    $this->expectExceptionMessageMatches('/SUT is absent from expected location.*/');

    $loader->load(self::CONFIG_FILE_PATH);

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(ConfigLoader::class, $loader, 'Instantiated class.');
  }

  /**
   * @dataProvider providerLoadExceptions
   */
  public function testLoadExceptions($caught, $thrown): void {
    $filesystem = $this->filesystem->reveal();
    $loader = new class ($filesystem, $caught) extends ConfigLoader {

      private $caught;

      public function __construct(Filesystem $filesystem, $caught) {
        $this->caught = $caught;
        parent::__construct($filesystem);
      }

      public function loadConfig($path):  Config {
        throw $this->caught;
      }

    };
    $this->expectException($thrown);

    $loader->load(self::CONFIG_FILE_PATH);
  }

  public function providerLoadExceptions(): array {
    return [
      'File not found' => [new NoodlehausFileNotFoundException(), OrcaFileNotFoundException::class],
      'Parse error' => [new NoodlehausParseException(['message' => '']), OrcaParseError::class],
      'Unknown error' => [new \Exception(), OrcaException::class],
    ];
  }

}
