<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\ProcessRunner;
use Acquia\Orca\Fixture\WebServer;
use PHPUnit\Framework\TestCase;

class WebServerTest extends TestCase {

  public function testConstruction() {
    $fixture = $this->prophesize(Fixture::class);
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $fixture->reveal();
    $process_runner = $this->prophesize(ProcessRunner::class);
    /** @var \Acquia\Orca\ProcessRunner $process_runner */
    $process_runner = $process_runner->reveal();

    $object = new WebServer($fixture, $process_runner);

    $this->assertInstanceOf(WebServer::class, $object, 'Successfully instantiated class.');
  }

}
