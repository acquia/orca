<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides environment variables.
 *
 * @method static EnvVarEnum ORCA_COVERAGE_CLOVER()
 * @method static EnvVarEnum ORCA_COVERAGE_COBERTURA()
 * @method static EnvVarEnum ORCA_COVERAGE_ENABLE()
 * @method static EnvVarEnum ORCA_ENABLE_NIGHTWATCH()
 * @method static EnvVarEnum ORCA_FIXTURE_DIR()
 * @method static EnvVarEnum ORCA_FIXTURE_PROFILE()
 * @method static EnvVarEnum ORCA_JOB()
 * @method static EnvVarEnum ORCA_PACKAGES_CONFIG()
 * @method static EnvVarEnum ORCA_PACKAGES_CONFIG_ALTER()
 * @method static EnvVarEnum ORCA_PHPCS_STANDARD()
 * @method static EnvVarEnum ORCA_ROOT()
 * @method static EnvVarEnum ORCA_SUT_BRANCH()
 * @method static EnvVarEnum ORCA_SUT_DIR()
 * @method static EnvVarEnum ORCA_SUT_HAS_NIGHTWATCH_TESTS()
 * @method static EnvVarEnum ORCA_SUT_MACHINE_NAME()
 * @method static EnvVarEnum ORCA_SUT_NAME()
 * @method static EnvVarEnum ORCA_TELEMETRY_ENABLE()
 * @method static EnvVarEnum ORCA_YARN_DIR()
 * @method static EnvVarEnum DRUPAL_NIGHTWATCH_IGNORE_DIRECTORIES()
 * @method static EnvVarEnum DRUPAL_NIGHTWATCH_OUTPUT()
 * @method static EnvVarEnum DRUPAL_NIGHTWATCH_SEARCH_DIRECTORY()
 * @method static EnvVarEnum DRUPAL_TEST_BASE_URL()
 * @method static EnvVarEnum DRUPAL_TEST_CHROMEDRIVER_AUTOSTART()
 * @method static EnvVarEnum DRUPAL_TEST_DB_URL()
 * @method static EnvVarEnum DRUPAL_TEST_WEBDRIVER_CHROME_ARGS()
 * @method static EnvVarEnum DRUPAL_TEST_WEBDRIVER_HOSTNAME()
 * @method static EnvVarEnum DRUPAL_TEST_WEBDRIVER_PORT()
 */
class EnvVarEnum extends Enum {

  public const ORCA_COVERAGE_CLOVER = 'ORCA_COVERAGE_CLOVER';

  public const ORCA_COVERAGE_COBERTURA = 'ORCA_COVERAGE_COBERTURA';

  public const ORCA_COVERAGE_ENABLE = 'ORCA_COVERAGE_ENABLE';

  public const ORCA_ENABLE_NIGHTWATCH = 'ORCA_ENABLE_NIGHTWATCH';

  public const ORCA_FIXTURE_DIR = 'ORCA_FIXTURE_DIR';

  public const ORCA_FIXTURE_PROFILE = 'ORCA_FIXTURE_PROFILE';

  public const ORCA_GOOGLE_API_CLIENT_ID = 'ORCA_GOOGLE_API_CLIENT_ID';

  public const ORCA_GOOGLE_API_CLIENT_SECRET = 'ORCA_GOOGLE_API_CLIENT_SECRET';

  public const ORCA_GOOGLE_API_REFRESH_TOKEN = 'ORCA_GOOGLE_API_REFRESH_TOKEN';

  public const ORCA_JOB = 'ORCA_JOB';

  public const ORCA_PACKAGES_CONFIG = 'ORCA_PACKAGES_CONFIG';

  public const ORCA_PACKAGES_CONFIG_ALTER = 'ORCA_PACKAGES_CONFIG_ALTER';

  public const ORCA_PHPCS_STANDARD = 'ORCA_PHPCS_STANDARD';

  public const ORCA_ROOT = 'ORCA_ROOT';

  public const ORCA_SUT_BRANCH = 'ORCA_SUT_BRANCH';

  public const ORCA_SUT_DIR = 'ORCA_SUT_DIR';

  public const ORCA_SUT_HAS_NIGHTWATCH_TESTS = 'ORCA_SUT_HAS_NIGHTWATCH_TESTS';

  public const ORCA_SUT_MACHINE_NAME = 'ORCA_SUT_MACHINE_NAME';

  public const ORCA_SUT_NAME = 'ORCA_SUT_NAME';

  public const ORCA_TELEMETRY_ENABLE = 'ORCA_TELEMETRY_ENABLE';

  public const ORCA_YARN_DIR = 'ORCA_YARN_DIR';

  public const DRUPAL_NIGHTWATCH_IGNORE_DIRECTORIES = 'DRUPAL_NIGHTWATCH_IGNORE_DIRECTORIES';

