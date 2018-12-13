<?php

namespace Acquia\Orca\Tests\Server;

use Acquia\Orca\Server\ChromeDriverServer;
use Acquia\Orca\Server\WebServer;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\ProcessRunner;
use PHPUnit\Framework\TestCase;

class ServersTest extends TestCase {

  /**
   * @dataProvider providerConstruction
   */
  public function testConstruction($class) {
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->prophesize(Fixture::class)->reveal();
    /** @var \Acquia\Orca\Utility\ProcessRunner $process_runner */
    $process_runner = $this->prophesize(ProcessRunner::class)->reveal();

    $object = new $class($fixture, $process_runner);

    $this->assertInstanceOf($class, $object, 'Successfully instantiated class.');
  }

  public function providerConstruction() {
    return [
      [ChromeDriverServer::class],
      [WebServer::class],
    ];
  }

}
