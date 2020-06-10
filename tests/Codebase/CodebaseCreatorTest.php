<?php

namespace Acquia\Orca\Tests\Codebase;

use Acquia\Orca\Codebase\CodebaseCreator;
use Acquia\Orca\Facade\ComposerFacade;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Facade\ComposerFacade|\Prophecy\Prophecy\ObjectProphecy $composer
 */
class CodebaseCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->composer = $this->prophesize(ComposerFacade::class);
  }

  private function createCodebaseCreator(): CodebaseCreator {
    $composer = $this->composer->reveal();
    return new CodebaseCreator($composer);
  }

  /**
   * @dataProvider providerCreate
   */
  public function testCreate(string $project_template_string, string $stability, string $directory): void {
    $this->composer
      ->createProject($project_template_string, $stability, $directory)
      ->shouldBeCalledOnce();

    $creator = $this->createCodebaseCreator();
    $creator->create($project_template_string, $stability, $directory);
  }

  public function providerCreate(): array {
    return [
      ['test/example-project1', 'alpha', '/var/www/orca-build1'],
      ['test/example-project2', 'dev', '/var/www/orca-build2'],
    ];
  }

}