  public const DRUPAL_NIGHTWATCH_OUTPUT = 'DRUPAL_NIGHTWATCH_OUTPUT';

  public const DRUPAL_NIGHTWATCH_SEARCH_DIRECTORY = 'DRUPAL_NIGHTWATCH_SEARCH_DIRECTORY';

  public const DRUPAL_TEST_BASE_URL = 'DRUPAL_TEST_BASE_URL';

  public const DRUPAL_TEST_CHROMEDRIVER_AUTOSTART = 'DRUPAL_TEST_CHROMEDRIVER_AUTOSTART';

  public const DRUPAL_TEST_DB_URL = 'DRUPAL_TEST_DB_URL';

  public const DRUPAL_TEST_WEBDRIVER_CHROME_ARGS = 'DRUPAL_TEST_WEBDRIVER_CHROME_ARGS';

  public const DRUPAL_TEST_WEBDRIVER_HOSTNAME = 'DRUPAL_TEST_WEBDRIVER_HOSTNAME';

  public const DRUPAL_TEST_WEBDRIVER_PORT = 'DRUPAL_TEST_WEBDRIVER_PORT';

  /**
   * Descriptions for the environment variables.
   *
   * @return array
   *   An associative array of variable names and their descriptions.
   */
  public static function descriptions(): array {
    return [
      self::ORCA_COVERAGE_CLOVER => 'The path where ORCA saves the PHPUnit test coverage Clover XML file',
      self::ORCA_COVERAGE_COBERTURA => 'The path where ORCA saves the PHPUnit test coverage Cobertura XML file',
      self::ORCA_COVERAGE_ENABLE => 'Whether or not to generate test coverage data',
      self::ORCA_ENABLE_NIGHTWATCH => 'Whether or not to run Nightwatch.js tests',
      self::ORCA_FIXTURE_DIR => 'The directory ORCA uses for test fixtures',
      self::ORCA_FIXTURE_PROFILE => 'The Drupal installation profile ORCA installs in fixtures',
      self::ORCA_GOOGLE_API_CLIENT_ID => 'The Google API Client ID',
      self::ORCA_GOOGLE_API_CLIENT_SECRET => 'The Google API Client Secret',
      self::ORCA_GOOGLE_API_REFRESH_TOKEN => 'The Google API Refresh Token',
      self::ORCA_JOB => 'The name of the ORCA CI job',
      self::ORCA_PACKAGES_CONFIG => 'The path to a config file to completely replace the list of packages ORCA installs in fixtures and runs tests on',
      self::ORCA_PACKAGES_CONFIG_ALTER => 'The path to a config file to alter the main list of packages ORCA installs in fixtures and runs tests on',
      self::ORCA_PHPCS_STANDARD => 'The PHP Code Sniffer standard to use',
      self::ORCA_ROOT => 'The path to the root of ORCA itself (Read-only)',
      self::ORCA_SUT_BRANCH => 'The name of the nearest Git version branch of the SUT',
      self::ORCA_SUT_DIR => 'The path to the SUT',
      self::ORCA_SUT_HAS_NIGHTWATCH_TESTS => 'Whether or not the SUT has Nightwatch.js tests (Read-only)',
      self::ORCA_SUT_MACHINE_NAME => 'The machine name of the SUT, "example" (Read-only)',
      self::ORCA_SUT_NAME => 'The full package name of the SUT, e.g., "drupal/example"',
      self::ORCA_TELEMETRY_ENABLE => 'Whether or not to enable telemetry',
      self::ORCA_YARN_DIR => 'The directory to install Yarn in (Read-only)',
      self::DRUPAL_NIGHTWATCH_IGNORE_DIRECTORIES => 'Directories to ignore when searching for Nightwatch.js tests (Read-only)',
      self::DRUPAL_NIGHTWATCH_OUTPUT => 'The directory to output Nightwatch.js reports to (Read-only)',
      self::DRUPAL_NIGHTWATCH_SEARCH_DIRECTORY => 'The directory to search for Nightwatch.js tests (Read-only)',
      self::DRUPAL_TEST_BASE_URL => 'The base URL for functional tests (Read-only)',
      self::DRUPAL_TEST_CHROMEDRIVER_AUTOSTART => 'Whether or not to automatically start ChromeDriver (Read-only)',
      self::DRUPAL_TEST_DB_URL => 'The Drupal database URL (Read-only)',
      self::DRUPAL_TEST_WEBDRIVER_CHROME_ARGS => 'The Chrome WebDriver arguments (Read-only)',
      self::DRUPAL_TEST_WEBDRIVER_HOSTNAME => 'The WebDriver hostname (Read-only)',
      self::DRUPAL_TEST_WEBDRIVER_PORT => 'The WebDriver port (Read-only)',
    ];
  }

  /**
   * Gets the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription(): string {
    $descriptions = static::descriptions();
    return $descriptions[$this->getKey()];
  }

}
