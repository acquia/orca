<?php

namespace Acquia\Orca\Tests\Helper;

use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property \Acquia\Orca\Tests\_Helper\TestSpy|\Prophecy\Prophecy\ObjectProphecy $spy
 */
class EnvFacadeTest extends TestCase {

  protected TestSpy|ObjectProphecy $spy;

  protected function setUp(): void {
    $this->spy = $this->prophesize(TestSpy::class);
  }

  private function createEnvFacade(): EnvFacade {
    $spy = $this->spy->reveal();
    return new class($spy) extends EnvFacade {

      private $spy;

      public function __construct(TestSpy $spy) {
        $this->spy = $spy;
      }

      /** @noinspection ReturnTypeCanBeDeclaredInspection */
      protected function getVar($variable) {
        /* @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->spy->call($variable);
      }

    };

  }

  /**
   * @dataProvider providerGetNoDefault
   */
  public function testGetNoDefault($variable, $expected_return): void {
    $this->spy
      ->call($variable)
      ->shouldBeCalledOnce()
      ->willReturn($expected_return);
    $env = $this->createEnvFacade();

    $actual_return = $env->get($variable);

    self::assertSame($expected_return, $actual_return);
  }

  public function providerGetNoDefault(): array {
    return [
      [
        'variable' => 'test',
        'expected_return' => 'example',
      ],
      [
        'variable' => 'lorem',
        'expected_return' => 'ipsum',
      ],
    ];
  }

  /**
   * @dataProvider providerGetWithDefault
   */
  public function testGetWithDefault($variable, $default, $expected_return): void {
    $this->spy
      ->call($variable)
      ->shouldBeCalledOnce()
      ->willReturn(NULL);
    $env = $this->createEnvFacade();

    $actual_return = $env->get($variable, $default);

    self::assertSame($expected_return, $actual_return);
  }

  public function providerGetWithDefault(): array {
    return [
      [
        'variable' => 'lorem',
        'default' => 'ipsum',
        'expected_return' => 'ipsum',
      ],
      [
        'variable' => 'sit',
        'default' => 'amet',
        'expected_return' => 'amet',
      ],
    ];
  }

}
