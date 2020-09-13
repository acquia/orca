<?php

namespace Acquia\Orca\Domain\Composer;

use Acquia\Orca\Exception\FileNotFoundException as OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Helper\Config\ConfigLoader;
use Composer\Package\Version\VersionGuesser as ComposerVersionGuesserAlias;
use Exception;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundExceptionAlias;
use Noodlehaus\Exception\ParseException;

/**
 * Provides a facade for encapsulating Composer version guessing.
 */
class VersionGuesser {

  /**
   * The Composer version guesser.
   *
   * @var \Composer\Package\Version\VersionGuesser
   */
  private $composerGuesser;

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Helper\Config\ConfigLoader
   */
  private $configLoader;

  /**
   * Constructs an instance.
   */
  public function __construct(ConfigLoader $config_loader, ComposerVersionGuesserAlias $composer_version_guesser) {
    $this->composerGuesser = $composer_version_guesser;
    $this->configLoader = $config_loader;
  }

  /**
   * Guesses the version of a local package.
   *
   * @param string $path
   *   The path to the package to guess.
   *
   * @return string
   *   The guessed version string.
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Acquia\Orca\Exception\ParseError
   */
  public function guessVersion(string $path): string {
    try {
      $composer_json_path = "{$path}/composer.json";
      $package_config = $this->configLoader
        ->load($composer_json_path)
        ->all();
    }
    catch (NoodlehausFileNotFoundExceptionAlias $e) {
      throw new OrcaFileNotFoundException("No such file: {$composer_json_path}");
    }
    catch (ParseException $e) {
      throw new ParseError("Cannot parse {$composer_json_path}");
    }
    catch (Exception $e) {
      throw new OrcaException("Unknown error guessing version at {$path}");
    }

    $guess = $this->composerGuesser
      ->guessVersion($package_config, $path);
    return (empty($guess['version'])) ? '@dev' : $guess['version'];
  }

}
