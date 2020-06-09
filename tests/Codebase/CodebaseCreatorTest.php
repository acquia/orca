<?php

namespace Acquia\Orca\Tests\Codebase;

use Acquia\Orca\Codebase\CodebaseCreator;
use PHPUnit\Framework\TestCase;

class CodebaseCreatorTest extends TestCase {

  public function testInstantiation(): void {
    $object = new CodebaseCreator();
    $this->assertInstanceOf(CodebaseCreator::class, $object, 'Instantiated class.');
  }

}
