<?php

namespace Acquia\Orca\Tests\Utility;

use Acquia\Orca\Utility\ConfigLoader;
use Noodlehaus\Config;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase {

  public function testConfigLoader() {
    $loader = new ConfigLoader();
    $config = $loader->load([]);

    self::assertInstanceOf(ConfigLoader::class, $loader, 'Instantiated class.');
    self::assertInstanceOf(Config::class, $config, 'Returned config object.');
  }

}
