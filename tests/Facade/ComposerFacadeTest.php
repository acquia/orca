<?php

namespace Acquia\Orca\Tests\Facade;

use Acquia\Orca\Facade\ComposerFacade;
use PHPUnit\Framework\TestCase;

class ComposerFacadeTest extends TestCase {

  public function testInstantiation(): void {
    $object = new ComposerFacade();
    $this->assertInstanceOf(ComposerFacade::class, $object, 'Instantiated class.');
  }

}
