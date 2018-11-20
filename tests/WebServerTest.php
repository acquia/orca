<?php

namespace Acquia\Orca\Tests;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\WebServer;
use PHPUnit\Framework\TestCase;

class WebServerTest extends TestCase {

  public function testConstruction() {
    $fixture = $this->prophesize(Fixture::class);
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $fixture->reveal();

    $object = new WebServer($fixture);

    $this->assertInstanceOf(WebServer::class, $object, 'Successfully instantiated class.');
  }

}
