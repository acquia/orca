<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\CodebaseFixture;
use Acquia\Orca\Fixture\FixtureInterface;
use PHPUnit\Framework\TestCase;

class CodebaseFixtureTest extends TestCase {

  public function testConstruction() {
    $codebase = new CodebaseFixture('/var/www/orca');

    $this->assertTrue($codebase instanceof CodebaseFixture, 'Instantiated class.');
    $this->assertTrue($codebase instanceof FixtureInterface, 'Implemented correct interface.');
  }

  /**
   * @dataProvider providerPathResolution
   */
  public function testPathResolution($orca_base, $root_path, $docroot_path) {
    $codebase = new CodebaseFixture($orca_base);

    $this->assertEquals($root_path, $codebase->rootPath(), 'Resolved root path.');
    $this->assertEquals($docroot_path, $codebase->docrootPath(), 'Resolved docroot path.');
  }

  public function providerPathResolution() {
    return [
      ['/var/www/orca', '/var/www/orca-build', '/var/www/orca-build/docroot'],
      ['/tmp/test', '/tmp/test-build', '/tmp/test-build/docroot'],
    ];
  }

}
