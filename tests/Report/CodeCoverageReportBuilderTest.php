<?php

namespace Acquia\Orca\Tests\Report;

use Acquia\Orca\Exception\DirectoryNotFoundException as OrcaDirectoryNotFoundException;
use Acquia\Orca\Exception\FileNotFoundException as OrcaFileNotFoundException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Filesystem\FinderFactory;
use Acquia\Orca\Filesystem\OrcaPathHandler;
use Acquia\Orca\Report\CodeCoverageReportBuilder;
use Acquia\Orca\Task\StaticAnalysisTool\PhplocTask;
use Acquia\Orca\Utility\ConfigLoader;
use ArrayIterator;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundException;
use Noodlehaus\Exception\ParseException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException as FinderDirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @property \ArrayIterator $classIterator
 * @property \Noodlehaus\Config|\Prophecy\Prophecy\ObjectProphecy $config
 * @property \Acquia\Orca\Utility\ConfigLoader|\Prophecy\Prophecy\ObjectProphecy $configLoader
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder $finder
 * @property \Acquia\Orca\Filesystem\FinderFactory|\Prophecy\Prophecy\ObjectProphecy $finderFactory
 * @property \Acquia\Orca\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @coversDefaultClass \Acquia\Orca\Report\CodeCoverageReportBuilder
 */
class CodeCoverageReportBuilderTest extends TestCase {

  private const DEFAULT_PATH = 'test/example';

  private $phplocData = [
    'directories' => 23,
    'files' => 78,
    'loc' => 9593,
    'lloc' => 2011,
    'llocClasses' => 1550,
    'llocFunctions' => 2,
    'llocGlobal' => 459,
    'cloc' => 4065,
    'ccn' => 374,
    'ccnMethods' => 369,
    'interfaces' => 3,
    'traits' => 1,
    'classes' => 71,
    'abstractClasses' => 4,
    'concreteClasses' => 67,
    'functions' => 5,
    'namedFunctions' => 2,
    'anonymousFunctions' => 3,
    'methods' => 393,
    'publicMethods' => 204,
    'nonPublicMethods' => 189,
    'nonStaticMethods' => 391,
    'staticMethods' => 2,
    'constants' => 27,
    'classConstants' => 27,
    'globalConstants' => 0,
    'testClasses' => 0,
    'testMethods' => 0,
    'ccnByLloc' => 0.1859771258080557,
    'llocByNof' => 0.4,
    'methodCalls' => 1101,
    'staticMethodCalls' => 42,
    'instanceMethodCalls' => 1059,
    'attributeAccesses' => 803,
    'staticAttributeAccesses' => 16,
    'instanceAttributeAccesses' => 787,
    'globalAccesses' => 2,
    'globalVariableAccesses' => 0,
    'superGlobalVariableAccesses' => 2,
    'globalConstantAccesses' => 0,
    'classCcnMin' => 1,
    'classCcnAvg' => 5.92,
    'classCcnMax' => 60,
    'classLlocMin' => 0,
    'classLlocAvg' => 20.666666666666668,
    'classLlocMax' => 257,
    'methodCcnMin' => 1,
    'methodCcnAvg' => 1.9605263157894737,
    'methodCcnMax' => 18,
    'methodLlocMin' => 1,
    'methodLlocAvg' => 3.3263157894736843,
    'methodLlocMax' => 26,
    'namespaces' => 21,
    'ncloc' => 5528,
  ];

  protected function setUp(): void {
    $this->configLoader = $this->prophesize(ConfigLoader::class);
    $this->config = $this->prophesize(Config::class);
    $this->finder = $this->prophesize(Finder::class);
    $this->finder
      ->in(Argument::any())
      ->willReturn($this->finder);
    $this->finder
      ->name(Argument::any())
      ->willReturn($this->finder);
    $this->finder
      ->notPath(Argument::any())
      ->willReturn($this->finder);
    $this->finder
      ->contains(Argument::any())
      ->willReturn($this->finder);
    $this->finderFactory = $this->prophesize(FinderFactory::class);
    $this->classIterator = new ArrayIterator([]);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH)
      ->willReturnArgument();
  }

  /**
   * @dataProvider providerHappyPath
   */
  public function testHappyPath(string $path, int $assertions, int $complexity, string $score): void {
    $this->phplocData['ccn'] = $complexity;
    $this->finder
      ->in($path)
      ->shouldBeCalledOnce()
      ->willReturn($this->finder);
    $this->finder
      ->name('*Test.php')
      ->shouldBeCalledOnce()
      ->willReturn($this->finder);
    $file_info = $this->prophesize(SplFileInfo::class);
    $file_info
      ->getContents()
      ->willReturn('self::assertTrue(TRUE);');
    $file_info = $file_info->reveal();
    $files = array_fill(0, $assertions, $file_info);
    $this->classIterator = new ArrayIterator($files);
    $builder = $this->createBuilder();

    $report = $builder->build($path);

    self::assertEquals([
      ['  Test assertions', $assertions],
      ['÷ Cyclomatic complexity', $complexity],
      new TableSeparator(),
      ['  Magic number', $score],
    ], $report, 'Returned correct report data.');
  }

  public function providerHappyPath(): array {
    return [
      ['test/example', 100, 100, '1.0'],
      ['test/example', 200, 100, '2.0'],
      ['test/example', 100, 200, '0.5'],
      ['test/example', 100, 1, '100.0'],
      ['test/example', 33, 375, '0.1'],
      ['test/example', 0, 100, '0.0'],
      ['test/example', 100, 0, '0.0'],
      ['lorem/ipsum', 1, 1, '1.0'],
    ];
  }

  public function testPathDoesNotExistOrIsNotDirectory(): void {
    $message = 'Example message';
    $this->finder
      ->in(self::DEFAULT_PATH)
      ->willThrow(new FinderDirectoryNotFoundException($message));
    $this->expectException(OrcaDirectoryNotFoundException::class);
    $this->expectExceptionMessage($message);

    $builder = $this->createBuilder();

    $builder->build(self::DEFAULT_PATH);
  }

  public function testNoCoverageData(): void {
    $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce();
    $this->configLoader
      ->load(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce()
      ->willThrow(NoodlehausFileNotFoundException::class);
    $this->expectException(OrcaFileNotFoundException::class);
    $builder = $this->createBuilder();

    $builder->build(self::DEFAULT_PATH);
  }

  public function testInvalidCoverageData(): void {
    $this->orca
      ->getPath(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce();
    $this->configLoader
      ->load(PhplocTask::JSON_LOG_PATH)
      ->shouldBeCalledOnce()
      ->willThrow(ParseException::class);
    $this->expectException(ParseError::class);
    $builder = $this->createBuilder();

    $builder->build(self::DEFAULT_PATH);
  }

  private function createBuilder(): CodeCoverageReportBuilder {
    $this->config
      ->all()
      ->willReturn($this->phplocData);
    $config = $this->config->reveal();
    $this->configLoader
      ->load(Argument::any())
      ->willReturn($config);
    $config_loader = $this->configLoader->reveal();
    $this->finder
      ->getIterator()
      ->willReturn($this->classIterator);
    $finder = $this->finder->reveal();
    $this->finderFactory
      ->create()
      ->willReturn($finder);
    $finder_factory = $this->finderFactory->reveal();
    $orca_path_handler = $this->orca->reveal();
    return new CodeCoverageReportBuilder($config_loader, $finder_factory, $orca_path_handler);
  }

}
