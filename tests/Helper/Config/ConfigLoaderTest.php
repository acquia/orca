<?php

namespace Acquia\Orca\Tests\Helper\Config;

use Acquia\Orca\Helper\Config\ConfigLoader;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase {

  public function testConfigLoader(): void {
    $loader = new ConfigLoader();
    $loader->load([]);

    self::assertInstanceOf(ConfigLoader::class, $loader, 'Instantiated class.');
  }

}
