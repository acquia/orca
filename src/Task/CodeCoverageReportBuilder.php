<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Exception\DirectoryNotFoundException;
use Acquia\Orca\Exception\FileNotFoundException as OrcaFileNotFoundException;
use Acquia\Orca\Exception\ParseError as OrcaParseError;
use Acquia\Orca\Filesystem\FinderFactory;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Task\StaticAnalysisTool\PhplocTask;
use Acquia\Orca\Utility\ConfigLoader;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundExceptionAlias;
use Noodlehaus\Exception\ParseException as NoodlehausParseException;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException as FinderDirectoryNotFoundException;

/**
 * Builds a code coverage report.
 */
class CodeCoverageReportBuilder {

  private const FINDER_PATH_EXCLUSIONS = [
    '@docroot/.*@',
    '@var/.*@',
    '@vendor/.*@',
  ];

  /**
   * PHP filename extensions as Finder expects them.
   *
   * @see \Acquia\Orca\Task\PhplocFacade::PHP_EXTENSIONS
   */
  private const PHP_NAME_PATTERNS = [
    '*.php',
    '*.module',
    '*.theme',
    '*.inc',
    '*.install',
    '*.profile',
    '*.engine',
  ];

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Utility\ConfigLoader
   */
  private $configLoader;

  /**
   * The Finder factory.
   *
   * @var \Acquia\Orca\Filesystem\FinderFactory
   */
  private $finderFactory;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The path to analyze.
   *
   * @var string
   */
  private $path = '';

  /**
   * The PHPLOC data.
   *
   * @var \Noodlehaus\Config
   */
  private $phplocData;

  /**
   * The data on tests.
   *
   * @var int[]
   */
  private $testsData = [
    'classes' => 0,
    'assertions' => 0,
  ];

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ConfigLoader $config_loader
   *   The config loader.
   * @param \Acquia\Orca\Filesystem\FinderFactory $finder_factory
   *   The finder factory.
   * @param \Acquia\Orca\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   */
  public function __construct(ConfigLoader $config_loader, FinderFactory $finder_factory, OrcaPathHandler $orca_path_handler) {
    $this->configLoader = $config_loader;
    $this->finderFactory = $finder_factory;
    $this->orca = $orca_path_handler;
  }

  /**
   * Builds the report.
   *
   * @param string $path
   *   The path to build the report from.
   *
   * @return array
   *   The report data as multidimensional array suitable for
   *   StatusTable::setRows().
   *
   * @see \Acquia\Orca\Console\Helper\StatusTable::setRows()
   *
   * @throws \Exception
   *   In case of errors.
   */
  public function build(string $path): array {
    $this->path = $path;
    $this->ensurePreconditions();
    $this->compileData();
    return $this->buildTable();
  }

  /**
   * Ensures that the preconditions are met.
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   *   In case no files are found to scan.
   */
  private function ensurePreconditions(): void {
    $finder = $this->finderFactory->create();
    $php = $finder
      ->in($this->path)
      ->name(self::PHP_NAME_PATTERNS)
      ->notPath(self::FINDER_PATH_EXCLUSIONS);
    if (!iterator_count($php)) {
      throw new OrcaFileNotFoundException('No files found to scan');
    }
  }

  /**
   * Compiles the data from the various sources.
   *
   * @throws \Exception
   *   In case of error.
   */
  private function compileData(): void {
    $this->getPhplocData();
    $this->getTestsData();
  }

  /**
   * Gets the PHPLOC log data.
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   *   In case of absent PHPLOC JSON log.
   * @throws \Acquia\Orca\Exception\ParseError
   *   In case of error parsing PHPLOC JSON log.
   */
  private function getPhplocData(): void {
    $log_path = $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH);
    try {
      $config = $this->configLoader->load($log_path);
    }
    catch (NoodlehausFileNotFoundExceptionAlias $e) {
      throw new OrcaFileNotFoundException($e->getMessage());
    }
    catch (NoodlehausParseException $e) {
      throw new OrcaParseError($e->getMessage());
    }
    $this->phplocData = $config->all();
  }

  /**
   * Gets the data on tests.
   *
   * @throws \Acquia\Orca\Exception\DirectoryNotFoundException
   *   In case of missing directory or non-directory path.
   */
  private function getTestsData(): void {
    $finder = $this->finderFactory->create();
    try {
      $classes = $finder
        ->in($this->path)
        ->name('*Test.php')
        ->notPath(self::FINDER_PATH_EXCLUSIONS)
        ->contains('public function test');
    }
    catch (FinderDirectoryNotFoundException $e) {
      throw new DirectoryNotFoundException($e->getMessage());
    }

    $this->testsData['classes'] = iterator_count($classes);

    foreach ($classes as $file) {
      $contents = $file->getContents();
      $this->testsData['assertions'] += substr_count($contents, '::assert');
      $this->testsData['assertions'] += substr_count($contents, '->assert');
    }
  }

  /**
   * Compiles the report data into a table array.
   *
   * @return array
   *   The report data array.
   */
  private function buildTable(): array {
    $complexity = $this->phplocData['ccn'];
    $assertions = $this->testsData['assertions'];
    return [
      ['  Test assertions', $assertions],
      ['÷ Cyclomatic complexity', $complexity],
      new TableSeparator(),
      ['  Magic number', $this->computeMagicNumber()],
    ];
  }

  /**
   * Computes the health score.
   *
   * @return float
   *   The score as a floating point number.
   */
  private function computeMagicNumber(): float {
    $assertions = $this->testsData['assertions'];
    $complexity = $this->phplocData['ccn'];

    if (!$assertions || !$complexity) {
      return 0;
    }

    return number_format($assertions / $complexity, 2);
  }

}
