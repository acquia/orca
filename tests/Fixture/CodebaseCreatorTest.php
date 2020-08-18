<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Composer\ComposerFacade;
use Acquia\Orca\Fixture\CodebaseCreator;
use Acquia\Orca\Git\GitFacade;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Composer\ComposerFacade|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Git\GitFacade|\Prophecy\Prophecy\ObjectProphecy $git
 */
class CodebaseCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->composer = $this->prophesize(ComposerFacade::class);
    $this->git = $this->prophesize(GitFacade::class);
  }

  private function createCodebaseCreator(): CodebaseCreator {
    $composer = $this->composer->reveal();
    $git = $this->git->reveal();
    return new CodebaseCreator($composer, $git);
  }

  /**
   * @dataProvider providerCreate
   */
  public function testCreate(string $project_template_string, string $stability, string $directory): void {
    $this->composer
      ->createProject($project_template_string, $stability, $directory)
      ->shouldBeCalledOnce();
    $this->git
      ->ensureFixtureRepo()
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
