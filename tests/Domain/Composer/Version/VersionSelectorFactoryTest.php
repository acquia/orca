<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Composer\Repository\RepositorySet;
use PHPUnit\Framework\TestCase;

/**
 * @property \Composer\Composer|\Prophecy\Prophecy\ObjectProphecy $composer
 * @coversDefaultClass \Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory
 */
class VersionSelectorFactoryTest extends TestCase {

  protected function createVersionSelectorFactory(): VersionSelectorFactory {
    return new VersionSelectorFactory();
  }

  /**
   * @dataProvider providerCreate
   * @covers ::create
   */
  public function testCreate($include_drupal_dot_org, $dev): void {
    $spy = $this->prophesize(TestSpy::class);
    $spy
      ->call()
      ->shouldBeCalledTimes((int) $include_drupal_dot_org);

    $selector_factory = new class ($spy->reveal()) extends VersionSelectorFactory {

      private $spy;

      public function __construct(TestSpy $spy) {
        $this->spy = $spy;
      }

      protected function addDrupalDotOrgRepository(RepositorySet $repository_set): void {
        $this->spy->call();
        parent::addDrupalDotOrgRepository($repository_set);
      }

    };

    $selector_factory->create($include_drupal_dot_org, $dev);

  }

  public function providerCreate(): array {
    return [
      [
        'include_drupal_dot_org' => TRUE,
        'dev' => TRUE,
      ],
      [
        'include_drupal_dot_org' => TRUE,
        'dev' => FALSE,
      ],
      [
        'include_drupal_dot_org' => FALSE,
        'dev' => TRUE,
      ],
      [
        'include_drupal_dot_org' => FALSE,
        'dev' => FALSE,
      ],
    ];
  }

}
