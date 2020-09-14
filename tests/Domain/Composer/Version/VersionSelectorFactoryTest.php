<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Composer\Package\Version\VersionSelector;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory
 */
class VersionSelectorFactoryTest extends TestCase {

  /**
   * @covers ::create
   */
  public function testCreate(): void {
    $selector = VersionSelectorFactory::create();

    /* @noinspection UnnecessaryAssertionInspection */
    self::assertInstanceOf(VersionSelector::class, $selector, 'Created a version selector.');
  }

}
