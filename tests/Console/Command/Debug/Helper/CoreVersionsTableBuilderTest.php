<?php

namespace Acquia\Orca\Tests\Console\Command\Debug\Helper;

use Acquia\Orca\Console\Command\Debug\Helper\CoreVersionsTableBuilder;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Acquia\Orca\Tests\Enum\_Helper\DrupalCoreVersionTestEnum;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionResolver
 */
class CoreVersionsTableBuilderTest extends TestCase {

  protected function setUp(): void {
    $this->drupalCoreVersionResolver = $this->prophesize(DrupalCoreVersionResolver::class);
  }

  private function createCoreVersionsTableBuilder(): CoreVersionsTableBuilder {
    $drupal_core_version_resolver = $this->drupalCoreVersionResolver->reveal();
    return new class ($drupal_core_version_resolver) extends CoreVersionsTableBuilder {

      protected function getVersions(): array {
        return DrupalCoreVersionTestEnum::values();
      }

    };
  }

  /**
   * @dataProvider providerBuild
   */
  public function testBuild($include_examples, $include_resolved, $resolved1, $resolved2, $headers, $rows): void {
    $this->drupalCoreVersionResolver
      ->resolve(Argument::any())
      ->willReturn($resolved1, $resolved2);
    $expected = (new Table(new NullOutput()))
      ->setHeaders($headers)
      ->setRows($rows);
    $builder = $this->createCoreVersionsTableBuilder();

    $actual = $builder->build(new NullOutput(), $include_examples, $include_resolved);

    self::assertEquals($expected, $actual, 'Built expected table.');
  }

  public function providerBuild(): array {
    return [
      [
        'include_examples' => FALSE,
        'include_resolved' => FALSE,
        'resolved1' => '',
        'resolved2' => '',
        'headers' => ['Version', 'Description'],
        'rows' => [
          ['LOREM', 'Lorem description'],
          ['IPSUM', 'Ipsum description'],
        ],
      ],
      [
        'include_examples' => TRUE,
        'include_resolved' => FALSE,
        'resolved1' => '',
        'resolved2' => '',
        'headers' => ['Version', 'Example', 'Description'],
        'rows' => [
          ['LOREM', '1.0.0', 'Lorem description'],
          ['IPSUM', '2.0.0', 'Ipsum description'],
        ],
      ],
      [
        'include_examples' => FALSE,
        'include_resolved' => TRUE,
        'resolved1' => '1.1.1',
        'resolved2' => '2.2.2',
        'headers' => ['Version', 'Resolved', 'Description'],
        'rows' => [
          ['LOREM', '1.1.1', 'Lorem description'],
          ['IPSUM', '2.2.2', 'Ipsum description'],
        ],
      ],
      [
        'include_examples' => TRUE,
        'include_resolved' => TRUE,
        'resolved1' => '1.1.1',
        'resolved2' => '2.2.2',
        'headers' => ['Version', 'Example', 'Resolved', 'Description'],
        'rows' => [
          ['LOREM', '1.0.0', '1.1.1', 'Lorem description'],
          ['IPSUM', '2.0.0', '2.2.2', 'Ipsum description'],
        ],
      ],
    ];
  }

  public function testBuildWithUnresolvableVersions(): void {
    $this->drupalCoreVersionResolver
      ->resolve(Argument::any())
      ->willThrow(OrcaVersionNotFoundException::class);
    $expected = (new Table(new NullOutput()))
      ->setHeaders(['Version', 'Resolved', 'Description'])
      ->setRows([
        ['LOREM', '~', 'Lorem description'],
        ['IPSUM', '~', 'Ipsum description'],
      ]);
    $builder = $this->createCoreVersionsTableBuilder();

    $actual = $builder->build(new NullOutput(), FALSE, TRUE);

    self::assertEquals($expected, $actual, 'Built expected table.');
  }

}
