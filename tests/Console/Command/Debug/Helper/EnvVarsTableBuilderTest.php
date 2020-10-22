<?php

namespace Acquia\Orca\Tests\Console\Command\Debug\Helper;

use Acquia\Orca\Console\Command\Debug\Helper\EnvVarsTableBuilder;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Tests\Enum\_Helper\EnvVarTestEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @property \Acquia\Orca\Helper\EnvFacade|\Prophecy\Prophecy\ObjectProphecy $env
 */
class EnvVarsTableBuilderTest extends TestCase {

  protected function setUp(): void {
    $this->env = $this->prophesize(EnvFacade::class);
  }

  private function createCoreVersionsTableBuilder(): EnvVarsTableBuilder {
    $env_facade = $this->env->reveal();
    return new class ($env_facade) extends EnvVarsTableBuilder {

      protected function getVars(): array {
        return EnvVarTestEnum::values();
      }

    };
  }

  /**
   * @dataProvider providerBuild
   */
  public function testBuild($rows): void {
    $expected = (new Table(new NullOutput()))
      ->setHeaders(['Variable', 'Value', 'Description'])
      ->setRows($rows);
    $builder = $this->createCoreVersionsTableBuilder();

    $actual = $builder->build(new NullOutput());

    self::assertEquals($expected, $actual, 'Built expected table.');
  }

  public function providerBuild(): array {
    return [
      [
        'rows' => [
          ['LOREM', '~', 'Lorem description'],
          ['IPSUM', '~', 'Ipsum description'],
        ],
      ],
    ];
  }

}
