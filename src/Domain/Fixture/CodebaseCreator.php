<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Composer\ComposerFacade;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaParseError;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;

/**
 * Creates the codebase component of a fixture.
 */
class CodebaseCreator {

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Domain\Composer\ComposerFacade
   */
  private $composer;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Domain\Git\GitFacade
   */
  private $git;

  /**
   * The fixture options.
   *
   * @var \Acquia\Orca\Options\FixtureOptions
   */
  private $options;

  /**
   * The fixture composer.json helper.
   *
   * @var \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper
   */
  private $composerJsonHelper;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\ComposerFacade $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper $composer_json_helper
   *   The fixture composer.json helper.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Git\GitFacade $git
   *   The Git facade.
   */
  public function __construct(ComposerFacade $composer, ComposerJsonHelper $composer_json_helper, FixturePathHandler $fixture_path_handler, GitFacade $git) {
    $this->composer = $composer;
    $this->composerJsonHelper = $composer_json_helper;
    $this->fixture = $fixture_path_handler;
    $this->git = $git;
  }

  /**
   * Creates the codebase.
   *
   * @param \Acquia\Orca\Options\FixtureOptions $options
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  public function create(FixtureOptions $options): void {
    $this->options = $options;
    $this->createProject();
    $this->git->ensureFixtureRepo();
    $this->configureComposerProject();
  }

  /**
   * Creates the Composer project.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function createProject(): void {
    if (!$this->options->hasSut()) {
      $this->composer->createProject($this->options);
      return;
    }

    /* @var \Acquia\Orca\Domain\Package\Package $sut */
    $sut = $this->options->getSut();
    if (!$sut->isProjectTemplate()) {
      $this->composer->createProject($this->options);
      return;
    }

    $this->composer->createProjectFromPackage($sut);
  }

  /**
   * Configures the Composer project (i.e., composer.json).
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   */
  private function configureComposerProject(): void {
    $composer_json_path = $this->fixture->getPath('composer.json');
    try {
      $this->writeSettings();
    }
    catch (OrcaFileNotFoundException $e) {
      throw new OrcaFileNotFoundException("No such file: {$composer_json_path}");
    }
    catch (OrcaParseError $e) {
      throw new OrcaParseError("Cannot parse {$composer_json_path}");
    }
  }

  /**
   * Writes settings to the composer.json.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaFixtureNotExistsException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  private function writeSettings(): void {
    $this->composerJsonHelper->writeFixtureOptions($this->options);

    // Prevent errors later because "Source directory docroot/core has
    // uncommitted changes" after "Removing package drupal/core so that it can
    // be re-installed and re-patched".
    // @see https://drupal.stackexchange.com/questions/273859
    $this->composerJsonHelper->set('config.discard-changes', TRUE);

    $this->composerJsonHelper->set('extra.composer-exit-on-patch-failure', !$this->options->ignorePatchFailure());
  }

}
