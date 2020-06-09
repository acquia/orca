<?php

namespace Acquia\Orca\Tests\Codebase;

use Acquia\Orca\Codebase\CodebaseCreator;
use Acquia\Orca\Facade\ComposerFacade;
use PHPUnit\Framework\TestCase;

/**
 * @property ComposerFacade|\Prophecy\Prophecy\ObjectProphecy $composer
 */
class CodebaseCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->composer = $this->prophesize(ComposerFacade::class);
  }

  public function testInstantiation(): void {
    $this->createComposerFacade();
  }

  private function createComposerFacade(): CodebaseCreator {
    /** @var \Acquia\Orca\Facade\ComposerFacade $composer */
    $composer = $this->composer->reveal();
    $object = new CodebaseCreator($composer);
    $this->assertInstanceOf(CodebaseCreator::class, $object, 'Instantiated class.');
    return $object;
  }

}
