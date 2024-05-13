<?php

namespace Acquia\Orca\Tests\Domain\Composer\Version;

use Acquia\Orca\Domain\Composer\Version\VersionSelectorFactory;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Acquia\Orca\Tests\TestCase;
use Composer\Repository\RepositorySet;

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
      ->call('createDefaultRepositorySet')
      ->shouldBeCalledOnce();
    $spy
      ->call('addDrupalDotOrgRepository')
      ->shouldBeCalledTimes((int) $include_drupal_dot_org);

    $selector_factory = new class ($spy->reveal()) extends VersionSelectorFactory {

      private $spy;

      public function __construct(TestSpy $spy) {
        $this->spy = $spy;
      }

      protected function createDefaultRepositorySet(bool $dev): RepositorySet {
        $this->spy->call('createDefaultRepositorySet');
        return parent::createDefaultRepositorySet($dev);
      }

      protected function addDrupalDotOrgRepository(RepositorySet $repository_set): void {
        $this->spy->call('addDrupalDotOrgRepository');
        parent::addDrupalDotOrgRepository($repository_set);
      }

    };

    $selector_factory->create($include_drupal_dot_org, $dev);

  }

  public static function providerCreate(): array {
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
