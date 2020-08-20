<?php

namespace Acquia\Orca\Tests\Helper\Filesystem;

use Acquia\Orca\Helper\Filesystem\FinderFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Acquia\Orca\Helper\Filesystem\FinderFactory
 * @covers ::create
 */
class FinderFactoryTest extends TestCase {

  /**
   * @covers ::create
   */
  public function testFactory(): void {
    $factory = new FinderFactory();

    $first = $factory->create();
    $second = $factory->create();
    $clone = clone($first);

    self::assertEquals($first, $second, 'Multiple calls create equal objects.');
    self::assertNotSame($first, $second, 'Multiple calls do not return the same instance.');
    self::assertNotSame($first, $clone, 'Cloning an instance results in a new instance.');

    $first->name('test');

    self::assertNotEquals($first, $second, 'Changes to one instance do not affect others.');
  }

}
