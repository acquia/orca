<?php

namespace Acquia\Orca\Tests\Utility;

use Acquia\Orca\Utility\ConfigLoader;
use Noodlehaus\Config;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase {

  public function testConfigLoader() {
    $loader = new ConfigLoader();
    $config = $loader->load([]);

    $this->assertInstanceOf(ConfigLoader::class, $loader, 'Instantiated class.');
    $this->assertInstanceOf(Config::class, $config, 'Returned config object.');
  }

}
